<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MongoDB\Client as MongoClient;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use ZipArchive;
use Throwable;

class BackupController extends Controller
{
    /**
     * GET /api/backups/export?type=excel|csv|json
     * - excel: XLSX con una hoja por colección
     * - csv: ZIP con un CSV por colección
     * - json: ZIP con un JSON por colección (array de documentos)
     */
    public function export(Request $request)
    {
        try {
            $type   = strtolower($request->query('type', 'json'));
            $db     = config('database.connections.mongodb.database');
            $folder = 'backups';
            Storage::makeDirectory($folder);

            if ($type === 'excel') {
                $fileName = 'backup_'.$db.'_'.now()->format('Ymd_His').'.xlsx';
                $path = $folder . '/' . $fileName;

                $dataByCollection = $this->fetchAllCollectionsAsArrays();
                Excel::store(
                    new \App\Exports\MongoCollectionsExport($dataByCollection),
                    $path,
                    null,
                    \Maatwebsite\Excel\Excel::XLSX
                );

                return Storage::download($path, $fileName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ]);
            }

            if ($type === 'csv') {
                // ZIP con un CSV por colección (usando buffer en memoria)
                $zipName = 'backup_'.$db.'_'.now()->format('Ymd_His').'.zip';
                $zipDiskPath = storage_path('app/'.$folder.'/'.$zipName);

                $dataByCollection = $this->fetchAllCollectionsAsArrays();

                $zip = new ZipArchive();
                if ($zip->open($zipDiskPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    Log::error('No se pudo crear el ZIP de respaldo (CSV).', ['path' => $zipDiskPath]);
                    return response()->json(['message' => 'No se pudo crear ZIP'], 500);
                }

                foreach ($dataByCollection as $collection => $rows) {
                    $headers = $this->collectAllKeys($rows);
                    $csv = $this->rowsToCsvString($headers, $rows);
                    $zip->addFromString($collection.'.csv', $csv);
                }

                $zip->close();

                return response()->download($zipDiskPath, $zipName, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="'.$zipName.'"',
                ])->deleteFileAfterSend(true);
            }

            // === JSON (ZIP por colección) ===
            if ($type === 'json') {
                $zipName = 'backup_'.$db.'_'.now()->format('Ymd_His').'.json.zip';
                $zipDiskPath = storage_path('app/'.$folder.'/'.$zipName);

                $dataByCollection = $this->fetchAllCollectionsAsArrays();

                $zip = new ZipArchive();
                if ($zip->open($zipDiskPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    Log::error('No se pudo crear el ZIP de respaldo (JSON).', ['path' => $zipDiskPath]);
                    return response()->json(['message' => 'No se pudo crear ZIP'], 500);
                }

                foreach ($dataByCollection as $collection => $rows) {
                    // Guardamos cada colección como un array JSON pretty
                    $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $zip->addFromString($collection.'.json', $json === false ? '[]' : $json);
                }

                $zip->close();

                return response()->download($zipDiskPath, $zipName, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="'.$zipName.'"',
                ])->deleteFileAfterSend(true);
            }

            return response()->json(['message' => 'Tipo no soportado. Usa excel|csv|json'], 422);

        } catch (Throwable $e) {
            Log::error('Export backup error', ['ex' => $e]);
            return response()->json([
                'message' => 'No se pudo generar el respaldo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/backups/import
     * multipart/form-data: file, format (excel|csv|txt|json), mode (replace|merge)
     *
     * JSON soportado:
     *  - Un .zip con varios .json (cada archivo es una colección: <nombre>.json = array de documentos)
     *  - Un .json único con un array de documentos => requiere ?collection=Nombre
     *  - Un .json único con objeto { "coleccionA": [...], "coleccionB": [...] }  (combinado)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file'   => ['required', 'file'],
            'format' => ['required', 'in:excel,csv,txt,json'],
            'mode'   => ['nullable', 'in:replace,merge']
        ]);

        try {
            $format = $request->input('format');
            $mode   = $request->input('mode', 'merge');
            $file   = $request->file('file');

            if ($format === 'excel') {
                $uploadedPath = $file->store('imports');
                $fullPath = storage_path('app/'.$uploadedPath);

                $sheets = \Maatwebsite\Excel\Facades\Excel::toArray([], $fullPath);
                if (empty($sheets)) {
                    return response()->json(['message' => 'El Excel no contiene hojas'], 422);
                }

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $collectionName = Str::snake($sheet->getTitle());
                    $rows = $sheet->toArray(null, true, true, true);
                    if (count($rows) < 2) continue;

                    $headers = array_values(array_map('trim', array_values($rows[1] ?? [])));
                    $docs = [];
                    for ($i=2; $i<=count($rows); $i++) {
                        $row = $rows[$i];
                        if (!$row) continue;
                        $doc = [];
                        $j=0;
                        foreach ($headers as $h) {
                            $col = chr(65+$j);
                            $val = $row[$col] ?? null;
                            if (is_string($val) && $this->looksLikeJson($val)) {
                                $decoded = json_decode($val, true);
                                if (json_last_error() === JSON_ERROR_NONE) $val = $decoded;
                            }
                            $doc[$h] = $val;
                            $j++;
                        }
                        if (!empty($doc)) $docs[] = $doc;
                    }
                    $this->bulkUpsert($collectionName, $docs, $mode);
                }
                return response()->json(['message' => 'Importación Excel completada']);
            }

            if ($format === 'csv' || $format === 'txt') {
                $uploadedPath = $file->store('imports');
                $fullPath = storage_path('app/'.$uploadedPath);

                $ext = strtolower($file->getClientOriginalExtension());
                if ($ext === 'zip') {
                    $zip = new ZipArchive();
                    if ($zip->open($fullPath) === true) {
                        $extractPath = storage_path('app/imports/'.pathinfo($uploadedPath, PATHINFO_FILENAME));
                        @mkdir($extractPath, 0775, true);
                        $zip->extractTo($extractPath);
                        $zip->close();

                        $files = glob($extractPath.'/*.csv');
                        foreach ($files as $csvPath) {
                            $collectionName = Str::snake(pathinfo($csvPath, PATHINFO_FILENAME));
                            $docs = $this->readCsv($csvPath);
                            $this->bulkUpsert($collectionName, $docs, $mode);
                        }
                    } else {
                        return response()->json(['message' => 'No se pudo abrir ZIP'], 422);
                    }
                } else {
                    $collectionName = Str::snake($request->query('collection', 'import_generic'));
                    $docs = $this->readCsv($fullPath);
                    $this->bulkUpsert($collectionName, $docs, $mode);
                }
                return response()->json(['message' => 'Importación CSV/TXT completada']);
            }

            if ($format === 'json') {
                $uploadedPath = $file->store('imports');
                $fullPath = storage_path('app/'.$uploadedPath);

                $ext = strtolower($file->getClientOriginalExtension());
                if ($ext === 'zip') {
                    // ZIP con varios .json => cada archivo es una colección
                    $zip = new ZipArchive();
                    if ($zip->open($fullPath) === true) {
                        $extractPath = storage_path('app/imports/'.pathinfo($uploadedPath, PATHINFO_FILENAME));
                        @mkdir($extractPath, 0775, true);
                        $zip->extractTo($extractPath);
                        $zip->close();

                        $files = glob($extractPath.'/*.json');
                        foreach ($files as $jsonPath) {
                            $collectionName = Str::snake(pathinfo($jsonPath, PATHINFO_FILENAME));
                            $docs = $this->readJsonFile($jsonPath, $request->query('collection'));
                            // readJsonFile retorna:
                            // - array de docs (para colecciones sueltas)
                            // - o ['__combined__' => [ 'colA'=>[], 'colB'=>[] ]] si es combinado (no será el caso por archivo)
                            if (isset($docs['__combined__'])) {
                                foreach ($docs['__combined__'] as $col => $arr) {
                                    $this->bulkUpsert(Str::snake($col), $arr, $mode);
                                }
                            } else {
                                $this->bulkUpsert($collectionName, $docs, $mode);
                            }
                        }
                    } else {
                        return response()->json(['message' => 'No se pudo abrir ZIP'], 422);
                    }
                } else {
                    // Un solo .json: puede ser colección única (array) o combinado (objeto con colecciones)
                    $parsed = $this->readJsonFile($fullPath, $request->query('collection'));
                    if (isset($parsed['__combined__'])) {
                        foreach ($parsed['__combined__'] as $col => $arr) {
                            $this->bulkUpsert(Str::snake($col), $arr, $mode);
                        }
                    } else {
                        $collectionName = Str::snake($request->query('collection', 'import_generic'));
                        $this->bulkUpsert($collectionName, $parsed, $mode);
                    }
                }
                return response()->json(['message' => 'Importación JSON completada']);
            }

            return response()->json(['message' => 'Formato no soportado'], 422);
        } catch (Throwable $e) {
            Log::error('Import backup error', ['ex' => $e]);
            return response()->json([
                'message' => 'Error en importación',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /* =================== Helpers =================== */

    // Lee todas las colecciones y retorna [ 'coleccion' => [ [doc], ... ] ]
    protected function fetchAllCollectionsAsArrays(): array
    {
        $client = $this->mongoClient();
        $dbName = config('database.connections.mongodb.database');
        $db = $client->selectDatabase($dbName);

        $list = $db->listCollections();
        $out = [];
        foreach ($list as $colInfo) {
            $name = $colInfo->getName();
            if (Str::startsWith($name, 'system.')) continue;

            $cursor = $db->selectCollection($name)->find();
            $rows = [];
            foreach ($cursor as $doc) {
                $arr = json_decode(json_encode($doc), true);
                // normaliza _id a string
                if (isset($arr['_id']) && is_array($arr['_id']) && isset($arr['_id']['$oid'])) {
                    $arr['_id'] = $arr['_id']['$oid'];
                } elseif (isset($arr['_id']) && is_object($arr['_id'])) {
                    $arr['_id'] = (string)$arr['_id'];
                }
                $rows[] = $arr;
            }
            $out[$name] = $rows;
        }
        return $out;
    }

    protected function collectAllKeys(array $rows): array
    {
        $keys = [];
        foreach ($rows as $r) {
            foreach (array_keys($r) as $k) $keys[$k] = true;
        }
        // _id primero si existe
        $all = array_keys($keys);
        usort($all, function ($a, $b) {
            if ($a === '_id') return -1;
            if ($b === '_id') return 1;
            return strcmp($a, $b);
        });
        return $all;
    }

    protected function rowsToCsvString(array $headers, array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        if (!empty($headers)) {
            fputcsv($fh, $headers);
            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $h) {
                    $val = $row[$h] ?? null;
                    if (is_array($val) || is_object($val)) {
                        $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                    }
                    $line[] = $val;
                }
                fputcsv($fh, $line);
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return (string)$csv;
    }

    /**
     * Lee un archivo JSON y devuelve:
     * - array de documentos (colección simple)
     * - o ['__combined__' => [ 'colA' => [...], 'colB' => [...] ]] si es combinado
     */
    protected function readJsonFile(string $path, ?string $collectionQueryParam = null): array
    {
        $content = @file_get_contents($path);
        if ($content === false) return [];

        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) return [];

        // Caso A: array => documentos para UNA colección (necesita ?collection si no está en ZIP)
        if (is_array($decoded) && array_is_list($decoded)) {
            return $decoded;
        }

        // Caso B: objeto => puede ser combinado { "colA": [...], "colB": [...] }
        if (is_array($decoded) && !array_is_list($decoded)) {
            // Si viene ?collection=Nombre, toma solo esa
            if ($collectionQueryParam && isset($decoded[$collectionQueryParam]) && is_array($decoded[$collectionQueryParam])) {
                return $decoded[$collectionQueryParam];
            }

            // Si no hubo ?collection, interpretamos como combinado
            $map = [];
            foreach ($decoded as $col => $arr) {
                if (is_array($arr)) {
                    $map[$col] = $arr;
                }
            }
            return ['__combined__' => $map];
        }

        return [];
    }

    protected function mongoClient(): MongoClient
    {
        $host = config('database.connections.mongodb.host', '127.0.0.1');
        $port = config('database.connections.mongodb.port', 27017);
        $user = config('database.connections.mongodb.username');
        $pass = config('database.connections.mongodb.password');
        $authSource = config('database.connections.mongodb.options.database') ?? 'admin';
        $dsn       = config('database.connections.mongodb.dsn');

        if (!empty($dsn)) {
            return new MongoClient($dsn);
        }

        $uri = "mongodb://";
        if ($user) $uri .= urlencode($user).($pass ? ':' . urlencode($pass) : '') . '@';
        $uri .= $host . ':' . $port;
        $uri .= '/?retryWrites=true&w=majority&authSource=' . $authSource;

        return new MongoClient($uri);
    }

    protected function looksLikeJson($val): bool
    {
        $s = trim((string)$val);
        if ($s === '' || (!Str::startsWith($s, '{') && !Str::startsWith($s, '['))) return false;
        json_decode($s, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) return [];
        $docs = [];
        $headers = fgetcsv($fh);
        if (!$headers) { fclose($fh); return []; }

        while (($row = fgetcsv($fh)) !== false) {
            $doc = [];
            foreach ($headers as $i => $h) {
                $h = trim((string)$h);
                if ($h === '') continue;
                $val = $row[$i] ?? null;
                if (is_string($val) && $this->looksLikeJson($val)) {
                    $decoded = json_decode($val, true);
                    if (json_last_error() === JSON_ERROR_NONE) $val = $decoded;
                }
                $doc[$h] = $val;
            }
            if (!empty($doc)) $docs[] = $doc;
        }
        fclose($fh);
        return $docs;
    }

    protected function bulkUpsert(string $collectionName, array $docs, string $mode = 'merge'): void
    {
        $client = $this->mongoClient();
        $dbName = config('database.connections.mongodb.database');
        $col = $client->selectDatabase($dbName)->selectCollection($collectionName);

        if ($mode === 'replace') {
            $col->drop();
            if (!empty($docs)) $col->insertMany($docs);
            return;
        }

        $bulk = [];
        $insertBatch = [];
        foreach ($docs as $d) {
            if (isset($d['_id']) && $d['_id'] !== null && $d['_id'] !== '') {
                $bulk[] = [
                    'updateOne' => [
                        ['_id' => $d['_id']],
                        ['$set' => $d],
                        ['upsert' => true]
                    ]
                ];
            } else {
                $insertBatch[] = $d;
            }
        }
        if (!empty($insertBatch)) $col->insertMany($insertBatch);
        if (!empty($bulk)) $col->bulkWrite($bulk);
    }
}

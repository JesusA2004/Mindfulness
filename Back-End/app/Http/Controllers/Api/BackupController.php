<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use MongoDB\Client as MongoClient;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class BackupController extends Controller
{
    /**
     * GET /api/backups/export?type=excel|csv|mongo
     * - excel: genera un XLSX con una hoja por colección
     * - csv: genera un ZIP con un CSV por colección
     * - mongo: genera un dump nativo (archivo .archive.gz mediante mongodump)
     */
    public function export(Request $request)
    {
        $type = strtolower($request->query('type', 'mongo')); // default mongo
        $db     = config('database.connections.mongodb.database');
        $folder = 'backups';
        Storage::makeDirectory($folder);

        switch ($type) {
            case 'excel':
                $fileName = 'backup_'.$db.'_'.now()->format('Ymd_His').'.xlsx';
                $path = $folder . '/' . $fileName;
                // Construir Excel con una hoja por colección:
                $dataByCollection = $this->fetchAllCollectionsAsArrays();
                // Usamos Excel::raw para crear el archivo en disco:
                Excel::store(new \App\Exports\MongoCollectionsExport($dataByCollection), $path, null, \Maatwebsite\Excel\Excel::XLSX);
                return Storage::download($path, $fileName);

            case 'csv':
                $zipName = 'backup_'.$db.'_'.now()->format('Ymd_His').'.zip';
                $zipPath = storage_path('app/'.$folder.'/'.$zipName);

                $dataByCollection = $this->fetchAllCollectionsAsArrays();
                // Crear CSVs temporales y zipearlos:
                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                    return response()->json(['message' => 'No se pudo crear ZIP'], 500);
                }

                foreach ($dataByCollection as $collection => $rows) {
                    $csvTemp = tmpfile();
                    $meta = stream_get_meta_data($csvTemp);
                    $tmpPath = $meta['uri'];

                    // Escribir encabezados + filas (asumiendo documentos flat/normalizados)
                    $headers = $this->collectAllKeys($rows);
                    fputcsv($csvTemp, $headers);
                    foreach ($rows as $row) {
                        $line = [];
                        foreach ($headers as $h) {
                            $val = $row[$h] ?? null;
                            if (is_array($val) || is_object($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                            $line[] = $val;
                        }
                        fputcsv($csvTemp, $line);
                    }
                    fflush($csvTemp);
                    $zip->addFile($tmpPath, $collection.'.csv');

                    // No cerramos tmpfile hasta cerrar zip (Windows lock-safe)
                }
                $zip->close();

                return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);

            case 'mongo':
            default:
                // Dump nativo: archivo .archive.gz
                $fileName = 'backup_'.$db.'_'.now()->format('Ymd_His').'.archive.gz';
                $fullPath = storage_path('app/'.$folder.'/'.$fileName);
                $cmd = [
                    'mongodump',
                    '--db='.$db,
                    '--archive='.$fullPath,
                    '--gzip',
                ];

                // Si usas usuario/contraseña:
                $username = config('database.connections.mongodb.username');
                $password = config('database.connections.mongodb.password');
                $host     = config('database.connections.mongodb.host', '127.0.0.1');
                $port     = config('database.connections.mongodb.port', 27017);

                $cmd[] = '--host='.$host.':'.$port;
                if ($username) $cmd[] = '--username='.$username;
                if ($password) $cmd[] = '--password='.$password;
                // Si tienes authSource
                $authSource = config('database.connections.mongodb.options.database') ?? null;
                if ($authSource) $cmd[] = '--authenticationDatabase='.$authSource;

                $process = new Process($cmd);
                $process->setTimeout(3600);
                $process->run();

                if (!$process->isSuccessful()) {
                    return response()->json([
                        'message' => 'Error al generar dump Mongo',
                        'error'   => $process->getErrorOutput(),
                    ], 500);
                }

                return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
        }
    }

    /**
     * POST /api/backups/import
     * multipart/form-data: file, mode (replace|merge opcional), format (excel|csv|txt|mongo)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file'   => ['required', 'file'],
            'format' => ['required', 'in:excel,csv,txt,mongo'],
            'mode'   => ['nullable', 'in:replace,merge'] // replace: borra colección antes de importar
        ]);

        $format = $request->input('format');
        $mode   = $request->input('mode', 'merge');
        $file   = $request->file('file');

        switch ($format) {
            case 'mongo':
                // Restaurar dump nativo (.archive.gz o .bson con --archive)
                $db = config('database.connections.mongodb.database');
                $uploadedPath = $file->store('imports');
                $fullPath = storage_path('app/'.$uploadedPath);

                $cmd = [
                    'mongorestore',
                    '--db='.$db,
                    '--archive='.$fullPath,
                    '--gzip',
                ];
                if ($mode === 'replace') $cmd[] = '--drop';

                // Conexión
                $username = config('database.connections.mongodb.username');
                $password = config('database.connections.mongodb.password');
                $host     = config('database.connections.mongodb.host', '127.0.0.1');
                $port     = config('database.connections.mongodb.port', 27017);

                $cmd[] = '--host='.$host.':'.$port;
                if ($username) $cmd[] = '--username='.$username;
                if ($password) $cmd[] = '--password='.$password;
                $authSource = config('database.connections.mongodb.options.database') ?? null;
                if ($authSource) $cmd[] = '--authenticationDatabase='.$authSource;

                $process = new Process($cmd);
                $process->setTimeout(3600);
                $process->run();

                if (!$process->isSuccessful()) {
                    return response()->json([
                        'message' => 'Error al restaurar dump Mongo',
                        'error'   => $process->getErrorOutput(),
                    ], 500);
                }
                return response()->json(['message' => 'Restauración Mongo completada']);

            case 'excel':
                // Un XLSX con varias hojas (una por colección).
                // Requiere que las primeras filas sean encabezados.
                $uploadedPath = $file->store('imports');
                $fullPath = storage_path('app/'.$uploadedPath);

                $sheets = Excel::toArray([], $fullPath); // array de hojas
                // sheets: [ [ [row1], [row2], ... ], [ ... ] ]
                if (empty($sheets)) {
                    return response()->json(['message' => 'El Excel no contiene hojas'], 422);
                }

                // Convención: el nombre de la hoja define el nombre de la colección
                // y la primera fila son los headers.
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $collectionName = Str::snake($sheet->getTitle()); // o deja tal cual
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
                            $col = chr(65+$j); // A,B,C...
                            $val = $row[$col] ?? null;
                            // Intenta decodificar JSON si luce como objeto/array
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

            case 'csv':
            case 'txt':
                // Recibe un ZIP de varios CSV (uno por colección) o un CSV único (una colección)
                $uploadedPath = $file->store('imports');
                $fullPath = storage_path('app/'.$uploadedPath);

                $ext = strtolower($file->getClientOriginalExtension());
                if ($ext === 'zip') {
                    $zip = new \ZipArchive();
                    if ($zip->open($fullPath) === TRUE) {
                        $extractPath = storage_path('app/imports/'.pathinfo($uploadedPath, PATHINFO_FILENAME));
                        @mkdir($extractPath, 0775, true);
                        $zip->extractTo($extractPath);
                        $zip->close();

                        // Procesar todos los CSV en la carpeta
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
                    // Un solo CSV/TXT -> requiere query param ?collection=Nombre
                    $collectionName = Str::snake($request->query('collection', 'import_generic'));
                    $docs = $this->readCsv($fullPath);
                    $this->bulkUpsert($collectionName, $docs, $mode);
                }
                return response()->json(['message' => 'Importación CSV/TXT completada']);
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
            // Evita system collections
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
        return array_values(array_keys($keys));
    }

    protected function mongoClient(): MongoClient
    {
        $host = config('database.connections.mongodb.host', '127.0.0.1');
        $port = config('database.connections.mongodb.port', 27017);
        $user = config('database.connections.mongodb.username');
        $pass = config('database.connections.mongodb.password');
        $authSource = config('database.connections.mongodb.options.database') ?? 'admin';

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
        if (!$headers) return [];

        while (($row = fgetcsv($fh)) !== false) {
            $doc = [];
            foreach ($headers as $i => $h) {
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

    /**
     * Inserta/actualiza en bulk.
     * - mode=replace: elimina colección antes de insertar
     * - mode=merge: upsert por _id si existe; sino inserta
     */
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

        // merge (upsert por _id si viene); si no hay _id, inserta
        $bulk = [];
        $insertBatch = [];
        foreach ($docs as $d) {
            if (isset($d['_id']) && $d['_id']) {
                $id = $d['_id'];
                $filter = ['_id' => $id];
                $update = ['$set' => $d];
                $bulk[] = ['updateOne' => [$filter, $update, ['upsert' => true]]];
            } else {
                $insertBatch[] = $d;
            }
        }
        if (!empty($insertBatch)) $col->insertMany($insertBatch);
        if (!empty($bulk)) $col->bulkWrite($bulk);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectId;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class RestoreController extends Controller
{
    private const ALLOWED_COLLECTIONS = ['tecnicas','recompensas'];
    private const ALLOWED_EXT = ['xlsx','csv','json'];
    private const BLOCKED_ARCHIVES = ['zip','rar','7z','tar','gz','bz2'];

    public function import(Request $request)
    {
        $request->validate([
            'file'       => ['required','file'],
            'format'     => ['required','in:excel,csv,json'],
            'mode'       => ['nullable','in:replace,append'],
            'collection' => ['nullable','string']
        ]);

        $file   = $request->file('file');
        $format = $request->string('format')->toString();
        $mode   = $request->string('mode')->toString() ?: 'append';
        $ext    = strtolower($file->getClientOriginalExtension() ?: '');

        if (in_array($ext, self::BLOCKED_ARCHIVES, true)) {
            return response()->json(['message' => 'No se aceptan archivos comprimidos (.zip, .rar, .7z, ...).'], 422);
        }
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return response()->json(['message' => 'Formato no soportado. Solo XLSX, CSV o JSON.'], 422);
        }

        $collection = null;
        if ($format !== 'excel') {
            $collection = Str::lower(trim((string)$request->input('collection','')));
            if (!in_array($collection, self::ALLOWED_COLLECTIONS, true)) {
                return response()->json(['message' => 'Parámetro "collection" requerido: tecnicas o recompensas.'], 422);
            }
        }

        try {
            $path = $file->store('imports');
            $fullPath = storage_path('app/'.$path);

            if ($format === 'excel') {
                $payload = $this->parseXlsx($fullPath);
            } elseif ($format === 'csv') {
                $payload = [$collection => $this->parseCsv($fullPath)];
            } else {
                $payload = [$collection => $this->parseJson($fullPath)];
            }

            foreach ($payload as $col => $docs) {
                $col = Str::lower($col);
                if (!in_array($col, self::ALLOWED_COLLECTIONS, true)) continue;

                $normDocs = [];
                foreach ($docs as $d) {
                    if (!is_array($d)) continue;

                    // _id → ObjectId si viene 24hex
                    if (isset($d['_id']) && is_string($d['_id']) && preg_match('/^[a-f0-9]{24}$/i', $d['_id'])) {
                        try { $d['_id'] = new ObjectId($d['_id']); } catch (\Throwable $e) { unset($d['_id']); }
                    }

                    if ($col === 'tecnicas') {
                        $d = $this->normalizeTecnica($d);
                        if (!$d) continue;
                    } elseif ($col === 'recompensas') {
                        $d = $this->normalizeRecompensa($d);
                        if (!$d) continue;
                    }

                    $nowIso = now()->toDateTimeString();
                    $d['created_at'] = $d['created_at'] ?? $nowIso;
                    $d['updated_at'] = $d['updated_at'] ?? $nowIso;

                    $normDocs[] = $d;
                }

                if (empty($normDocs)) continue;
                $this->insertDocs($col, $normDocs, $mode);
            }

            return response()->json(['message' => 'Restauración completada.']);
        } catch (Throwable $e) {
            Log::error('Restore import error', ['ex'=>$e]);
            return response()->json([
                'message'=>'No se pudo restaurar la base de datos.',
                'error'=>$e->getMessage()
            ], 500);
        }
    }

    /* ===================== Parsers ===================== */

    // No decodificar automáticamente 'recursos' ni 'calificaciones' si vienen como texto
    private function parseXlsx(string $fullPath): array
    {
        $spreadsheet = IOFactory::load($fullPath);
        $out = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $name = Str::lower(Str::snake(trim($sheet->getTitle())));
            if (!in_array($name, self::ALLOWED_COLLECTIONS, true)) continue;

            $rows = $sheet->toArray(null, true, true, true);
            if (count($rows) < 2) continue;
            $headers = array_values(array_map('trim', array_values($rows[1] ?? [])));

            $docs=[];
            for ($i=2; $i<=count($rows); $i++) {
                $r = $rows[$i] ?? null; if (!$r) continue;
                $doc=[];
                for ($j=0; $j<count($headers); $j++) {
                    $col = chr(65+$j);
                    $key = $headers[$j];
                    $val = $r[$col] ?? null;
                    $doc[$key] = $this->maybeJsonDecode($val, $key);
                }
                if (!empty(array_filter($doc, fn($v)=>$v!==null && $v!==''))) $docs[]=$doc;
            }
            $out[$name]=$docs;
        }
        return $out;
    }

    private function parseCsv(string $fullPath): array
    {
        $fh=fopen($fullPath,'r'); if(!$fh) return [];
        $headers=fgetcsv($fh) ?: [];
        $headers=array_map(fn($h)=>trim((string)$h),$headers);

        $docs=[];
        while(($row=fgetcsv($fh))!==false){
            $doc=[];
            foreach($headers as $i=>$h){
                if($h==='') continue;
                $doc[$h]=$this->maybeJsonDecode($row[$i] ?? null, $h);
            }
            if (!empty(array_filter($doc, fn($v)=>$v!==null && $v!==''))) $docs[]=$doc;
        }
        fclose($fh);
        return $docs;
    }

    private function parseJson(string $fullPath): array
    {
        $content=@file_get_contents($fullPath); if($content===false) return [];
        $decoded=json_decode($content,true);
        return (json_last_error()===JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }

    private function maybeJsonDecode($val, ?string $key=null){
        $k = Str::lower((string)$key);
        if (in_array($k, ['recursos','calificaciones'], true)) {
            return $val; // conservar literal si viene como string
        }
        if(!is_string($val)) return $val;
        $s=trim($val);
        if($s==='' || (!str_starts_with($s,'{') && !str_starts_with($s,'['))) return $val;
        $dec=json_decode($s,true);
        return json_last_error()===JSON_ERROR_NONE ? $dec : $val;
    }

    /* ================= Normalizadores ================= */

    private function normalizeTecnica(array $d): ?array
    {
        // Mínimos
        if (!isset($d['nombre']) || !is_string($d['nombre']) || trim($d['nombre'])==='') return null;
        if (isset($d['duracion']) && !is_numeric($d['duracion'])) return null;

        /* ------- recursos: STRING JSON obligatoriamente ------- */
        if (array_key_exists('recursos', $d)) {
            if (is_string($d['recursos'])) {
                $d['recursos'] = $this->ensureJsonArrayString($d['recursos']);
            } elseif (is_array($d['recursos'])) {
                $list = array_values(array_filter(array_map(function($r){
                    if (!is_array($r)) return null;

                    $url = isset($r['url']) ? (string)$r['url'] : '';
                    $tipoRaw = isset($r['tipo']) && is_string($r['tipo']) ? $r['tipo'] : $this->guessTipoPorUrl($url);
                    $tipo = ucfirst(strtolower(trim($tipoRaw)));

                    $desc = isset($r['descripcion']) && is_string($r['descripcion']) && $r['descripcion'] !== ''
                        ? $r['descripcion']
                        : (strtolower($tipo)==='video'?'video':(strtolower($tipo)==='audio'?'audio':(strtolower($tipo)==='imagen'?'imagen':'documento')));

                    $fecha = isset($r['fecha']) && is_string($r['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $r['fecha'])
                        ? $r['fecha']
                        : date('Y-m-d');

                    $rid = isset($r['_id']) && is_string($r['_id']) && $r['_id'] !== ''
                        ? $r['_id']
                        : (string) new \MongoDB\BSON\ObjectId();

                    return [
                        'tipo'        => $tipo,
                        'url'         => $url,
                        'descripcion' => $desc,
                        'fecha'       => $fecha,
                        '_id'         => $rid,
                    ];
                }, $d['recursos']), fn($x)=>$x!==null));

                $d['recursos'] = json_encode($list, JSON_UNESCAPED_SLASHES);
            } else {
                $d['recursos'] = '[]';
            }
        } else {
            $d['recursos'] = '[]';
        }

        /* ------- calificaciones: STRING JSON obligatoriamente ------- */
        if (array_key_exists('calificaciones', $d)) {
            if (is_string($d['calificaciones'])) {
                $d['calificaciones'] = $this->ensureJsonArrayString($d['calificaciones']);
            } elseif (is_array($d['calificaciones'])) {
                $califs = array_values(array_filter(array_map(function($c){
                    if (!is_array($c)) return null;
                    $c['_id'] = isset($c['_id']) && is_string($c['_id']) && $c['_id'] !== ''
                        ? $c['_id']
                        : (string) new \MongoDB\BSON\ObjectId();
                    return $c;
                }, $d['calificaciones']), fn($x)=>$x!==null));
                $d['calificaciones'] = json_encode($califs, JSON_UNESCAPED_SLASHES);
            } else {
                $d['calificaciones'] = '[]';
            }
        } else {
            $d['calificaciones'] = '[]';
        }

        // Campos base
        if (isset($d['dificultad']) && !is_string($d['dificultad'])) unset($d['dificultad']);
        if (isset($d['categoria'])  && !is_string($d['categoria']))  unset($d['categoria']);
        if (isset($d['descripcion'])&& !is_string($d['descripcion']))unset($d['descripcion']);
        if (isset($d['duracion'])) $d['duracion'] = (int)$d['duracion'];

        return $d;
    }

    private function ensureJsonArrayString(?string $raw): string
    {
        $s = trim((string)$raw);
        if ($s === '') return '[]';
        $dec = json_decode($s, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
            return json_encode($dec, JSON_UNESCAPED_SLASHES);
        }
        // si no es JSON válido, no arriesgamos: lo reemplazamos por arreglo vacío
        return '[]';
    }

    private function normalizeRecompensa(array $d): ?array
    {
        if (!isset($d['nombre']) || !is_string($d['nombre']) || trim($d['nombre'])==='') return null;
        unset($d['canjeo']);

        if (isset($d['puntos_necesarios'])) {
            if (!is_numeric($d['puntos_necesarios'])) return null;
            $d['puntos_necesarios'] = (int)$d['puntos_necesarios'];
        }
        if (isset($d['stock'])) {
            if (!is_numeric($d['stock'])) return null;
            $d['stock'] = (int)$d['stock'];
        }

        if (isset($d['descripcion']) && !is_string($d['descripcion'])) unset($d['descripcion']);
        return $d;
    }

    /* ================ Inserción ================ */

    private function insertDocs(string $collection, array $docs, string $mode): void
    {
        $client = $this->mongo();
        $dbName = config('database.connections.mongodb.database');
        $col = $client->selectDatabase($dbName)->selectCollection($collection);

        if ($mode==='replace'){
            $col->drop();
            if(!empty($docs)) $col->insertMany($docs);
            return;
        }
        if(!empty($docs)) $col->insertMany($docs, ['ordered'=>false]);
    }

    private function mongo(): MongoClient
    {
        $dsn = config('database.connections.mongodb.dsn');
        if ($dsn) return new MongoClient($dsn);

        $host=config('database.connections.mongodb.host','127.0.0.1');
        $port=config('database.connections.mongodb.port',27017);
        $user=config('database.connections.mongodb.username');
        $pass=config('database.connections.mongodb.password');
        $auth=config('database.connections.mongodb.options.database') ?? 'admin';

        $uri="mongodb://";
        if ($user) $uri.=urlencode($user).($pass?':'.urlencode($pass):'').'@';
        $uri.=$host.':'.$port.'/?retryWrites=true&w=majority&authSource='.$auth;
        return new MongoClient($uri);
    }

    private function guessTipoPorUrl(string $url): string
    {
        $s = strtolower($url);
        if (preg_match('#(youtube\.com/watch\?v=|youtu\.be/)#', $s)) return 'Video';
        if (preg_match('#vimeo\.com/\d+#', $s)) return 'Video';
        if (preg_match('#soundcloud\.com/#', $s)) return 'Audio';
        if (preg_match('#open\.spotify\.com/#', $s)) return 'Audio';
        if (preg_match('#\.(png|jpe?g|gif|webp|avif|svg)(\?.*)?$#', $s)) return 'Imagen';
        if (preg_match('#\.(mp4|webm|ogg|mov|m4v)(\?.*)?$#', $s))     return 'Video';
        if (preg_match('#\.(mp3|wav|ogg|m4a)(\?.*)?$#', $s))           return 'Audio';
        return 'Documento';
    }
}

<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Recompensa;
use App\Models\Persona;
use App\Models\User;

class RecompensasCanjeadasController extends BaseReportController
{
    public function index(Request $r)
    {
        $q = Recompensa::query();

        /*
         * ===== Filtro por nombre de recompensa =====
         *  - Parámetro oficial: ?nombre=
         *  - Soporte retro: si no viene nombre, intenta leer ?tipo= (por si algo quedó viejo)
         */
        $nombre = $this->d($r, 'nombre');
        if ($nombre === null) {
            // fallback suave: por si en algún punto se envió ?tipo= como filtro
            $nombre = $this->d($r, 'tipo');
        }

        if ($nombre !== null) {
            // Jenssegers: where 'like' -> /valor/i (contains, case-insensitive)
            $q->where('nombre', 'like', $nombre);
        }

        // Traemos recompensas con su arreglo de canjeos
        $recs = $q->get(['_id', 'nombre', 'canjeo']);

        // ===== Mapear user_id -> persona para enriquecer filas =====
        $uids = [];
        foreach ($recs as $rec) {
            foreach ((array) $rec->canjeo as $c) {
                $uid = (string)($c['usuario_id'] ?? '');
                if ($uid !== '') {
                    $uids[] = $uid;
                }
            }
        }
        $uids = array_values(array_unique($uids));

        $users = empty($uids)
            ? collect()
            : User::whereIn('_id', $this->mixedIn($uids))->get(['_id', 'persona_id']);

        $personaIds = $users->pluck('persona_id')->filter()->unique()->values()->all();

        $personas = empty($personaIds)
            ? collect()
            : Persona::whereIn('_id', $this->mixedIn($personaIds))
                ->get(['_id', 'nombre', 'apellidoPaterno', 'apellidoMaterno', 'matricula'])
                ->keyBy('_id');

        $personaByUser = [];
        foreach ($users as $u) {
            $pid = (string)($u->persona_id ?? '');
            $personaByUser[(string) $u->_id] = $pid && isset($personas[$pid]) ? $personas[$pid] : null;
        }

        // ===== Rango de fechas (aplicado sobre canjeo[*].fechaCanjeo) =====
        $desde = $this->d($r, 'desde');
        $hasta = $this->d($r, 'hasta');

        $D = $desde ? Carbon::parse($desde)->startOfDay() : null;
        $H = $hasta ? Carbon::parse($hasta)->endOfDay()   : null;

        $rows = [];

        foreach ($recs as $rec) {
            $nombreRec = (string)($rec->nombre ?? 'Recompensa');

            foreach ((array) $rec->canjeo as $c) {
                $uid = (string)($c['usuario_id'] ?? '');
                if ($uid === '') {
                    continue;
                }

                // Fecha de canje (string YYYY-MM-DD según tu Request)
                $f = null;
                if (!empty($c['fechaCanjeo'])) {
                    try {
                        $f = Carbon::parse($c['fechaCanjeo']);
                    } catch (\Throwable $e) {
                        $f = null;
                    }
                }

                // Si hay rango de fechas, filtrar
                if ($D && $f && $f->lt($D)) continue;
                if ($H && $f && $f->gt($H)) continue;
                if (($D || $H) && !$f) continue; // pidieron rango y no hay fecha => fuera

                $per = $personaByUser[$uid] ?? null;
                $alumno = $per
                    ? trim(($per->nombre ?? '') . ' ' . ($per->apellidoPaterno ?? '') . ' ' . ($per->apellidoMaterno ?? ''))
                    : 'Alumno';

                $rows[] = [
                    'alumno'     => $alumno !== '' ? $alumno : 'Alumno',
                    'matricula'  => (string)($per->matricula ?? '—'),
                    'recompensa' => $nombreRec,
                    'puntos'     => (int)($c['puntos'] ?? 0),
                    'fecha'      => $f ? $f->format('Y-m-d') : null,
                ];
            }
        }

        // Ordenar por fecha desc (nulls al final)
        usort($rows, function ($a, $b) {
            $fa = $a['fecha'] ? strtotime($a['fecha']) : 0;
            $fb = $b['fecha'] ? strtotime($b['fecha']) : 0;
            return $fb <=> $fa;
        });

        return response()->json(['rows' => $rows]);
    }
}

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

        // Filtro por tipo (nombre)
        $tipo = trim((string)$r->query('tipo', ''));
        if ($tipo !== '') {
            $q->where('nombre', 'like', "%{$tipo}%");
        }

        $recs = $q->get(['_id','nombre','canjeo']);

        // Mapear user_id -> persona para enriquecer filas
        $uids = [];
        foreach ($recs as $rec) {
            foreach ((array)$rec->canjeo as $c) {
                $uid = (string)($c['usuario_id'] ?? '');
                if ($uid !== '') $uids[] = $uid;
            }
        }
        $uids = array_values(array_unique($uids));

        $users = empty($uids) ? collect() : User::whereIn('_id', $uids)->get(['_id','persona_id']);
        $personaIds = $users->pluck('persona_id')->filter()->unique()->values()->all();

        $personas = empty($personaIds)
            ? collect()
            : Persona::whereIn('_id', $personaIds)->get(['_id','nombre','apellidoPaterno','apellidoMaterno','matricula'])->keyBy('_id');

        $personaByUser = [];
        foreach ($users as $u) {
            $pid = (string)($u->persona_id ?? '');
            $personaByUser[(string)$u->_id] = $pid && isset($personas[$pid]) ? $personas[$pid] : null;
        }

        // Rango de fechas
        $desde = trim((string)$r->query('desde',''));
        $hasta = trim((string)$r->query('hasta',''));
        $D = $desde ? Carbon::parse($desde)->startOfDay() : null;
        $H = $hasta ? Carbon::parse($hasta)->endOfDay()   : null;

        $rows = [];

        foreach ($recs as $rec) {
            $nombreRec = (string)($rec->nombre ?? 'Recompensa');

            foreach ((array)$rec->canjeo as $c) {
                $uid = (string)($c['usuario_id'] ?? '');
                if ($uid === '') continue;

                // Fecha de canje (puede venir como string ISO o Y-m-d)
                $f = null;
                if (!empty($c['fechaCanjeo'])) {
                    try { $f = Carbon::parse($c['fechaCanjeo']); } catch (\Throwable $e) {}
                }

                // Si hay rango de fechas, filtrar sólo si hay fecha válida
                if ($D && $f && $f->lt($D)) continue;
                if ($H && $f && $f->gt($H)) continue;
                if (($D || $H) && !$f) continue; // si pidieron rango y no hay fecha, descartar

                $per = $personaByUser[$uid] ?? null;
                $alumno = $per ? trim(($per->nombre ?? '').' '.($per->apellidoPaterno ?? '').' '.($per->apellidoMaterno ?? '')) : 'Alumno';

                $rows[] = [
                    'alumno'     => $alumno !== '' ? $alumno : 'Alumno',
                    'matricula'  => (string)($per->matricula ?? '—'),
                    'tipo'       => $nombreRec,
                    'recompensa' => $nombreRec,
                    'puntos'     => (int)($c['puntos'] ?? 0),
                    'fecha'      => $f ? $f->toDateTimeString() : null,
                ];
            }
        }

        // Ordena por fecha desc (nulls al final)
        usort($rows, function ($a, $b) {
            $fa = $a['fecha'] ? strtotime($a['fecha']) : 0;
            $fb = $b['fecha'] ? strtotime($b['fecha']) : 0;
            return $fb <=> $fa;
        });

        return response()->json(['rows' => $rows]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Tecnica;
use App\Models\Bitacora;
use Illuminate\Support\Carbon; // zona horaria
use Exception;

class DashboardController extends Controller
{
    public function overview(Request $request)
    {
        try {
            // Zona horaria MX para "hoy"
            $todayMx = Carbon::now('America/Mexico_City')->toDateString(); // "YYYY-MM-DD"

            // Separados por rol
            $totalEstudiantes = User::where('rol', 'estudiante')->count();
            $totalDocentes    = User::where('rol', 'profesor')->count();
            $totalUsuarios    = $totalEstudiantes + $totalDocentes;

            $totalTecnicas    = Tecnica::count();

            // Bitácoras de HOY (fecha string "YYYY-MM-DD")
            $bitacorasHoy     = Bitacora::where('fecha', $todayMx)->count();

            // Total global (mantener por compatibilidad)
            $bitacorasTotales = Bitacora::count();

            return response()->json([
                'usuariosTotales'  => (int) $totalUsuarios,
                'estudiantes'      => (int) $totalEstudiantes,
                'docentes'         => (int) $totalDocentes,
                'totalTecnicas'    => (int) $totalTecnicas,
                'bitacorasHoy'     => (int) $bitacorasHoy,
                'bitacorasTotales' => (int) $bitacorasTotales,
                'hoy'              => $todayMx,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'usuariosTotales'  => 0,
                'estudiantes'      => 0,
                'docentes'         => 0,
                'totalTecnicas'    => 0,
                'bitacorasHoy'     => 0,
                'bitacorasTotales' => 0,
                'hoy'              => Carbon::now('America/Mexico_City')->toDateString(),
            ], 200);
        }
    }

    public function bitacorasPorMes(Request $request)
    {
        $year   = (int) ($request->query('year') ?: date('Y'));
        $labels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        try {
            // Pipeline con fecha STRING "YYYY-MM-DD"
            $pipeline = [
                [
                    '$match' => [
                        'fecha' => [
                            '$type'  => 'string',
                            '$regex' => '^' . $year . '\-',
                        ],
                    ],
                ],
                [
                    '$project' => [
                        '_id'   => 0,
                        'month' => [
                            '$toInt' => [
                                '$substrBytes' => ['$fecha', 5, 2]
                            ]
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => ['m' => '$month'],
                        'count' => ['$sum' => 1],
                    ],
                ],
                [
                    '$project' => [
                        '_id'   => 0,
                        'month' => '$_id.m',
                        'count' => 1,
                    ],
                ],
                [ '$sort' => ['month' => 1] ],
            ];

            $cursor = Bitacora::raw(fn($c) => $c->aggregate($pipeline));

            $countsByMonth = array_fill(1, 12, 0);
            foreach ($cursor as $doc) {
                $m = (int) ($doc['month'] ?? 0);
                $c = (int) ($doc['count'] ?? 0);
                if ($m >= 1 && $m <= 12) $countsByMonth[$m] = $c;
            }

            return response()->json([
                'labels' => $labels,
                'data'   => array_values($countsByMonth),
                'year'   => $year,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'labels' => $labels,
                'data'   => array_fill(0, 12, 0),
                'year'   => $year,
            ], 200);
        }
    }
}

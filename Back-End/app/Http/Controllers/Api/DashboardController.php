<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Tecnica;
use App\Models\Bitacora;

use Exception;

class DashboardController extends Controller
{
    public function overview(Request $request)
    {
        try {
            // roles según tu modelo: estudiante | profesor | admin
            $totalUsuarios  = User::whereIn('rol', ['estudiante', 'profesor'])->count();
            $totalTecnicas  = Tecnica::count();
            $totalBitacoras = Bitacora::count();

            return response()->json([
                'totalUsuarios'  => (int) $totalUsuarios,
                'totalTecnicas'  => (int) $totalTecnicas,
                'totalBitacoras' => (int) $totalBitacoras,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'totalUsuarios'  => 0,
                'totalTecnicas'  => 0,
                'totalBitacoras' => 0,
            ], 200);
        }
    }

    public function bitacorasPorMes(Request $request)
    {
        $year   = (int) ($request->query('year') ?: date('Y'));
        $labels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        try {
            // Pipeline usando fecha STRING "YYYY-MM-DD"
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

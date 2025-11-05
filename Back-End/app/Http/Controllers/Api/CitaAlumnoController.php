<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId as MongoObjectId;
use App\Models\Cita;
use App\Http\Resources\CitaResource;

class CitaAlumnoController extends Controller
{
    /**
     * Muestra las citas del alumno autenticado.
     */
    public function index(Request $request)
    {
        $uid = auth()->id() ?? $request->user()?->_id ?? null;
        if (!$uid) return response()->json(['message' => 'Usuario no autenticado'], 401);

        try {
            $citas = Cita::with([
                    'docente.persona',   // <-- necesario para docente_nombre
                    'alumno.persona',    // opcional, por si lo muestras
                ])
                ->where('alumno_id', new MongoObjectId($uid))
                ->orderByDesc('fecha_cita')
                ->get();

            return CitaResource::collection($citas);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al cargar tus citas',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Permite registrar una nueva cita (solo alumno).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'docente_id' => 'required|string',
            'fecha_cita' => 'required|string',
            'modalidad'  => 'required|string|in:Presencial,Virtual',
            'motivo'     => 'nullable|string|max:1000',
        ]);

        $uid = auth()->id() ?? $request->user()?->_id ?? null;
        if (!$uid) return response()->json(['message' => 'Usuario no autenticado'], 401);

        $data['alumno_id']     = new MongoObjectId($uid);
        $data['estado']        = 'Pendiente';
        $data['observaciones'] = null;

        try {
            $cita = Cita::create($data);

            // <-- importante: cargar relaciones antes de devolver el resource
            $cita->load(['docente.persona', 'alumno.persona']);

            return new CitaResource($cita);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'No se pudo registrar la cita',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    
}

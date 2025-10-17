<?php

namespace App\Http\Controllers\Api;

use App\Models\Cita;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\CitaRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CitaResource;
use App\Events\CitaEstadoCambiado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId as MongoObjectId;

class CitaController extends Controller
{
    /**
     * GET /citas (paginado)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);

        $citas = Cita::with(['alumno','docente'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'registros' => \App\Http\Resources\CitaResource::collection($citas)->resolve(),
            'enlaces'   => [
                'primero'   => $citas->url(1),
                'ultimo'    => $citas->url($citas->lastPage()),
                'anterior'  => $citas->previousPageUrl(),
                'siguiente' => $citas->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /citas/{cita}
     */
    public function show(Cita $cita): JsonResponse
    {
        return response()->json([
            'cita' => new CitaResource($cita),
        ], 200);
    }

    /**
     * PUT /citas/{cita}
     * Actualiza la cita (lo hace PROFESOR o ADMIN) y
     * notifica SOLO al ALUMNO del nuevo estado.
     */
    public function update(CitaRequest $request, Cita $cita): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['estado'])) {
            $data['estado'] = ucfirst(strtolower($data['estado']));
        }

        $cita->update($data);

        try {
            $this->notificarActualizacion($cita);
        } catch (\Throwable $e) {
            Log::error('Broadcast cita.update falló', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json([
            'mensaje' => 'Cita actualizada correctamente.',
            'cita'    => new CitaResource($cita),
        ], 200);
    }

    /**
     * DELETE /citas/{cita}
     */
    public function destroy(Cita $cita): JsonResponse
    {
        $cita->delete();

        return response()->json([
            'mensaje' => 'Cita eliminada correctamente.',
        ], 200);
    }

    /**
     * PATCH /citas/{id}/estado  (opcional, si mantienes esta ruta separada)
     * Cambia solo el estado y notifica al alumno.
     */
    public function updateEstado(string $id): JsonResponse
    {
        $cita = Cita::findOrFail($id);
        $nuevo = ucfirst(strtolower(request('estado', 'Pendiente')));
        $cita->estado = $nuevo;
        $cita->save();

        try {
            $this->notificarActualizacion($cita);
        } catch (\Throwable $e) {
            Log::error('Broadcast cita.updateEstado falló', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json([
            'ok'   => true,
            'cita' => new CitaResource($cita)
        ], 200);
    }

    /* ===================== Helpers privados ===================== */

    /**
     * Notifica creación:
     * - Alumno: registrada (Pendiente)
     * - Profesor: solicitud entrante
     * - Admins: igual que profesor
     */
    private function notificarCreacion(Cita $cita): void
    {
        $citaId    = (string)($cita->_id ?? $cita->id);
        $alumnoId  = (string)$cita->alumno_id;
        $docenteId = (string)$cita->docente_id;
        $fechaIso  = $this->toIso($cita->fecha_cita);

        $alumno  = User::find($alumnoId);
        $docente = User::find($docenteId);

        $alumnoNombre  = $alumno->name  ?? $alumno->nombre  ?? 'Alumno';
        $docenteNombre = $docente->name ?? $docente->nombre ?? 'Profesor';

        // 1) Alumno: “cita registrada”
        event(new CitaEstadoCambiado($alumnoId, [
            'cita_id'        => $citaId,
            'estado'         => (string)$cita->estado,
            'fecha_cita'     => $fechaIso,
            'mensaje'        => 'Tu cita quedó registrada y está en estado Pendiente.',
            'docente_nombre' => $docenteNombre,
            'tipo'           => 'creacion',
        ]));

        // 2) Profesor: solicitud entrante
        event(new CitaEstadoCambiado($docenteId, [
            'cita_id'       => $citaId,
            'estado'        => (string)$cita->estado,
            'fecha_cita'    => $fechaIso,
            'mensaje'       => "El alumno {$alumnoNombre} solicita una cita contigo.",
            'alumno_nombre' => $alumnoNombre,
            'tipo'          => 'solicitud',
        ]));

        // 3) Admins: igual que profesor
        $admins = User::where('rol', 'admin')->pluck('_id')->all();
        foreach ($admins as $adminId) {
            event(new CitaEstadoCambiado((string)$adminId, [
                'cita_id'       => $citaId,
                'estado'        => (string)$cita->estado,
                'fecha_cita'    => $fechaIso,
                'mensaje'       => "El alumno {$alumnoNombre} solicita una cita (vista admin).",
                'alumno_nombre' => $alumnoNombre,
                'tipo'          => 'solicitud',
            ]));
        }
    }

    /**
     * Notifica actualización (quien actualiza suele ser profesor/admin):
     * - Alumno: recibe el nuevo estado y el nombre de quien hizo el cambio.
     */
    private function notificarActualizacion(Cita $cita): void
    {
        $citaId   = (string)($cita->_id ?? $cita->id);
        $alumnoId = (string)$cita->alumno_id;
        $fechaIso = $this->toIso($cita->fecha_cita);

        $actor = Auth::user();
        $actorNombre = $actor->name ?? $actor->nombre ?? 'Profesor';

        $estado = (string)$cita->estado;
        $map = [
            'Aceptada'   => 'Tu cita fue Aceptada.',
            'Rechazada'  => 'Tu cita fue Rechazada.',
            'Finalizada' => 'Tu cita quedó Finalizada.',
            'Pendiente'  => 'Tu cita está Pendiente.',
        ];
        $mensaje = $map[$estado] ?? ('Tu cita cambió a '.$estado.'.');

        event(new CitaEstadoCambiado($alumnoId, [
            'cita_id'        => $citaId,
            'estado'         => $estado,
            'fecha_cita'     => $fechaIso,
            'docente_nombre' => $actorNombre,
            'mensaje'        => $mensaje,
            'tipo'           => 'actualizacion',
        ]));
    }

    /**
     * Formatea a ISO-8601:
     * - Carbon: toISOString()
     * - DateTimeInterface: format('c')
     * - string: lo devuelve tal cual
     */
    private function toIso($fecha): ?string
    {
        if (!$fecha) return null;
        if (is_string($fecha)) return $fecha;
        if (method_exists($fecha, 'toISOString')) return $fecha->toISOString();
        if ($fecha instanceof \DateTimeInterface) return $fecha->format('c');
        return (string)$fecha;
    }
}

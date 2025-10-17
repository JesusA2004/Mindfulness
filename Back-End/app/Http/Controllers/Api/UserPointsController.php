<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserPointsController extends Controller
{
    /**
     * Abona puntos al usuario.
     * JSON esperado: { "puntos": 10 }
     */
    public function earn(string $id, Request $request)
    {
        $data = $request->validate(
            [
                'puntos' => ['required', 'integer', 'min:1'],
            ],
            [
                'puntos.required' => 'Debes indicar la cantidad de puntos a abonar.',
                'puntos.integer'  => 'La cantidad de puntos debe ser un número entero.',
                'puntos.min'      => 'La cantidad de puntos debe ser al menos 1. No se permiten números negativos ni cero.',
            ],
            [
                'puntos' => 'puntos',
            ]
        );

        $user = User::findOrFail($id);
        // (Opcional) policy: $this->authorize('update', $user);

        // Usa el helper del modelo (internamente puede usar $inc)
        $user->earnPoints((int) $data['puntos']);

        return response()->json([
            'message'      => 'Puntos abonados correctamente.',
            'puntosCanjeo' => (int) $user->refresh()->puntosCanjeo,
        ]);
    }

    /**
     * Canjea (resta) puntos al usuario, validando que tenga saldo suficiente.
     * JSON esperado: { "puntos": 10 }
     */
    public function redeem(string $id, Request $request)
    {
        $data = $request->validate(
            [
                'puntos' => ['required', 'integer', 'min:1'],
            ],
            [
                'puntos.required' => 'Debes indicar la cantidad de puntos a canjear.',
                'puntos.integer'  => 'La cantidad de puntos debe ser un número entero.',
                'puntos.min'      => 'La cantidad de puntos debe ser al menos 1. No se permiten números negativos ni cero.',
            ],
            [
                'puntos' => 'puntos',
            ]
        );

        $puntos = (int) $data['puntos'];

        // Carga usuario (para mensajes y ver saldo)
        $user = User::findOrFail($id);
        // (Opcional) policy: $this->authorize('update', $user);

        // 1) Validación previa de saldo (mensaje claro)
        $saldo = (int) ($user->puntosCanjeo ?? 0);
        if ($puntos > $saldo) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors'  => [
                    'puntos' => [
                        "No cuentas con puntos suficientes para canjear. Tu saldo actual es de {$saldo} puntos y solicitaste {$puntos}."
                    ],
                ],
            ], 422);
        }

        // 2) Decremento ATÓMICO condicionado para evitar condiciones de carrera:
        $modificados = User::where('_id', $user->_id)
            ->where('puntosCanjeo', '>=', $puntos)
            ->update(['$inc' => ['puntosCanjeo' => -$puntos]]);

        // Si no se modificó, otra operación pudo cambiar el saldo entre lectura y update
        if ($modificados === 0) {
            $saldoActual = (int) (User::find($user->_id)->puntosCanjeo ?? 0);
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors'  => [
                    'puntos' => [
                        "No fue posible canjear los puntos porque tu saldo actual es insuficiente. Saldo actual: {$saldoActual}, solicitado: {$puntos}."
                    ],
                ],
            ], 422);
        }

        // 3) Respuesta OK
        $user->refresh();

        return response()->json([
            'message'      => 'Puntos canjeados correctamente.',
            'puntosCanjeo' => (int) $user->puntosCanjeo,
        ]);
    }

    /**
     * Obtiene los puntos actuales del usuario (sin modificar nada).
     * GET /api/users/{id}/points
     */
    public function getPoints(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'message'      => 'Consulta exitosa. Puntos actuales del usuario obtenidos correctamente.',
            'puntosCanjeo' => (int) ($user->puntosCanjeo ?? 0),
        ]);
    }
}

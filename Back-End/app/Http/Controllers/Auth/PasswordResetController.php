<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetLinkMail;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /** POST /api/password/forgot  { email } */
    public function forgot(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
        ]);

        $user = User::where('email', strtolower($data['email']))->first();
        // Para no filtrar existencia, responde 200 siempre; pero si quieres avisar, quita este early-return silencioso
        if (!$user) {
            return response()->json(['message' => 'Si el correo existe, se enviará un enlace.'], 200);
        }

        // Generar token y guardar hash (no guardes el token plano)
        $tokenPlain  = Str::random(64);
        $tokenHash   = Hash::make($tokenPlain);
        $expiresAt   = Carbon::now()->addMinutes(60);

        // Invalida tokens previos activos del mismo email (opcional, por seguridad)
        PasswordReset::where('email', $user->email)
            ->whereNull('used_at')
            ->delete();

        PasswordReset::create([
            'email'      => $user->email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip'         => $request->ip(),
            'ua'         => substr((string)$request->header('User-Agent'), 0, 512),
        ]);

        // Construir URL del front
        $front = rtrim(config('app.frontend_url') ?? config('app.url'), '/');
        $resetUrl = $front . '/reset-password?token=' . $tokenPlain . '&email=' . urlencode($user->email);

        Mail::to($user->email)->send(new PasswordResetLinkMail($user->name ?? 'Usuario', $user->email, $resetUrl));

        return response()->json(['message' => 'Se ha enviado el enlace para restablecer la contraseña (si el correo existe).'], 200);
    }

    /** POST /api/password/reset  { email, token, password, password_confirmation } */
    public function reset(Request $request)
    {
        $data = $request->validate([
            'email'                 => ['required','email'],
            'token'                 => ['required','string'],
            'password'              => ['required','string','min:8','confirmed'],
        ]);

        $reset = PasswordReset::where('email', strtolower($data['email']))
            ->whereNull('used_at')
            ->orderByDesc('_id')
            ->first();

        if (!$reset) {
            return response()->json(['message' => 'Token inválido o ya usado.'], 422);
        }

        if (Carbon::now()->greaterThan($reset->expires_at)) {
            return response()->json(['message' => 'El enlace ha expirado.'], 422);
        }

        if (!Hash::check($data['token'], $reset->token_hash)) {
            return response()->json(['message' => 'Token inválido.'], 422);
        }

        $user = User::where('email', strtolower($data['email']))->first();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        // Actualiza password (tu mutator ya hace bcrypt si no empieza con $2y$)
        $user->password = $data['password'];
        // Por seguridad, invalida cualquier JTI vigente si lo usas
        $user->current_jti = null;
        $user->save();

        // Marca token como usado
        $reset->used_at = Carbon::now();
        $reset->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente.'], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Mail\UserPasswordMail;

class UserController extends Controller
{
    /**
     * GET /users
     */
    public function index(Request $request)
    {
        // Eager load para que el front tenga persona de una vez
        $users = User::with('persona')->paginate();
        return UserResource::collection($users);
    }

    /**
     * POST /users
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Si no viene password, generar una segura
        $plain = $data['password'] ?? Str::password(14);
        $data['password'] = Hash::make($plain);

        // Crear usuario
        $user = User::create($data);

        // Enviar email si se solicita
        if ($request->boolean('notify_email') && !empty($user->email)) {
            Mail::to($user->email)->send(
                new UserPasswordMail($user->name, $user->email, $plain)
            );
        }

        // Devolver con persona
        $user->load('persona');

        return response()->json(new UserResource($user));
    }

    /**
     * GET /users/{user}
     */
    public function show(User $user): JsonResponse
    {
        $user->load('persona');
        return response()->json(new UserResource($user));
    }

    /**
     * PUT/PATCH /users/{user}
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        $plain = null;
        if (!empty($data['password'])) {
            $plain = $data['password'];              // conservar para email
            $data['password'] = Hash::make($plain);  // guardar hash
        } else {
            unset($data['password']); // no tocar password si no viene
        }

        $user->update($data);

        // Si se cambió la password y piden notificar, enviar email
        if ($plain && $request->boolean('notify_email') && !empty($user->email)) {
            Mail::to($user->email)->send(
                new UserPasswordMail($user->name, $user->email, $plain)
            );
        }

        $user->load('persona');

        return response()->json(new UserResource($user));
    }

    /**
     * DELETE /users/{user}
     */
    public function destroy(User $user): Response
    {
        $user->delete();
        return response()->noContent();
    }
}

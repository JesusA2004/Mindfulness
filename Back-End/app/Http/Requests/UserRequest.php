<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Soporta binding por modelo o id directo
        $routeUser = $this->route('user');
        $userId = is_object($routeUser) && method_exists($routeUser, 'getKey')
            ? $routeUser->getKey()
            : $routeUser;

        return [
            'name'          => ['required', 'string', 'max:255'],

            'matricula'     => [
                'required', 'string', 'max:50',
                Rule::unique('users', 'matricula')->ignore($userId, '_id'),
            ],

            'email'         => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId, '_id'),
            ],

            // La contraseña es OPCIONAL: si no llega, se generará en el controlador
            'password'      => ['nullable', 'string', 'min:8'],

            'rol'           => ['required', 'string', Rule::in(['estudiante','profesor','admin'])],

            'estatus'       => ['nullable', 'string', Rule::in(['activo','bajaSistema','bajaTemporal'])],

            'urlFotoPerfil' => ['nullable', 'url'],

            // bandera para que el backend envíe o no el correo con la contraseña
            'notify_email'  => ['sometimes', 'boolean'],

            // id de la persona (usa size:24 si tu _id es ObjectId; ajusta si usas UUID/auto-increment)
            'persona_id'    => ['required', 'string', 'size:24'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'El nombre es obligatorio.',
            'name.string'          => 'El nombre debe ser texto.',
            'name.max'             => 'El nombre no debe exceder 255 caracteres.',

            'matricula.required'   => 'La matrícula es obligatoria.',
            'matricula.string'     => 'La matrícula debe ser texto.',
            'matricula.max'        => 'La matrícula no debe exceder 50 caracteres.',
            'matricula.unique'     => 'La matrícula ya está registrada.',

            'email.required'       => 'El correo es obligatorio.',
            'email.email'          => 'El correo debe ser válido.',
            'email.max'            => 'El correo no debe exceder 255 caracteres.',
            'email.unique'         => 'El correo ya está registrado.',

            'password.string'      => 'La contraseña debe ser texto.',
            'password.min'         => 'La contraseña debe tener al menos 8 caracteres.',

            'rol.required'         => 'El rol es obligatorio.',
            'rol.in'               => 'El rol debe ser: estudiante, profesor o admin.',

            'estatus.in'           => 'El estatus debe ser: activo, bajaSistema o bajaTemporal.',

            'urlFotoPerfil.url'    => 'La URL de la foto de perfil no es válida.',

            'notify_email.boolean' => 'El campo de notificación por correo es inválido.',

            'persona_id.required'  => 'El ID de la persona es obligatorio.',
            'persona_id.string'    => 'El ID de la persona debe ser una cadena.',
            'persona_id.size'      => 'El ID de la persona debe tener exactamente 24 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'persona_id' => 'ID de la persona',
        ];
    }
}

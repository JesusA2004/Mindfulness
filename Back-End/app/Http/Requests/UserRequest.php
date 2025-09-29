<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Institucion;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Soporta tanto route-model binding como id string
        $routeUser = $this->route('user');
        $userId = is_object($routeUser) && method_exists($routeUser, 'getKey')
            ? $routeUser->getKey()
            : $routeUser;

        return [
            'name'            => ['required', 'string', 'max:255'],

            'matricula'       => [
                'required', 'string', 'max:50',
                Rule::unique('users', 'matricula')->ignore($userId, '_id'),
            ],

            'email'           => [
                'required', 'email',
                Rule::unique('users', 'email')->ignore($userId, '_id'),
            ],

            'password'        => $this->isMethod('POST')
                ? ['required', 'string', 'min:6', 'confirmed']
                : ['nullable', 'string', 'min:6', 'confirmed'],

            // Usa los valores en minúsculas que definiste
            'rol'             => ['required', 'string', Rule::in(['estudiante','profesor','admin'])],

            'estatus'         => ['nullable', 'string', Rule::in(['activo','bajaSistema','bajaTemporal'])],

            'urlFotoPerfil'   => ['nullable', 'url'],

            'persona_id'      => ['required', 'string', 'size:24'],

            // NUEVO: obligatorio y debe existir en instituciones
            'institucion_id'  => [
                'required', 'string', 'size:24',
                function ($attribute, $value, $fail) {
                    if (!Institucion::where('_id', $value)->exists()) {
                        $fail('La institución especificada no existe.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'El nombre es obligatorio.',
            'name.string'             => 'El nombre debe ser una cadena de texto.',
            'name.max'                => 'El nombre no debe exceder los 255 caracteres.',

            'matricula.required'      => 'La matrícula es obligatoria.',
            'matricula.string'        => 'La matrícula debe ser una cadena de texto.',
            'matricula.max'           => 'La matrícula no debe exceder los 50 caracteres.',
            'matricula.unique'        => 'La matrícula ya está registrada.',

            'email.required'          => 'El correo electrónico es obligatorio.',
            'email.email'             => 'El correo electrónico no es válido.',
            'email.unique'            => 'El correo electrónico ya está registrado.',

            'password.required'       => 'La contraseña es obligatoria.',
            'password.string'         => 'La contraseña debe ser una cadena de texto.',
            'password.min'            => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'      => 'La confirmación de la contraseña no coincide.',

            'rol.required'            => 'El rol es obligatorio.',
            'rol.string'              => 'El rol debe ser una cadena de texto.',
            'rol.in'                  => 'El rol debe ser uno de los siguientes: estudiante, profesor o admin.',

            'estatus.string'          => 'El estatus debe ser una cadena de texto.',
            'estatus.in'              => 'El estatus debe ser: activo, bajaSistema o bajaTemporal.',

            'urlFotoPerfil.url'       => 'La URL de la foto de perfil no es válida.',

            'persona_id.required'     => 'El ID de la persona es obligatorio.',
            'persona_id.string'       => 'El ID de la persona debe ser una cadena.',
            'persona_id.size'         => 'El ID de la persona debe tener exactamente 24 caracteres.',

            'institucion_id.required' => 'El ID de la institución es obligatorio.',
            'institucion_id.string'   => 'El ID de la institución debe ser una cadena.',
            'institucion_id.size'     => 'El ID de la institución debe tener exactamente 24 caracteres.',
            // El mensaje “no existe” se maneja en la closure del rule.
        ];
    }
}

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

            // >>> puntosCanjeo: entero y no negativo
            'puntosCanjeo'    => ['sometimes', 'integer', 'min:0'],

            'persona_id'      => ['required', 'string', 'size:24'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'El nombre es obligatorio. Capture el nombre del usuario.',
            'name.string'             => 'El nombre debe ser texto.',
            'name.max'                => 'El nombre no debe exceder 255 caracteres.',

            'matricula.required'      => 'La matrícula es obligatoria. Ingrésela tal como aparece en su registro.',
            'matricula.string'        => 'La matrícula debe ser texto.',
            'matricula.max'           => 'La matrícula no debe exceder 50 caracteres.',
            'matricula.unique'        => 'La matrícula ya está registrada. Use una diferente.',

            'email.required'          => 'El correo electrónico es obligatorio.',
            'email.email'             => 'Ingrese un correo electrónico válido (ejemplo@dominio.com).',
            'email.unique'            => 'El correo electrónico ya está registrado. Use uno diferente.',

            'password.required'       => 'La contraseña es obligatoria.',
            'password.string'         => 'La contraseña debe ser texto.',
            'password.min'            => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'      => 'La confirmación de la contraseña no coincide. Vuelva a escribirla.',

            'rol.required'            => 'El rol es obligatorio. Seleccione estudiante, profesor o admin.',
            'rol.string'              => 'El rol debe ser texto.',
            'rol.in'                  => 'El rol debe ser uno de: estudiante, profesor o admin.',

            'estatus.string'          => 'El estatus debe ser texto.',
            'estatus.in'              => 'El estatus debe ser: activo, bajaSistema o bajaTemporal.',

            'urlFotoPerfil.url'       => 'La URL de la foto de perfil no es válida. Ingrese una dirección web correcta.',

            // >>> mensajes claros para puntosCanjeo
            'puntosCanjeo.integer'    => 'Los puntos de canje deben ser un número entero.',
            'puntosCanjeo.min'        => 'No se permiten números negativos en los puntos de canje. Ingrese un valor mayor o igual a 0.',

            'persona_id.required'     => 'El ID de la persona es obligatorio.',
            'persona_id.string'       => 'El ID de la persona debe ser una cadena.',
            'persona_id.size'         => 'El ID de la persona debe tener exactamente 24 caracteres.',
        ];
    }

    public function attributes(): array
    {
        // Para que los mensajes se lean más naturales
        return [
            'puntosCanjeo'   => 'puntos de canje',
            'persona_id'     => 'ID de la persona',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'matricula'     => 'required|string|max:50|unique:users,matricula,' . $this->route('user'),
            'email'         => 'required|email|unique:users,email,' . $this->route('user'),
            'password'      => $this->isMethod('POST')
                                    ? 'required|string|min:6|confirmed'
                                    : 'nullable|string|min:6|confirmed',
            'rol'           => 'required|string|in:estudiante,profesor,admin',
            'estatus'       => 'nullable|string|in:activo,bajaSistema,bajaTemporal',
            'urlFotoPerfil' => 'nullable|url',
            'persona_id'    => 'required|string|size:24',
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
        ];
    }
}

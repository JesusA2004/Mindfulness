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
            'name'         => 'required|string|max:255',
            'matricula'    => 'required|string|max:50|unique:users,matricula,' . $this->route('user'),
            'email'        => 'required|email|unique:users,email,' . $this->route('user'),
            'password'     => $this->isMethod('POST')
                                ? 'required|string|min:6|confirmed'
                                : 'nullable|string|min:6|confirmed',
            'rol'          => 'required|string|in:Estudiante,Profesor,Administrador',
            'urlFotoPerfil'=> 'nullable|url',
            'persona_id'   => 'required|string|size:24', // Validación básica para un ObjectId
        ];
    }

}

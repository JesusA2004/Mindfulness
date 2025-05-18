<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BitacoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // En caso de update, captura el id de la bitácora
        $bitacoraId = $this->route('bitacora');

        return [
            // Título obligatorio, texto y longitud máxima
            'titulo'       => 'required|string|max:150',
            // Descripción opcional
            'descripcion'  => 'nullable|string',
            // Fecha obligatoria, con formato ISO YYYY-MM-DD
            'fecha'        => 'required|date_format:Y-m-d',
            // El alumno debe existir en la colección users
            'alumno_id'    => 'required|exists:users,_id',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required'      => 'El título de la bitácora es obligatorio.',
            'titulo.max'           => 'El título no puede exceder 150 caracteres.',
            'descripcion.string'   => 'La descripción debe ser un texto válido.',
            'fecha.required'       => 'La fecha es obligatoria.',
            'fecha.date_format'    => 'La fecha debe tener el formato YYYY-MM-DD.',
            'alumno_id.required'   => 'Debe especificar el alumno al que pertenece la bitácora.',
            'alumno_id.exists'     => 'El alumno seleccionado no está registrado.',
        ];
    }
}

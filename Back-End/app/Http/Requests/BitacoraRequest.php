<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BitacoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Ignora cualquier intento del cliente de enviar alumno_id
        if ($this->has('alumno_id')) {
            $this->request->remove('alumno_id');
        }
    }

    public function rules(): array
    {
        return [
            'titulo'      => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'fecha'       => 'required|date_format:Y-m-d',
            // ❌ NO validar alumno_id; lo asigna el controlador con auth()->id()
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required'   => 'El título de la bitácora es obligatorio.',
            'titulo.max'        => 'El título no puede exceder 150 caracteres.',
            'descripcion.string'=> 'La descripción debe ser un texto válido.',
            'fecha.required'    => 'La fecha es obligatoria.',
            'fecha.date_format' => 'La fecha debe tener el formato YYYY-MM-DD.',
        ];
    }
}

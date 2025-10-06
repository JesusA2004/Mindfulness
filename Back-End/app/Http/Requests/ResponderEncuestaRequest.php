<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderEncuestaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'usuario_id' => 'required|string',
            'respuestas' => 'required|array|min:1',
            'respuestas.*.pregunta_id' => 'required|string',
            // "respuesta" puede ser string o array según tipo; validaremos en el controlador
            'respuestas.*.respuesta'   => 'required',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderTestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'usuario_id'              => 'required|string',
            'respuestas'              => 'required|array|min:1',
            'respuestas.*.pregunta_id'=> 'required|string',
            'respuestas.*.respuesta'  => 'required', // string o array, se valida en el controlador según tipo
        ];
    }
}

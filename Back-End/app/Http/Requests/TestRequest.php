<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Para update, excluir el propio registro:
        $testId = $this->route('test'); // asumiendo ruta /tests/{test}

        return [
            // Datos principales
            'nombre'             => "required|string|max:150|unique:tests,nombre,{$testId}",
            'descripcion'        => 'nullable|string',
            'duracion_estimada'  => 'required|integer|min:1',
            'fechaAplicacion'    => 'nullable|date_format:Y-m-d',

            // Cuestionario (opcional)
            'cuestionario'                 => 'sometimes|array|min:1',
            'cuestionario.*.pregunta'      => 'required_with:cuestionario|string|max:255',
            'cuestionario.*.respuestas'    => 'required_with:cuestionario|array|min:1',
            'cuestionario.*.respuestas.*'  => 'string|max:200',            // cada respuesta
            'cuestionario.*.idUsuario'     => 'required_with:cuestionario|exists:users,_id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique'                 => 'Ya existe un test con este nombre.',
            'fechaAplicacion.date_format'   => 'La fecha de aplicación debe tener el formato YYYY-MM-DD.',
            'cuestionario.array'            => 'El cuestionario debe ser un arreglo de preguntas.',
            'cuestionario.min'              => 'Debe incluir al menos una pregunta.',
            'cuestionario.*.pregunta.max'   => 'Cada pregunta no debe exceder 255 caracteres.',
            'cuestionario.*.respuestas.array'=> 'Las respuestas deben enviarse como un arreglo.',
            'cuestionario.*.respuestas.min'  => 'Cada pregunta debe tener al menos una respuesta.',
            'cuestionario.*.respuestas.*.string' => 'Cada respuesta debe ser texto de hasta 200 caracteres.',
            'cuestionario.*.idUsuario.exists'    => 'El usuario que responde no existe.',
        ];
    }
}

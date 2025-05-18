<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecompensaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Para update, excluye la recompensa actual si usas unique sobre nombre
        $recompensaId = $this->route('recompensa');

        return [
            // Campos principales
            'nombre'            => "required|string|max:150|unique:recompensas,nombre,{$recompensaId}",
            'descripcion'       => 'nullable|string',
            'puntos_necesarios' => 'required|integer|min:0',
            'stock'             => 'nullable|integer|min:0',

            // Validación del array de canjeos (opcional)
            'canjeo'                  => 'sometimes|array|min:1',
            'canjeo.*.usuario_id'     => 'required_with:canjeo|exists:users,_id',
            'canjeo.*.fechaCanjeo'    => 'required_with:canjeo|date_format:Y-m-d',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique'                => 'Ya existe una recompensa con ese nombre.',
            'canjeo.array'                 => 'El canjeo debe ser un arreglo de registros.',
            'canjeo.min'                   => 'Debe indicar al menos un registro de canjeo.',
            'canjeo.*.usuario_id.exists'   => 'El usuario que canjea no está registrado.',
            'canjeo.*.fechaCanjeo.date_format' => 'La fecha de canjeo debe tener el formato YYYY-MM-DD.',
        ];
    }
}


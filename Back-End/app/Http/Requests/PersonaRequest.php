<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Persona;

class PersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación base.
     * NOTA: Para carrera / cuatrimestre / grupo aceptamos string o array
     * con una regla de cierre (closure) que valida ambos casos.
     */
    public function rules(): array
    {
        return [
            'nombre'           => 'required|string|max:100',
            'apellidoPaterno'  => 'required|string|max:100',
            'apellidoMaterno'  => 'nullable|string|max:100',
            'fechaNacimiento'  => 'required|date_format:Y-m-d',
            'telefono'         => 'nullable|string|max:20',
            'sexo'             => 'nullable|string|in:Masculino,Femenino,Otro,Prefiero no decir',

            // matricula: unicidad en Mongo se valida en withValidator()
            'matricula'        => 'required|string|max:50',

            // String O array (se valida con closures)
            'carrera'          => ['nullable', $this->stringOrStringArrayRule(100)],
            'cuatrimestre'     => ['nullable', $this->stringOrStringArrayRule(20)],
            'grupo'            => ['nullable', $this->stringOrStringArrayRule(20)],
        ];
    }

    /**
     * Normaliza inputs ANTES de validar:
     * - trim de strings
     * - convierte "a, b, c" en ['a','b','c'] si corresponde
     * - permite que string simple se quede como string (estudiante)
     */
    protected function prepareForValidation(): void
    {
        $in = $this->all();

        // Helper para trim seguro
        $trimStr = static function ($v) {
            return is_string($v) ? trim($v) : $v;
        };

        // Campos simples (trim)
        foreach (['nombre','apellidoPaterno','apellidoMaterno','telefono','sexo','matricula'] as $k) {
            if (array_key_exists($k, $in)) {
                $in[$k] = $trimStr($in[$k]);
                if ($in[$k] === '') $in[$k] = null;
            }
        }

        // fechaNacimiento ya viene en Y-m-d desde el front

        // Para carrera / cuatrimestre / grupo:
        // - Si viene string con comas → array
        // - Si viene string simple → lo dejamos como string
        // - Si viene array → limpiamos (trim + quitamos vacíos)
        foreach (['carrera' => 100, 'cuatrimestre' => 20, 'grupo' => 20] as $field => $max) {
            if (!array_key_exists($field, $in)) continue;

            $val = $in[$field];

            // Si es string y contiene comas, lo partimos a array
            if (is_string($val) && strpos($val, ',') !== false) {
                $parts = array_map('trim', explode(',', $val));
                $parts = array_values(array_filter($parts, fn($v) => $v !== '' && $v !== null));
                $in[$field] = $parts ?: null;
                continue;
            }

            // Si es array, limpiamos
            if (is_array($val)) {
                $val = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $val);
                $val = array_values(array_filter($val, fn($v) => is_string($v) ? $v !== '' : $v !== null));
                $in[$field] = $val ?: null;
                continue;
            }

            // Si es string vacío → null
            if (is_string($val) && trim($val) === '') {
                $in[$field] = null;
            }
        }

        // Sexo: normaliza capitalización si viene en otra forma
        if (!empty($in['sexo']) && is_string($in['sexo'])) {
            $map = [
                'masculino' => 'Masculino',
                'femenino' => 'Femenino',
                'otro' => 'Otro',
                'prefiero no decir' => 'Prefiero no decir',
            ];
            $key = mb_strtolower(trim($in['sexo']));
            if (isset($map[$key])) $in['sexo'] = $map[$key];
        }

        $this->replace($in);
    }

    /**
     * Validaciones adicionales DESPUÉS de las reglas:
     * - Unicidad de 'matricula' en Mongo (ignorando el propio _id si es update)
     * - Longitud de cada item si los campos son arrays (ya se valida en el closure,
     *   esto es redundante/defensivo)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Unicidad de matrícula (Mongo)
            $matricula = $this->input('matricula');
            if ($matricula) {
                // Determinar el _id actual (si hay model binding, viene objeto)
                $routeParam = $this->route('persona');
                $currentId  = is_object($routeParam) ? ($routeParam->_id ?? $routeParam->id ?? null) : $routeParam;

                $exists = Persona::where('matricula', $matricula)
                    ->when($currentId, fn($q) => $q->where('_id', '!=', $currentId))
                    ->exists();

                if ($exists) {
                    $v->errors()->add('matricula', 'La matrícula ya está registrada.');
                }
            }
        });
    }

    /**
     * Después de validar, pulimos el payload:
     * - si arrays quedaron vacíos → null
     */
    protected function passedValidation(): void
    {
        $out = $this->validated();

        foreach (['carrera','cuatrimestre','grupo'] as $k) {
            if (array_key_exists($k, $out)) {
                if (is_array($out[$k]) && count($out[$k]) === 0) {
                    $out[$k] = null;
                }
            }
        }

        // Sobrescribe inputs limpios para el controlador
        $this->replace($out);
    }

    /**
     * Regla reusable: acepta string O array de strings con longitud máxima $max.
     */
    protected function stringOrStringArrayRule(int $max): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($max) {
            // string simple
            if (is_string($value)) {
                if (mb_strlen($value) > $max) {
                    $fail("El campo :attribute no debe exceder de {$max} caracteres.");
                }
                return;
            }

            // array de strings
            if (is_array($value)) {
                foreach ($value as $idx => $item) {
                    if (!is_string($item)) {
                        $fail("Cada elemento de :attribute debe ser texto.");
                        return;
                    }
                    if (mb_strlen($item) > $max) {
                        $fail("El elemento #".($idx+1)." de :attribute excede {$max} caracteres.");
                        return;
                    }
                }
                return;
            }

            // otro tipo → error
            $fail('El campo :attribute debe ser texto o una lista de textos.');
        };
    }

    /**
     * Etiquetas legibles para mensajes.
     */
    public function attributes(): array
    {
        return [
            'nombre'           => 'nombre',
            'apellidoPaterno'  => 'apellido paterno',
            'apellidoMaterno'  => 'apellido materno',
            'fechaNacimiento'  => 'fecha de nacimiento',
            'telefono'         => 'teléfono',
            'sexo'             => 'sexo',
            'matricula'        => 'matrícula',
            'carrera'          => 'carrera(s)',
            'cuatrimestre'     => 'cuatrimestre(s)',
            'grupo'            => 'grupo(s)',
        ];
    }

    /**
     * Mensajes personalizados clave.
     */
    public function messages(): array
    {
        return [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellidoPaterno.required'  => 'El apellido paterno es obligatorio.',
            'fechaNacimiento.required'  => 'La fecha de nacimiento es obligatoria.',
            'fechaNacimiento.date_format' => 'La fecha de nacimiento debe tener el formato YYYY-MM-DD.',
            'matricula.required'        => 'La matrícula es obligatoria.',
            'matricula.max'             => 'La matrícula no debe exceder de :max caracteres.',
            'sexo.in'                   => 'El sexo seleccionado no es válido.',
        ];
    }
}

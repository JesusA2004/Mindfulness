<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PersonaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'           => 'required|string|max:100',
            'apellidoPaterno'  => 'required|string|max:100',
            'apellidoMaterno'  => 'nullable|string|max:100',
            'fechaNacimiento'  => 'required|date_format:Y-m-d',
            'telefono'         => 'nullable|string|max:20',
            'sexo'             => 'nullable|string|in:Masculino,Femenino,Otro,Prefiero no decir',
            'matricula'        => 'required|string|max:50',

            // NUEVO: "cohorte" puede ser string o array de strings tipo "ITI 10 A"
            'cohorte'          => ['nullable', $this->cohorteStringOrArrayRule()],
        ];
    }

    /**
     * Normalización:
     * - Back-compat: si llegan 'carrera', 'cuatrimestre', 'grupo', construye "cohorte".
     * - Acepta también 'cohorte' ya armado (string o "a, b" -> array).
     */
    protected function prepareForValidation(): void
    {
        $in = $this->all();

        $trim = static fn($v) => is_string($v) ? trim($v) : $v;
        foreach (['nombre','apellidoPaterno','apellidoMaterno','telefono','sexo','matricula'] as $k) {
            if (array_key_exists($k, $in)) {
                $in[$k] = $trim($in[$k]);
                if ($in[$k] === '') $in[$k] = null;
            }
        }

        // Si viene "cohorte" como "a, b" -> array
        if (array_key_exists('cohorte', $in)) {
            $in['cohorte'] = $this->toArrayIfCsv($in['cohorte']);
        } else {
            // Construir "cohorte" desde legacy si existen
            $carrera = $in['carrera'] ?? null;         // puede venir solo para estudiante (front)
            $cuat    = $in['cuatrimestre'] ?? null;
            $grupo   = $in['grupo'] ?? null;

            // profesor podría mandar arrays de cada cosa -> combinamos
            $toArr = function ($v) {
                if (is_array($v)) {
                    $v = array_values(array_filter(array_map(fn($x) => is_string($x) ? trim($x) : $x, $v), fn($x) => $x !== '' && $x !== null));
                    return $v ?: null;
                }
                if (is_string($v)) {
                    $v = trim($v);
                    if ($v === '') return null;
                    if (strpos($v, ',') !== false) {
                        $parts = array_values(array_filter(array_map('trim', explode(',', $v)), fn($x) => $x !== ''));
                        return $parts ?: null;
                    }
                    return [$v];
                }
                return null;
            };

            $arrCar  = $toArr($carrera);
            $arrCuat = $toArr($cuat);
            $arrGrp  = $toArr($grupo);

            $cohortes = null;

            if ($arrCar && $arrCuat && $arrGrp) {
                $tmp = [];
                foreach ($arrCar as $car) {
                    foreach ($arrCuat as $c) {
                        foreach ($arrGrp as $g) {
                            $tmp[] = $this->buildCohorte($car, $c, $g);
                        }
                    }
                }
                $cohortes = $tmp ?: null;
            } elseif ($arrCar && $arrCuat) {
                $tmp = [];
                foreach ($arrCar as $car) foreach ($arrCuat as $c) $tmp[] = $this->buildCohorte($car, $c, '');
                $cohortes = $tmp ?: null;
            } elseif ($arrCar && $arrGrp) {
                $tmp = [];
                foreach ($arrCar as $car) foreach ($arrGrp as $g) $tmp[] = $this->buildCohorte($car, '', $g);
                $cohortes = $tmp ?: null;
            } elseif ($arrCar) {
                $cohortes = $arrCar;
            }

            if (!empty($cohortes)) {
                // Si hay solo 1, se acepta string; si >1, array (profesor)
                $in['cohorte'] = count($cohortes) === 1 ? $cohortes[0] : $cohortes;
            }
        }

        // Sexo: capitalización
        if (!empty($in['sexo']) && is_string($in['sexo'])) {
            $map = [
                'masculino' => 'Masculino',
                'femenino'  => 'Femenino',
                'otro'      => 'Otro',
                'prefiero no decir' => 'Prefiero no decir',
            ];
            $key = mb_strtolower(trim($in['sexo']));
            if (isset($map[$key])) $in['sexo'] = $map[$key];
        }

        $this->replace($in);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $matricula = $this->input('matricula');
            if ($matricula) {
                $routeParam = $this->route('persona');
                $currentId  = is_object($routeParam) ? ($routeParam->_id ?? $routeParam->id ?? null) : $routeParam;

                $exists = Persona::where('matricula', $matricula)
                    ->when($currentId, fn($q) => $q->where('_id', '!=', $currentId))
                    ->exists();

                if ($exists) $v->errors()->add('matricula', 'La matrícula ya está registrada.');
            }
        });
    }

    protected function passedValidation(): void
    {
        $out = $this->validated();
        // no-op; cohorte puede ser string o array
        $this->replace($out);
    }

    /** Reglas reutilizables **/
    protected function cohorteStringOrArrayRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            $ok = function ($s) {
                if (!is_string($s)) return false;
                $s = trim($s);
                if ($s === '') return false;
                // Acepta letras/números/espacios (p.ej. "ITI 10 A", "IA 7 C")
                return (bool) preg_match('/^[\p{L}\p{N} ]+$/u', $s);
            };

            if (is_null($value)) return;

            if (is_string($value)) {
                if (!$ok($value)) $fail('Formato de cohorte inválido. Usa p.ej. "ITI 10 A".');
                return;
            }

            if (is_array($value)) {
                foreach ($value as $idx => $item) {
                    if (!is_string($item) || !$ok($item)) {
                        $fail('Formato de cohorte inválido en el elemento #'.($idx+1).'. Usa p.ej. "ITI 10 A".');
                        return;
                    }
                }
                return;
            }

            $fail('El campo :attribute debe ser texto o lista de textos.');
        };
    }

    /** Helpers **/
    protected function toArrayIfCsv($v): ?array
    {
        if (is_array($v)) {
            $v = array_values(array_filter(array_map(fn($x) => is_string($x) ? trim($x) : $x, $v), fn($x) => $x !== '' && $x !== null));
            return $v ?: null;
        }
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') return null;
            if (strpos($v, ',') !== false) {
                $parts = array_values(array_filter(array_map('trim', explode(',', $v)), fn($x) => $x !== ''));
                return $parts ?: null;
            }
            // string simple -> lo dejaremos como string en passedValidation
            return [$v]; // devolvemos array para validarlo; luego puedes guardarlo como string si tiene 1
        }
        return null;
    }

    protected function buildCohorte($carrera, $cuatrimestre, $grupo): string
    {
        $parts = array_filter([trim((string)$carrera), trim((string)$cuatrimestre), trim((string)$grupo)], fn($x) => $x !== '');
        return implode(' ', $parts); // "ITI 10 A"
    }

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
            'cohorte'          => 'cohorte(s)',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'            => 'El nombre es obligatorio.',
            'apellidoPaterno.required'   => 'El apellido paterno es obligatorio.',
            'fechaNacimiento.required'   => 'La fecha de nacimiento es obligatoria.',
            'fechaNacimiento.date_format'=> 'La fecha de nacimiento debe tener el formato YYYY-MM-DD.',
            'matricula.required'         => 'La matrícula es obligatoria.',
            'matricula.max'              => 'La matrícula no debe exceder de :max caracteres.',
            'sexo.in'                    => 'El sexo seleccionado no es válido.',
        ];
    }
}

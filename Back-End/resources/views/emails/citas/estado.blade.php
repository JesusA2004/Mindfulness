@component('mail::message')
# Estado de cita: {{ $estado }}

Hola **{{ $alumno_nombre ?? 'Alumno' }}**,

Tu cita con **{{ $docente_nombre ?? 'Profesor' }}** cambió a **{{ $estado }}**.

**Fecha/Hora:** {{ $fecha_pretty ?? $fecha_cita }}

@isset($observaciones)
**Observaciones:**  
{{ $observaciones }}
@endisset

@component('mail::panel')
Cambio realizado por: {{ $actor_nombre ?? 'Profesor' }}
@endcomponent

Gracias,  
{{ config('app.name') }}
@endcomponent

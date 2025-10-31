@component('mail::message')
# Cita registrada

Hola **{{ $alumno_nombre ?? 'Alumno' }}**,

Tu cita fue registrada con el estado **{{ $estado ?? 'Pendiente' }}**.

**Docente:** {{ $docente_nombre ?? 'Profesor' }}  
**Fecha/Hora:** {{ $fecha_pretty ?? $fecha_cita }}  
**Modalidad:** {{ $modalidad ?? '—' }}  
**Motivo:** {{ $motivo ?? '—' }}

@component('mail::panel')
Si el docente acepta o rechaza tu solicitud, te llegará otro correo de confirmación.
@endcomponent

Gracias,  
{{ config('app.name') }}
@endcomponent

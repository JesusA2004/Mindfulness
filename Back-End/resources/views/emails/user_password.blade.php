@php
    // URL del login (ajústala a tu front)
    $loginUrl = $loginUrl ?? (config('app.frontend_url') ?: rtrim(config('app.url'), '/').'/login');
    $appName  = config('app.name', 'Mindora');
@endphp

@component('mail::message')
{{-- Encabezado --}}
# ¡Hola {{ $name }}!

Tu cuenta en **{{ $appName }}** ha sido creada o actualizada. Aquí tienes tus credenciales temporales:

@component('mail::panel')
**Usuario:** <a href="mailto:{{ $emailPlain }}">{{ $emailPlain }}</a>  
**Contraseña temporal:** `{{ $passwordPlain }}`
@endcomponent

@component('mail::button', ['url' => $loginUrl])
Ir a {{ $appName }}
@endcomponent

> **Por seguridad:** cambia tu contraseña.

Si no solicitaste este acceso, ignora este mensaje o contáctanos.

Saludos,  
**Equipo de {{ $appName }}**

@slot('subcopy')
Si el botón no funciona, copia y pega este enlace en tu navegador:  
{{ $loginUrl }}
@endslot
@endcomponent

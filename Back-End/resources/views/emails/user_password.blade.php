@component('mail::message')
# Hola {{ $name }}

Tu cuenta en **Mindfulness** ha sido creada/actualizada.

@component('mail::panel')
**Usuario:** {{ $emailPlain }}  
**Contraseña temporal:** {{ $passwordPlain }}
@endcomponent

> Por seguridad, cambia tu contraseña al primer inicio de sesión.

Gracias,<br>
{{ config('app.name') }}
@endcomponent

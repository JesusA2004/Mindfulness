@component('mail::message')
@if(!empty($logoCid))
<p style="text-align:center;margin:0 0 16px;">
  <img src="{{ $logoCid }}" alt="Mindora" style="height:42px;">
</p>
@endif

# ¡Bienvenido(a), {{ $name }}!

Tu cuenta en **Mindora** fue creada o actualizada. Aquí tienes tus credenciales:

@component('mail::panel')
**Usuario:** {{ $emailPlain }}  
**Contraseña:** <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace;">{{ $passwordPlain }}</span>
@endcomponent

> Por seguridad, inicia sesión y **cambia tu contraseña**.

@component('mail::button', ['url' => $loginUrl])
Ir a Mindora
@endcomponent

Si el botón no funciona, copia y pega este enlace en tu navegador:  
{{ $loginUrl }}

Gracias,<br>
**Equipo de Mindora**
@endcomponent

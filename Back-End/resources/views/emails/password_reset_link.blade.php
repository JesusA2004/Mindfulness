@component('mail::message')
@if(!empty($logoCid))
<p style="text-align:center;margin:0 0 16px;">
  <img src="{{ $logoCid }}" alt="Mindora" style="height:42px;">
</p>
@endif

# Restablecer contraseña

Hola, **{{ $name }}** 👋

Recibimos una solicitud para restablecer tu contraseña en **{{ config('app.name') }}**.  
Haz clic en el siguiente botón para continuar. El enlace **expira en 60 minutos**.

@component('mail::button', ['url' => $resetUrl])
Cambiar mi contraseña
@endcomponent

Si el botón no funciona, copia y pega este enlace en tu navegador:  
{{ $resetUrl }}

> Si tú no solicitaste este cambio, puedes ignorar este mensaje.

Gracias,<br>
**Equipo de {{ config('app.name') }}**
@endcomponent

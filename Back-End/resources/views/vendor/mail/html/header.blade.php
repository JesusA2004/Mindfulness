@props(['loginUrl' => null, 'logoCid' => null])

@php
  $brand = trim($slot) !== '' ? trim($slot) : config('app.name', 'Mindora');
  $fallback = asset('images/mail-logo.png');
  $href = $loginUrl
      ?? (config('app.frontend_url') ? rtrim(config('app.frontend_url'), '/') : rtrim(config('app.url'), '/') . '/login');
@endphp

<tr>
  <td class="header" align="center" style="padding:25px 0;">
    <a href="{{ $href }}" target="_blank" style="display:inline-block; text-decoration:none;">
      @if(!empty($logoCid))
        <img src="{{ $logoCid }}" alt="{{ $brand }}" height="56" style="height:56px;max-height:56px;display:block;margin:0 auto;">
      @else
        <img src="{{ $fallback }}" alt="{{ $brand }}" height="56" style="height:56px;max-height:56px;display:block;margin:0 auto;">
      @endif
    </a>
  </td>
</tr>

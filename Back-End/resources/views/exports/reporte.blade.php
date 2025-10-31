<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <style>
    body{ font-family: DejaVu Sans, sans-serif; color:#1f2937; }
    h1{ font-size:20px; margin:0 0 6px; }
    .muted{ color:#6b7280; }
    table{ width:100%; border-collapse: collapse; margin-top:10px;}
    th,td{ border:1px solid #e5e7eb; padding:6px 8px; font-size:12px; }
    th{ background:#f3f4f6; text-align:left; }
    .head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
  </style>
</head>
<body>
  <div class="head">
    <div>
      <h1>{{ $titulo ?? 'Reporte' }}</h1>
      <div class="muted">{{ $subtitulo ?? '' }}</div>
    </div>
    <div class="muted">{{ now()->format('Y-m-d H:i') }}</div>
  </div>

  @isset($resumen)
    <div class="muted">Resumen: {{ $resumen }}</div>
  @endisset

  <table>
    <thead>
      <tr>
        @foreach(($headings ?? []) as $h)
          <th>{{ $h }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse(($rows ?? []) as $r)
        <tr>
          @foreach($r as $cell)
            <td>{{ is_scalar($cell) ? $cell : json_encode($cell) }}</td>
          @endforeach
        </tr>
      @empty
        <tr><td colspan="{{ count($headings ?? []) }}">Sin datos</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>

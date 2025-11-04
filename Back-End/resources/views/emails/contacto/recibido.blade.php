<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Nuevo mensaje de contacto</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Preheader (vista previa en bandeja) -->
  <meta name="color-scheme" content="light dark">
  <meta name="supported-color-schemes" content="light dark">
  <style>
    /* ====== RESET SUAVE PARA EMAILS SIN TABLAS ====== */
    html,body{margin:0;padding:0}
    body{
      background:#0b0c12;
      color:#e9efff;
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
      font:15px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", Arial;
    }
    a{color:#8ab4ff;text-decoration:none}
    img{max-width:100%;display:block;border:0}

    /* ====== LAYOUT ====== */
    .preheader{
      display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0;
      overflow:hidden; mso-hide:all;
    }
    .wrap{max-width:720px;margin:24px auto;padding:0 14px;}
    .card{
      border-radius:20px; overflow:hidden; position:relative;
      border:1px solid rgba(124,58,237,.25);
      background:
        radial-gradient(1200px 500px at 110% -40%, rgba(34,211,238,.12), transparent 60%),
        radial-gradient(1000px 500px at -10% 120%, rgba(79,70,229,.18), transparent 60%),
        linear-gradient(180deg, #0f1220, #0c0f1a);
      box-shadow:0 16px 50px rgba(0,0,0,.45);
    }

    /* ====== HEADER ====== */
    .head{
      padding:28px 26px 20px;
      border-bottom:1px solid rgba(255,255,255,.06);
      background:
        radial-gradient(900px 360px at 100% -40%, rgba(124,58,237,.26), transparent 60%),
        radial-gradient(700px 320px at -10% 120%, rgba(37,99,235,.26), transparent 60%),
        linear-gradient(180deg, #111637, #0f132e);
    }
    .brand{
      display:inline-flex; align-items:center; gap:.6rem;
      font-weight:800; letter-spacing:.3px; color:#fff; margin:0 0 6px 0;
      font-size:18px;
    }
    .title{
      margin:0; color:#fff; font-weight:900; letter-spacing:.2px;
      font-size:22px;
    }
    .badge{
      display:inline-block; margin-top:10px;
      padding:.35rem .6rem; border-radius:999px;
      background:rgba(124,58,237,.18);
      border:1px solid rgba(124,58,237,.36);
      color:#e6ddff; font-weight:700; font-size:12px; letter-spacing:.2px;
    }

    /* ====== BODY ====== */
    .body{ padding:24px 22px }
    .grid{
      display:flex; flex-wrap:wrap; gap:12px;
      margin:0 0 14px 0;
    }
    .chip{
      flex:1 1 220px; min-width:220px;
      background:#11162f; border:1px solid rgba(255,255,255,.08);
      border-radius:14px; padding:10px 12px;
      color:#dfe7ff;
    }
    .chip .label{ display:block; font-size:12px; color:#9fb0d1; margin-bottom:2px; }
    .chip .value{ font-size:15px; font-weight:700; color:#f3f6ff; }

    .subject{
      margin:6px 0 14px 0;
      display:inline-block;
      padding:.4rem .7rem; border-radius:12px;
      background:rgba(37,99,235,.18);
      border:1px solid rgba(37,99,235,.35);
      color:#deebff; font-weight:800; letter-spacing:.2px;
    }

    .msg{
      white-space:pre-wrap; word-break:break-word;
      line-height:1.7; color:#f4f7ff;
      background:#0f1324; border:1px solid rgba(79,70,229,.35);
      padding:16px; border-radius:14px;
      box-shadow:inset 0 1px 0 rgba(255,255,255,.03);
    }

    .meta{
      margin-top:18px; color:#9fb0d1; font-size:12px;
    }

    /* ====== FOOTER / CTA ====== */
    .cta{
      margin-top:18px;
      display:inline-block; font-weight:800; letter-spacing:.2px;
      color:#0a1630; background:linear-gradient(90deg,#22d3ee,#60a5fa,#a78bfa);
      padding:.65rem 1rem; border-radius:12px;
      box-shadow:0 10px 26px rgba(99,102,241,.35);
    }
    .foot{
      padding:16px 22px; border-top:1px solid rgba(255,255,255,.06);
      font-size:12px; color:#9fb0d1;
      background:linear-gradient(180deg, #0f1220, #0d101b);
    }

    /* ====== DARK MODE OPCIONAL ====== */
    @media (prefers-color-scheme: light){
      body{ background:#f5f7ff; color:#0b1635 }
      .card{ background:#ffffff; box-shadow:0 18px 50px rgba(16,24,40,.12) }
      .head{ background:linear-gradient(180deg,#f6f7ff,#eef3ff) }
      .title{ color:#0b1635 }
      .chip{ background:#f7f9ff; border-color:#e4ebff; color:#0b1635 }
      .chip .label{ color:#5a6b91 }
      .chip .value{ color:#0b1635 }
      .subject{ background:#e8f0ff; border-color:#d4e2ff; color:#10265c }
      .msg{ background:#f7f9ff; border-color:#e4ebff; color:#10265c }
      .meta{ color:#667aa3 }
      .foot{ background:#f6f7ff; color:#667aa3 }
      .cta{ color:#071432 }
    }
  </style>
</head>
<body>
  <!-- Texto corto que aparecerá como preview en Gmail/Outlook/iOS -->
  <div class="preheader">
    Nuevo mensaje recibido desde el formulario de contacto de {{ config('app.name') }}.
  </div>

  <div class="wrap">
    <article class="card" role="article" aria-label="Nuevo mensaje de contacto">
      <header class="head">
        <div class="brand">✨ {{ config('app.name') }}</div>
        <h1 class="title">Nuevo mensaje de contacto</h1>
      </header>

      <section class="body">
        <!-- Datos rápidos en chips -->
        <div class="grid">
          <div class="chip">
            <span class="label">Nombre</span>
            <span class="value">{{ $data['nombre'] }}</span>
          </div>
          <div class="chip">
            <span class="label">Correo</span>
            <span class="value">{{ $data['email'] }}</span>
          </div>
        </div>

        <!-- Asunto destacado -->
        <div class="subject">Asunto: {{ $data['asunto'] }}</div>

        <!-- Mensaje -->
        <div class="msg">{{ $data['mensaje'] }}</div>

        <!-- Meta -->
        <div class="meta">
          Enviado el {{ $meta['when'] }}
        </div>

        <!-- CTA de respuesta rápida -->
        <a class="cta"
           href="mailto:{{ $data['email'] }}?subject=Re:%20{{ rawurlencode($data['asunto']) }}">
          Responder ahora
        </a>
      </section>

      <footer class="foot">
        Este correo fue generado por {{ config('app.name') }}. Si no esperabas este mensaje, puedes ignorarlo.
      </footer>
    </article>
  </div>
</body>
</html>

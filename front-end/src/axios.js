import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
window.Pusher = Pusher

function initEcho () {
  try {
    const jwt  = localStorage.getItem('token')
    const uStr = localStorage.getItem('user')
    if (!jwt || !uStr) return

    const u   = JSON.parse(uStr) || {}
    const uid = String(u.id || u._id || '')
    if (!uid) return
    if (echo) return

    // 🔑 Usa SIEMPRE VUE_APP_ en Vue CLI
    const PUSHER_KEY     = process.env.VUE_APP_PUSHER_APP_KEY
    const PUSHER_CLUSTER = process.env.VUE_APP_PUSHER_APP_CLUSTER || 'mt1'
    const API_BASE       = process.env.VUE_APP_API_BASE  

    if (!PUSHER_KEY) {
      console.error('[Echo] Falta VUE_APP_PUSHER_APP_KEY en tu .env del FRONT')
      return
    }
    if (!API_BASE) {
      console.error('[Echo] Falta VUE_APP_API_BASE en tu .env del FRONT')
      return
    }

    echo = new Echo({
      broadcaster: 'pusher',
      key: PUSHER_KEY,
      cluster: PUSHER_CLUSTER,
      forceTLS: window.location.protocol === 'https:', // evita fallas en http local
      authEndpoint: `${API_BASE}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${jwt}`,
          Accept: 'application/json'
        }
      }
    })

    const p = echo.connector?.pusher
    p?.connection?.bind('state_change', s => console.log('[Echo]', s.previous, '=>', s.current))
    p?.connection?.bind('error', e => console.error('[Echo] error', e))
    p?.connection?.bind('connected', () => console.log('[Echo] connected'))

    channel = echo.private(`user.${uid}`)

    if (!bound) {
      bound = true
      channel.subscribed(() => console.log('[Echo] subscription_succeeded user.' + uid))
      channel.error((status) => console.error('[Echo] subscription_error', status))

      // tus listeners existentes…
      channel.listen('.CitaEstadoCambiado', (data) => {
        notifCount.value++
        notifications.value.unshift({
          title: `Cita ${data?.estado ?? ''}`.trim(),
          body: data?.mensaje || 'Se actualizó el estado de tu cita.',
          time: new Date().toLocaleString(),
          raw: data
        })
      })

      // cierre forzado por login en otro navegador
      channel.listen('.ForcedLogout', async () => {
        try {
          await Swal.fire({
            icon: 'warning',
            title: 'Se inició sesión en otro lugar',
            text: 'Esta sesión se cerrará en este navegador.',
            timer: 2500,
            showConfirmButton: false
          })
        } catch (e) {}
        localStorage.clear()
        destroyEcho()
        router.push('/login')
      })
    }
  } catch (e) {
    console.error('Echo init error:', e)
  }
}

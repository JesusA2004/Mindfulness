// usecitasrealtime.js
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { makeEcho } from '@/echo';

export function useCitasRealtime(getUser, getJwtToken) {
  const received = ref([]);
  let echo, channel;

  onMounted(() => {
    const u = (typeof getUser === 'function' ? getUser() : null) || JSON.parse(localStorage.getItem('user') || '{}');
    const uid = u.id || u._id; // soporta id y _id
    if (!uid) {
      console.warn('[useCitasRealtime] No user id/_id in localStorage');
      return;
    }

    echo = makeEcho(getJwtToken);

    // Logs de diagnóstico (te muestran 401/404 al instante)
    const p = echo.connector?.pusher;
    p?.connection?.bind('state_change', s => console.log('[Echo]', s.previous, '=>', s.current));
    p?.connection?.bind('error', e => console.error('[Echo] error', e));
    p?.connection?.bind('connected', () => console.log('[Echo] connected'));

    channel = echo.private(`user.${uid}`)
      .listen('.CitaEstadoCambiado', (data) => {
        received.value.unshift({
          title: `Cita ${data.estado}`,
          body: data.mensaje || 'Cambio en tu cita',
          at: new Date().toLocaleString(),
          raw: data,
        });
      });
  });

  onBeforeUnmount(() => {
    try {
      if (channel) channel.stopListening('.CitaEstadoCambiado');
      if (echo) echo.disconnect();
    } catch {}
  });

  return { received };
}

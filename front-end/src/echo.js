import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
window.Pusher = Pusher

export function makeEcho(getJwtToken) {
  return new Echo({
    broadcaster: 'pusher',
    key: process.env.VUE_APP_PUSHER_APP_KEY,
    cluster: process.env.VUE_APP_PUSHER_APP_CLUSTER || 'mt1',
    forceTLS: true,
    // 👇 OJO: base sin /api
    authEndpoint: process.env.VUE_APP_API_BASE + '/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${getJwtToken()}`,
        Accept: 'application/json'
      }
    },
  })
}

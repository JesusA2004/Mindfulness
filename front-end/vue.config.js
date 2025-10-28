const { defineConfig } = require('@vue/cli-service')

module.exports = defineConfig({
  transpileDependencies: true,

  // Configuración del servidor de desarrollo
  devServer: {
    port: 8080, // Puerto donde corre tu front (localhost:8080)

    proxy: {
      // Redirige todas las peticiones que empiecen con /api al backend Laravel
      '^/api': {
        target: 'http://127.0.0.1:8000', // URL de tu backend Laravel
        changeOrigin: true,
        ws: true,
        logLevel: 'debug',
      },

      // Soporte para Laravel Echo / Pusher si lo usas
      '^/broadcasting': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        ws: true,
        logLevel: 'debug',
      },

      // Permite servir imágenes u otros archivos desde /storage
      '^/storage': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})

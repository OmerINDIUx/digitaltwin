import { defineConfig } from 'vite';

export default defineConfig({
  server: {
    proxy: {
      // Proxemos cualquier llamada a .php al servidor Apache de Laragon
      '/api_reservas.php': {
        target: 'http://localhost/mi-proyecto-3d/',
        changeOrigin: true,
      },
    },
  },
});

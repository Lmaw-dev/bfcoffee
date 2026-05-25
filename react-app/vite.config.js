import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      // Forward PHP API calls to the XAMPP-served project to avoid CORS issues during dev
      '/store-api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => `/webdev/bfc - Copy${path}`
      },
      '/admin-api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => `/webdev/bfc - Copy${path}`
      },
      '/log-in.php': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => `/webdev/bfc - Copy${path}`
      }
    }
  }
});
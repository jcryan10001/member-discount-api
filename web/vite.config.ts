import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// The React SPA builds into Laravel's public/ so one service serves both the app
// and the JSON API from the same origin (no CORS, one URL to share). base '/'
// keeps asset paths absolute from the domain root under HTTPS, outDir points at
// Laravel's public/ for local builds, and emptyOutDir is false so the build
// merges in rather than wiping Laravel's own public/index.php and .htaccess.
//
// The Docker build overrides outDir to a local dist/ and copies that into
// public/ in the PHP stage, since the node stage has no sibling api/ folder.
export default defineConfig({
  plugins: [react(), tailwindcss()],
  base: '/',
  build: {
    outDir: '../api/public',
    emptyOutDir: false,
  },
  server: {
    // In dev, proxy API calls to the Laravel dev server (php artisan serve).
    proxy: {
      '/api': 'http://localhost:8000',
    },
  },
})

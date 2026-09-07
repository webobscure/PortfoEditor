import { fileURLToPath, URL } from 'node:url'

import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      // Template stylesheets are owned by the backend and shipped verbatim in
      // exports. The editor imports the very same files rather than a copy, so
      // the preview cannot drift from the downloaded archive.
      '@templates': fileURLToPath(new URL('../backend/resources/templates', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    proxy: {
      // Same-origin in development so Sanctum's session cookie is first-party.
      '/api': { target: 'http://127.0.0.1:8000', changeOrigin: false },
      '/sanctum': { target: 'http://127.0.0.1:8000', changeOrigin: false },
      '/storage': { target: 'http://127.0.0.1:8000', changeOrigin: false },
    },
  },
  test: {
    environment: 'jsdom',
    include: ['src/**/*.spec.ts'],
  },
})

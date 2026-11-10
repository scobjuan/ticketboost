import { defineConfig } from 'vite'

// Builds the browser bundle into public/, the only plugin directory GLPI 11+ serves.
export default defineConfig({
  publicDir: false,
  build: {
    outDir: 'public',
    emptyOutDir: false,
    target: 'es2022',
    sourcemap: false,
    minify: true,
    lib: {
      entry: 'frontend/src/main.ts',
      formats: ['es'],
      fileName: () => 'js/ticketboost.js',
      cssFileName: 'css/ticketboost'
    }
  }
})

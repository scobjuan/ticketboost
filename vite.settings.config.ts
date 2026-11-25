import { defineConfig } from 'vite'

// Builds the bundle of the configuration pages (ADR 0019). It shares no output with the
// main bundle and ships no stylesheet: the pages use the GLPI classes.
export default defineConfig({
  publicDir: false,
  build: {
    outDir: 'public',
    emptyOutDir: false,
    target: 'es2022',
    sourcemap: false,
    minify: true,
    lib: {
      entry: 'frontend/src/config/main.ts',
      formats: ['es'],
      fileName: () => 'js/ticketboost-config.js'
    }
  }
})

import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

// Mirrors how Laravel + Vite is configured, so this drops into the Laravel
// project later with only the input/output paths changing.
export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: false,
    rollupOptions: {
      input: {
        app: 'src/app.js',
      },
      output: {
        entryFileNames: 'app.js',
        assetFileNames: '[name][extname]',
      },
    },
  },
});

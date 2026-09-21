import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

// Mirrors how Laravel + Vite is configured, so this drops into the Laravel
// project later with only the input/output paths changing.
export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    outDir: 'backend/public/theme',
    emptyOutDir: true,
    manifest: false,
    // Lightning CSS (Vite's default minifier) collapses compound selectors
    // that share a suffix and end up with identical declarations after
    // Tailwind's @apply expansion — e.g. .page-loader.is-active and
    // .carousel__dot.is-active both reduce to "opacity:1;pointer-events:auto",
    // and it wrongly merges them down to the bare ".is-active", silently
    // breaking every is-active toggle sitewide (page loader spinner, carousel
    // dots, hero fade, bottom nav). 'esbuild' would sidestep it but isn't
    // installed as a direct dependency here, so minification is off instead —
    // correctness over the file-size saving on what's already mostly
    // Tailwind utility output.
    cssMinify: false,
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

import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath } from 'node:url'

/**
 * A page that renders every ported component in light and dark, for looking at.
 * No Storybook in Phase 0 — this is one file and it is enough to catch a recipe
 * that was copied wrong.
 *
 *   npm run playground
 */
export default defineConfig({
  root: fileURLToPath(new URL('.', import.meta.url)),
  plugins: [react(), tailwindcss()],
  server: { port: 5180, host: true },
})

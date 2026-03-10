import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: 'localhost',
    port: 5179,
    open: true, // Abre o navegador automaticamente
  },
  base: '/', // Garante que as rotas funcionem corretamente
})

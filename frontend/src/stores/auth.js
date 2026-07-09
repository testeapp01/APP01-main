import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    hydrated: false
  }),
  actions: {
    clear(redirect = true, redirectPath = '/login') {
      this.user = null
      if (redirect && typeof window !== 'undefined') {
        window.location.assign(redirectPath)
      }
    },
    expireSession() {
      this.clear(true, '/sessao-expirada')
    },
    async hydrateSession() {
      try {
        const response = await api.get('/auth/me')
        this.user = response.data?.user || null
      } catch {
        this.user = null
      } finally {
        this.hydrated = true
      }
    },
    async login(email, password) {
      const res = await api.post('/auth/login', { email, password })
      this.user = res.data.user
      return res.data
    },
    async logout() {
      try { await api.post('/auth/logout') } catch { /* ignore */ }
      this.clear(true)
    }
  },
  getters: {
    isAuthenticated: (state) => !!state.user,
    isSystemUser: (state) => {
      const role = (state.user?.role || '').toString().trim().toLowerCase()
      return ['adm sistema', 'adm_sistema', 'admsistema'].includes(role)
    }
  }
})

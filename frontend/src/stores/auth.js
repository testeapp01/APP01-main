import { defineStore } from 'pinia'
import api from '../services/api'

// SECURITY NOTE: The JWT is currently stored in localStorage, which is
// accessible to JavaScript and therefore vulnerable to XSS attacks.
//
// Migration plan to httpOnly cookie (requires backend changes):
//   1. Backend login() sets cookie: httpOnly + Secure + SameSite=Strict
//   2. Backend reads token from cookie (not Authorization header) when present
//   3. Frontend removes all localStorage.setItem/getItem for token
//   4. Add CSRF protection (double-submit cookie or synchronizer token)
//
// Until migration is complete, reduce risk by:
//   - Keeping JWT expiry at 8h (already done)
//   - Enforcing strict CSP to reduce XSS surface (already done in backend)
//   - Never logging or exposing the token in console/error messages

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: (typeof localStorage !== 'undefined' ? localStorage.getItem('hf_token') : null),
    user: null,
    hydrated: false
  }),
  actions: {
    setToken(token) {
      this.token = token
      if (typeof localStorage !== 'undefined') localStorage.setItem('hf_token', token)
      api.defaults.headers.common.Authorization = `Bearer ${token}`
    },
    clear(redirect = true, redirectPath = '/login') {
      this.token = null
      this.user = null
      if (typeof localStorage !== 'undefined') localStorage.removeItem('hf_token')
      delete api.defaults.headers.common.Authorization
      if (redirect && typeof window !== 'undefined') {
        window.location.assign(redirectPath)
      }
    },
    expireSession() {
      this.clear(true, '/sessao-expirada')
    },
    async hydrateSession() {
      const storedToken = (typeof localStorage !== 'undefined') ? localStorage.getItem('hf_token') : null
      if (!storedToken) {
        this.hydrated = true
        this.clear(false)
        return
      }

      this.setToken(storedToken)
      try {
        const response = await api.get('/auth/me')
        this.user = response.data?.user || null
      } catch (error) {
        this.clear(false)
      } finally {
        this.hydrated = true
      }
    },
    async login(email, password) {
      const res = await api.post('/auth/login', { email, password })
      this.user = res.data.user
      this.setToken(res.data.token)
      return res.data
    }
  }
})

import { defineStore } from 'pinia'
import axios from 'axios'

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
      axios.defaults.headers.common.Authorization = `Bearer ${token}`
    },
    clear(redirect = true) {
      this.token = null
      this.user = null
      if (typeof localStorage !== 'undefined') localStorage.removeItem('hf_token')
      delete axios.defaults.headers.common.Authorization
      if (redirect && typeof window !== 'undefined') {
        window.location.assign('/login')
      }
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
        const response = await axios.get('/api/v1/auth/me')
        this.user = response.data?.user || null
      } catch (error) {
        this.clear(false)
      } finally {
        this.hydrated = true
      }
    },
    async login(email, password) {
      const res = await axios.post('/api/v1/auth/login', { email, password })
      this.user = res.data.user
      this.setToken(res.data.token)
      return res.data
    }
  }
})

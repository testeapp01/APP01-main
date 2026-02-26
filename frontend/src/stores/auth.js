import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({ token: (typeof localStorage !== 'undefined' ? localStorage.getItem('hf_token') : null), user: null }),
  actions: {
    setToken(token) { this.token = token; if (typeof localStorage !== 'undefined') localStorage.setItem('hf_token', token); axios.defaults.headers.common['Authorization'] = `Bearer ${token}` },
    clear() { this.token = null; this.user = null; if (typeof localStorage !== 'undefined') localStorage.removeItem('hf_token'); delete axios.defaults.headers.common['Authorization']; window.location.hash = '#/login' },
    async login(email, password) {
      const res = await axios.post('/api/v1/auth/login', { email, password })
      this.user = res.data.user
      this.setToken(res.data.token)
      return res.data
    }
  }
})

import { defineStore } from 'pinia'
import api from '../services/api'

export const useClientStore = defineStore('clients', {
  state: () => ({
    clients: [],
    loading: false,
    error: null,
    query: '',
    page: 1,
    perPage: 20,
    total: 0,
  }),
  actions: {
    async fetchClients() {
      this.loading = true
      this.error = null
      try {
        const res = await api.get('/api/v1/clients', { params: { q: this.query, page: this.page, perPage: this.perPage } })
        // adapt depending on backend shape
        this.clients = res.data.items || res.data || []
        this.total = res.data.total || this.clients.length
      } catch (err) {
        this.error = err
      } finally {
        this.loading = false
      }
    },
    setQuery(q) {
      this.query = q
      this.page = 1
    },
  }
})

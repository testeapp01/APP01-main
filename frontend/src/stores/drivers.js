import { defineStore } from 'pinia'
import api from '../services/api'

export const useDriverStore = defineStore('drivers', {
  state: () => ({
    drivers: [],
    loading: false,
    error: null,
    query: '',
    page: 1,
    perPage: 20,
    total: 0,
  }),
  actions: {
    async fetchDrivers() {
      this.loading = true
      this.error = null
      try {
        const res = await api.get('/api/v1/drivers', { params: { q: this.query, page: this.page, perPage: this.perPage } })
        this.drivers = res.data.items || res.data || []
        this.total = res.data.total || this.drivers.length
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

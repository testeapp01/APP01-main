import { defineStore } from 'pinia'
import api from '../services/api'

export const useSalesStore = defineStore('sales', {
  state: () => ({
    sales: [],
    loading: false,
    error: null,
    query: '',
    page: 1,
    perPage: 20,
    total: 0,
  }),
  actions: {
    async fetchSales() {
      this.loading = true
      this.error = null
      try {
        const res = await api.get('/api/v1/vendas', { params: { q: this.query, page: this.page, perPage: this.perPage } })
        this.sales = res.data.items || res.data || []
        this.total = res.data.total || this.sales.length
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

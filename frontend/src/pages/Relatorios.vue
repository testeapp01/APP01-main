<template>
  <div class="page-shell report-shell">
    <PageHero
      title="Relatório Estratégico de Compras"
      subtitle="Visão clara dos pedidos, custos e prazos para apoiar decisões rápidas."
    >
      <template #actions>
        <BaseButton
          variant="secondary"
          class="w-full sm:w-auto"
          :disabled="loading"
          @click="resetFilters"
        >
          Limpar
        </BaseButton>
        <BaseButton
          variant="primary"
          class="w-full sm:w-auto"
          :loading="loading"
          :disabled="loading"
          @click="applyFilters"
        >
          Aplicar filtros
        </BaseButton>
        <BaseButton
          variant="secondary"
          class="w-full sm:w-auto"
          :loading="exportingCsv"
          :disabled="exportingXlsx"
          @click="exportReport('csv')"
        >
          Exportar CSV
        </BaseButton>
        <BaseButton
          variant="secondary"
          class="w-full sm:w-auto"
          :loading="exportingXlsx"
          :disabled="exportingCsv"
          @click="exportReport('xlsx')"
        >
          Exportar Excel
        </BaseButton>
      </template>
    </PageHero>

    <PurchaseReportFilters
      :filters="filters"
      :options="options"
      @update-filter="updateFilter"
      @apply="applyFilters"
    />

    <div class="mb-3 px-1 text-sm text-slate-500">
      {{ pagination.total || 0 }} resultado(s) encontrado(s)
    </div>

    <PurchaseKpiCards :kpis="kpis" />

    <PurchaseReportCharts :charts="charts" />

    <PurchaseReportTable
      :rows="rows"
      :loading="loading"
      :pagination="pagination"
      :sort-by="sortBy"
      :sort-dir="sortDir"
      @sort="handleSort"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
      @select-row="selectRow"
    />

    <section
      v-if="selectedRow"
      class="panel-inner mt-4"
    >
      <h3 class="text-base font-semibold text-slate-800 mb-3">
        Linha do tempo de status • Pedido #{{ selectedRow.compra_cabecalho_id || selectedRow.compra_id }}
      </h3>
      <div
        v-if="(selectedRow.status_timeline || []).length"
        class="space-y-2"
      >
        <div
          v-for="(item, idx) in selectedRow.status_timeline"
          :key="`timeline-${idx}`"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2"
        >
          <div class="text-sm font-medium text-slate-700">
            {{ item.status }}
          </div>
          <div class="text-xs text-slate-500">
            {{ formatDateTime(item.confirmado_em) }} • {{ item.usuario || 'Sistema' }}
          </div>
        </div>
      </div>
      <div
        v-else
        class="text-sm text-slate-500"
      >
        Não há histórico de status para este pedido.
      </div>
    </section>
  </div>
</template>

<script>
import api from '../services/api'
import PageHero from '../components/ui/PageHero.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import PurchaseKpiCards from '../components/reports/PurchaseKpiCards.vue'
import PurchaseReportFilters from '../components/reports/PurchaseReportFilters.vue'
import PurchaseReportCharts from '../components/reports/PurchaseReportCharts.vue'
import PurchaseReportTable from '../components/reports/PurchaseReportTable.vue'

export default {
  name: 'ReportsStrategicPurchases',
  components: {
    PageHero,
    BaseButton,
    PurchaseKpiCards,
    PurchaseReportFilters,
    PurchaseReportCharts,
    PurchaseReportTable
  },
  data() {
    return {
      loading: false,
      exportingCsv: false,
      exportingXlsx: false,
      debounceSearchHandle: null,
      rows: [],
      options: {
        fornecedores: [],
        produtos: [],
        motoristas: [],
        status: [],
        ufs: []
      },
      kpis: {
        soma_custo_total: 0,
        soma_comissao_total: 0,
        soma_custo_final_real: 0,
        ticket_medio: 0,
        prazo_medio_dias: 0,
        produto_mais_comprado: { nome: 'Sem dados', quantidade: 0 },
        fornecedor_maior_volume: { nome: 'Sem dados', total_financeiro: 0 }
      },
      charts: {
        line: { labels: [], data: [], datasetLabel: 'Evolução mensal' },
        bar: { labels: [], data: [], datasetLabel: 'Fornecedor x volume' },
        pie: { labels: ['Sem dados'], data: [1] }
      },
      pagination: {
        page: 1,
        per_page: 20,
        pages: 1,
        total: 0
      },
      sortBy: 'data_compra',
      sortDir: 'DESC',
      selectedRow: null,
      filters: {
        from: '',
        to: '',
        fornecedor_id: '',
        produto_id: '',
        motorista_id: '',
        status: '',
        uf: '',
        q: ''
      }
    }
  },
  watch: {
    'filters.q'() {
      clearTimeout(this.debounceSearchHandle)
      this.debounceSearchHandle = setTimeout(() => {
        this.pagination.page = 1
        this.loadReport()
      }, 350)
    }
  },
  mounted() {
    this.loadReport()
  },
  beforeUnmount() {
    clearTimeout(this.debounceSearchHandle)
  },
  methods: {
    buildParams() {
      const params = {
        page: this.pagination.page,
        per_page: this.pagination.per_page,
        sort_by: this.sortBy,
        sort_dir: this.sortDir
      }

      const keys = ['from', 'to', 'fornecedor_id', 'produto_id', 'motorista_id', 'status', 'uf', 'q']
      for (const key of keys) {
        const value = this.filters[key]
        if (value !== null && value !== undefined && String(value).trim() !== '') {
          params[key] = value
        }
      }

      return params
    },
    async loadReport() {
      this.loading = true
      try {
        const response = await api.get('/relatorios/compras', { params: this.buildParams() })
        const data = response.data || {}
        this.rows = data.items || []
        this.kpis = data.kpis || this.kpis
        this.charts = data.charts || this.charts
        this.options = data.options || this.options
        this.pagination = data.pagination || this.pagination
        if (this.selectedRow) {
          const found = this.rows.find(r => r.compra_grupo_id === this.selectedRow.compra_grupo_id)
          this.selectedRow = found || null
        }
      } catch (e) {
        this.rows = []
      } finally {
        this.loading = false
      }
    },
    updateFilter({ key, value }) {
      this.filters = { ...this.filters, [key]: value }
    },
    async applyFilters() {
      this.pagination.page = 1
      await this.loadReport()
    },
    async resetFilters() {
      this.filters = {
        from: '',
        to: '',
        fornecedor_id: '',
        produto_id: '',
        motorista_id: '',
        status: '',
        uf: '',
        q: ''
      }
      this.sortBy = 'data_compra'
      this.sortDir = 'DESC'
      this.pagination.page = 1
      this.selectedRow = null
      await this.loadReport()
    },
    handleSort(column) {
      if (this.sortBy === column) {
        this.sortDir = this.sortDir === 'ASC' ? 'DESC' : 'ASC'
      } else {
        this.sortBy = column
        this.sortDir = 'DESC'
      }
      this.pagination.page = 1
      this.loadReport()
    },
    handlePageChange(page) {
      this.pagination.page = Math.max(1, page)
      this.loadReport()
    },
    handlePerPageChange(perPage) {
      this.pagination.per_page = perPage
      this.pagination.page = 1
      this.loadReport()
    },
    selectRow(row) {
      this.selectedRow = row
    },
    async exportReport(format) {
      if (format === 'csv') this.exportingCsv = true
      if (format === 'xlsx') this.exportingXlsx = true
      try {
        const response = await api.get('/relatorios/compras/export', {
          params: { ...this.buildParams(), format },
          responseType: 'blob'
        })
        const contentDisposition = response.headers['content-disposition'] || ''
        const match = contentDisposition.match(/filename="?([^";]+)"?/i)
        const defaultName = format === 'xlsx' ? 'relatorio-compras-estrategico.xlsx' : 'relatorio-compras-estrategico.csv'
        const fileName = match ? match[1] : defaultName

        const blob = new Blob([response.data], { type: response.headers['content-type'] || 'application/octet-stream' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } finally {
        this.exportingCsv = false
        this.exportingXlsx = false
      }
    },
    formatDateTime(value) {
      if (!value) return '-'
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return String(value)
      return date.toLocaleString('pt-BR')
    }
  }
}
</script>

<style scoped>
.report-shell {
  background: transparent;
}
</style>
<template>
  <div class="page-shell dashboard-container">
    <PageHero
      title="Painel Executivo"
      subtitle="Visão consolidada de performance comercial e operacional."
    >
      <template #actions>
        <select
          v-model="period"
          class="p-2 border border-slate-300 rounded-lg text-sm"
          @change="loadDashboard"
        >
          <option value="7d">Últimos 7 dias</option>
          <option value="30d">Últimos 30 dias</option>
          <option value="90d">Últimos 90 dias</option>
          <option value="180d">Últimos 180 dias</option>
          <option value="365d">Últimos 365 dias</option>
        </select>
        <select
          v-model="metric"
          class="p-2 border border-slate-300 rounded-lg text-sm"
          @change="loadDashboard"
        >
          <option value="sales">Linha: Vendas</option>
          <option value="purchases">Linha: Compras</option>
          <option value="profit">Linha: Lucro</option>
        </select>
      </template>
      <template #context>
        <div class="saas-context-grid">
          <article class="saas-kpi-card">
            <div class="saas-kpi-label">
              Receita de Vendas
            </div>
            <div class="saas-kpi-value">
              {{ asMoney(cards.sales_total) }}
            </div>
            <div class="saas-kpi-help">
              Indicador comercial
            </div>
          </article>
          <article class="saas-kpi-card">
            <div class="saas-kpi-label">
              Volume de Compras
            </div>
            <div class="saas-kpi-value">
              {{ asMoney(cards.purchases_total) }}
            </div>
            <div class="saas-kpi-help">
              Gestão de compras
            </div>
          </article>
          <article class="saas-kpi-card">
            <div class="saas-kpi-label">
              Lucro Estimado
            </div>
            <div class="saas-kpi-value">
              {{ asMoney(cards.estimated_profit) }}
            </div>
            <div class="saas-kpi-help">
              Vendas - Compras
            </div>
          </article>
        </div>
      </template>
    </PageHero>

    <div
      v-if="showEmptyOverview"
      class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4"
    >
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
          <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              class="h-4 w-4"
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
            </svg>
          </span>
          <div>
            <p class="text-sm font-semibold text-slate-700">Sem registros para exibir no painel</p>
            <p class="text-sm text-slate-600">Amplie o período para visualizar movimentações.</p>
          </div>
        </div>
        <BaseButton
          variant="secondary"
          class="w-full sm:w-auto"
          :disabled="loading"
          @click="applySuggestedPeriod"
        >
          {{ suggestedPeriodLabel }}
        </BaseButton>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="card p-4 rounded-xl border border-slate-200 bg-white">
        <div class="text-xs uppercase text-slate-500">Vendas</div>
        <div class="text-2xl font-bold text-slate-800">{{ cards.sales_count }}</div>
      </div>
      <div class="card p-4 rounded-xl border border-slate-200 bg-white">
        <div class="text-xs uppercase text-slate-500">Compras</div>
        <div class="text-2xl font-bold text-slate-800">{{ cards.purchases_count }}</div>
      </div>
      <div class="card p-4 rounded-xl border border-slate-200 bg-white">
        <div class="text-xs uppercase text-slate-500">Ticket Médio</div>
        <div class="text-2xl font-bold text-slate-800">{{ asMoney(cards.average_ticket) }}</div>
      </div>
      <div class="card p-4 rounded-xl border border-slate-200 bg-white">
        <div class="text-xs uppercase text-slate-500">Clientes / Produtos</div>
        <div class="text-2xl font-bold text-slate-800">{{ cards.clients_total }} / {{ cards.products_total }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <div class="chart-card xl:col-span-2">
        <div class="chart-header flex items-center justify-between gap-3">
          <h3>{{ lineTitle }}</h3>
          <span class="text-xs text-slate-500">{{ loading ? 'Atualizando...' : 'Atualizado' }}</span>
        </div>

        <div class="chart-wrapper relative">
          <canvas ref="lineCanvas" />
          <div
            v-if="!hasLineData"
            class="absolute inset-0 flex items-center justify-center text-sm text-slate-500"
          >
            Sem registros no período selecionado.
          </div>
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-header">
          <h3>Status das Vendas</h3>
        </div>
        <div class="chart-wrapper pie-wrapper relative">
          <canvas ref="pieCanvas" />
          <div
            v-if="!hasPieData"
            class="absolute inset-0 flex items-center justify-center text-sm text-slate-500"
          >
            Sem registros de status no período.
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
    >
      {{ errorMessage }}
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue'
import PageHero from '../components/ui/PageHero.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import api from '../services/api'
import Chart from 'chart.js/auto'

export default {
  components: { PageHero, BaseButton },

  setup() {

    const period = ref('30d')
    const metric = ref('sales')
    const loading = ref(false)
    const errorMessage = ref('')
    const cards = ref({
      sales_total: 0,
      purchases_total: 0,
      estimated_profit: 0,
      sales_count: 0,
      purchases_count: 0,
      average_ticket: 0,
      clients_total: 0,
      products_total: 0,
    })

    const lineCanvas = ref(null)
    const pieCanvas = ref(null)

    const lineData = ref({ labels: [], datasetLabel: 'Vendas', data: [] })
    const pieData = ref({ labels: [], data: [] })

    let lineChart = null
    let pieChart = null
    let autoRefreshTimer = null

    const lineTitle = computed(() => {
      if (metric.value === 'purchases') return 'Evolução de Compras'
      if (metric.value === 'profit') return 'Evolução de Lucro Estimado'
      return 'Evolução de Vendas'
    })

    const hasLineData = computed(() => {
      return (lineData.value.data || []).some(value => Number(value || 0) > 0)
    })

    const hasPieData = computed(() => {
      const labels = pieData.value.labels || []
      const values = pieData.value.data || []
      if (!labels.length || !values.length) return false
      if (labels.length === 1 && String(labels[0]).toLowerCase() === 'sem dados') return false
      return values.some(value => Number(value || 0) > 0)
    })

    const showEmptyOverview = computed(() => {
      const salesCount = Number(cards.value.sales_count || 0)
      const purchasesCount = Number(cards.value.purchases_count || 0)
      return salesCount === 0 && purchasesCount === 0
    })

    const periodOrder = ['7d', '30d', '90d', '180d', '365d']
    const suggestedPeriod = computed(() => {
      const idx = periodOrder.indexOf(period.value)
      if (idx < 0) return '90d'
      return periodOrder[Math.min(idx + 1, periodOrder.length - 1)]
    })

    const suggestedPeriodLabel = computed(() => {
      const value = suggestedPeriod.value
      return `Expandir para últimos ${value.replace('d', '')} dias`
    })

    const normalizeLine = (line) => {
      const labels = Array.isArray(line?.labels) ? line.labels : []
      const data = Array.isArray(line?.data) ? line.data : []
      const datasetLabel = line?.datasetLabel || 'Vendas'

      if (!labels.length || !data.length) {
        return { labels: ['Sem dados'], data: [0], datasetLabel }
      }

      return { labels, data, datasetLabel }
    }

    const normalizePie = (pie) => {
      const labels = Array.isArray(pie?.labels) ? pie.labels : []
      const data = Array.isArray(pie?.data) ? pie.data : []

      if (!labels.length || !data.length) {
        return { labels: ['Sem dados'], data: [1] }
      }

      return { labels, data }
    }

    const asMoney = (value) => {
      return Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      })
    }

    const loadDashboard = async () => {
      loading.value = true
      errorMessage.value = ''
      try {
        const response = await api.get('/relatorios/dashboard', {
          headers: {
            'Cache-Control': 'no-cache',
            Pragma: 'no-cache'
          },
          params: {
            period: period.value,
            metric: metric.value,
            _ts: Date.now(),
          }
        })

        cards.value = { ...cards.value, ...(response.data?.cards || {}) }
        lineData.value = normalizeLine(response.data?.line)
        pieData.value = normalizePie(response.data?.pie)

        await nextTick()
        renderLineChart()
        renderPieChart()
      } catch (e) {
        console.error('Erro ao buscar dashboard:', e)
        errorMessage.value = 'Não foi possível atualizar o painel agora. Tente novamente em instantes.'
        lineData.value = { labels: ['Sem dados'], datasetLabel: 'Vendas', data: [0] }
        pieData.value = { labels: ['Sem dados'], data: [1] }
        await nextTick()
        renderLineChart()
        renderPieChart()
      } finally {
        loading.value = false
      }
    }

    const applySuggestedPeriod = async () => {
      period.value = suggestedPeriod.value
      await loadDashboard()
    }

    const renderLineChart = () => {
      const ctx = lineCanvas.value
      if (!ctx) return

      if (lineChart) {
        lineChart.destroy()
      }

      const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300)
      gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)')
      gradient.addColorStop(1, 'rgba(99, 102, 241, 0.05)')

      lineChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: lineData.value.labels,
          datasets: [{
            label: lineData.value.datasetLabel,
            data: lineData.value.data,
            borderColor: '#6366f1',
            backgroundColor: gradient,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#6366f1'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: 'index',
            intersect: false
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#111827',
              padding: 10,
              cornerRadius: 8
            }
          },
          scales: {
            x: {
              grid: { display: false }
            },
            y: {
              beginAtZero: false,
              grid: {
                color: 'rgba(0,0,0,0.05)'
              }
            }
          }
        }
      })
    }

    const renderPieChart = () => {
      const ctx = pieCanvas.value
      if (!ctx) return

      if (pieChart) {
        pieChart.destroy()
      }

      pieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: pieData.value.labels,
          datasets: [
            {
              data: pieData.value.data,
              backgroundColor: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'],
              borderWidth: 1,
              borderColor: '#fff',
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      })
    }

    const handleWindowFocus = () => {
      loadDashboard()
    }

    const handleVisibilityChange = () => {
      if (!document.hidden) {
        loadDashboard()
      }
    }

    onMounted(async () => {
      await nextTick()
      await loadDashboard()
      window.addEventListener('focus', handleWindowFocus)
      document.addEventListener('visibilitychange', handleVisibilityChange)
      autoRefreshTimer = window.setInterval(() => {
        loadDashboard()
      }, 30000)
    })

    onBeforeUnmount(() => {
      if (lineChart) lineChart.destroy()
      if (pieChart) pieChart.destroy()
      window.removeEventListener('focus', handleWindowFocus)
      document.removeEventListener('visibilitychange', handleVisibilityChange)
      if (autoRefreshTimer) window.clearInterval(autoRefreshTimer)
    })

    return {
      period,
      metric,
      loading,
      errorMessage,
      cards,
      lineCanvas,
      pieCanvas,
      lineTitle,
      hasLineData,
      hasPieData,
      showEmptyOverview,
      suggestedPeriodLabel,
      applySuggestedPeriod,
      asMoney,
      loadDashboard,
    }
  }
}
</script>

<style scoped>

.dashboard-container {
  padding: 12px;
  background: #f9fafb;
  min-height: auto;
}

.dashboard-header {
  margin-bottom: 20px;
}

.dashboard-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
}

.chart-card {
  margin-top: 16px;
  background: white;
  border-radius: 16px;
  padding: 14px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
  transition: all .3s ease;
}

.chart-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(0,0,0,0.08);
}

.chart-header {
  margin-bottom: 16px;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.chart-wrapper {
  position: relative;
  width: 100%;
  height: 240px;
}

.pie-wrapper {
  max-height: 320px;
}

@media (min-width: 640px) {
  .dashboard-container { padding: 20px; }
  .dashboard-title { font-size: 28px; }
  .chart-card { margin-top: 24px; padding: 20px; }
  .chart-wrapper { height: 300px; }
}

canvas {
  width: 100% !important;
  height: 100% !important;
}

</style>
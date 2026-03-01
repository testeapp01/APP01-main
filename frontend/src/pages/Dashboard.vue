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
              Reposição de estoque
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

        <div class="chart-wrapper">
          <canvas ref="lineCanvas" />
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-header">
          <h3>Status das Vendas</h3>
        </div>
        <div class="chart-wrapper pie-wrapper">
          <canvas ref="pieCanvas" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue'
import PageHero from '../components/ui/PageHero.vue'
import api from '../services/api'
import Chart from 'chart.js/auto'

export default {
  components: { PageHero },

  setup() {

    const period = ref('30d')
    const metric = ref('sales')
    const loading = ref(false)
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

    const lineTitle = computed(() => {
      if (metric.value === 'purchases') return 'Evolução de Compras'
      if (metric.value === 'profit') return 'Evolução de Lucro Estimado'
      return 'Evolução de Vendas'
    })

    const asMoney = (value) => {
      return Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      })
    }

    const loadDashboard = async () => {
      loading.value = true
      try {
        const response = await api.get('/relatorios/dashboard', {
          params: {
            period: period.value,
            metric: metric.value,
          }
        })

        cards.value = { ...cards.value, ...(response.data?.cards || {}) }
        lineData.value = response.data?.line || { labels: [], datasetLabel: 'Vendas', data: [] }
        pieData.value = response.data?.pie || { labels: ['Sem dados'], data: [1] }

        await nextTick()
        renderLineChart()
        renderPieChart()
      } catch (e) {
        console.error('Erro ao buscar dashboard:', e)
      } finally {
        loading.value = false
      }
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

    onMounted(async () => {
      await nextTick()
      loadDashboard()
    })

    onBeforeUnmount(() => {
      if (lineChart) lineChart.destroy()
      if (pieChart) pieChart.destroy()
    })

    return {
      period,
      metric,
      loading,
      cards,
      lineCanvas,
      pieCanvas,
      lineTitle,
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
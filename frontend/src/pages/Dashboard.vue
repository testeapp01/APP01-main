<template>
  <div class="page-shell dashboard-container">
    <PageHero
      title="Painel Executivo"
      subtitle="Visão consolidada de performance comercial e operacional."
    >
      <template #context>
        <div class="saas-context-grid">
          <article class="saas-kpi-card">
            <div class="saas-kpi-label">
              Receita de Vendas
            </div>
            <div class="saas-kpi-value">
              R$ {{ totals.sales }}
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
              R$ {{ totals.purchases }}
            </div>
            <div class="saas-kpi-help">
              Reposição de estoque
            </div>
          </article>
          <article class="saas-kpi-card">
            <div class="saas-kpi-label">
              Clientes Ativos
            </div>
            <div class="saas-kpi-value">
              {{ totals.clients }}
            </div>
            <div class="saas-kpi-help">
              Base relacional
            </div>
          </article>
        </div>
      </template>
    </PageHero>

    <DashboardStats :totals="totals" />

    <div class="chart-card">
      <div class="chart-header">
        <h3>Projeção de Vendas</h3>
      </div>

      <div class="chart-wrapper">
        <canvas id="sales-chart" />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import DashboardStats from '../components/DashboardStats.vue'
import PageHero from '../components/ui/PageHero.vue'
import api from '../services/api'
import Chart from 'chart.js/auto'

export default {
  components: { DashboardStats, PageHero },

  setup() {

    const totals = ref({ sales: 0, purchases: 0, clients: 0 })
    let chartInstance = null

    const fetchData = async () => {
      try {
        const [sRes, pRes, cRes] = await Promise.all([
          api.get('/api/v1/vendas/total').catch(() => ({ data: { total: 0 } })),
          api.get('/api/v1/compras/total').catch(() => ({ data: { total: 0 } })),
          api.get('/api/v1/clientes/ativos').catch(() => ({ data: { total: 0 } })),
        ])

        totals.value = {
          sales: sRes.data.total,
          purchases: pRes.data.total,
          clients: cRes.data.total
        }

      } catch (e) {
        console.error('Erro ao buscar totais:', e)
      }
    }

    const renderChart = () => {

      const ctx = document.getElementById('sales-chart')
      if (!ctx) return

      if (chartInstance) {
        chartInstance.destroy()
      }

      const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300)
      gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)')
      gradient.addColorStop(1, 'rgba(99, 102, 241, 0.05)')

      chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
          datasets: [{
            label: 'Vendas',
            data: [5000, 10000, 7500, 12500, 15000],
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
              beginAtZero: true,
              grid: {
                color: 'rgba(0,0,0,0.05)'
              }
            }
          }
        }
      })
    }

    onMounted(async () => {
      await nextTick()
      fetchData()
      renderChart()
    })

    onBeforeUnmount(() => {
      if (chartInstance) {
        chartInstance.destroy()
      }
    })

    return { totals }
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
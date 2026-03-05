<template>
  <section class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
    <article class="panel-inner">
      <div class="text-sm font-semibold text-slate-700 mb-2">
        Evolução mensal
      </div>
      <div class="h-56">
        <canvas
          ref="lineChart"
          class="w-full h-full"
        />
      </div>
    </article>

    <article class="panel-inner">
      <div class="text-sm font-semibold text-slate-700 mb-2">
        Fornecedor x volume
      </div>
      <div class="h-56">
        <canvas
          ref="barChart"
          class="w-full h-full"
        />
      </div>
    </article>

    <article class="panel-inner">
      <div class="text-sm font-semibold text-slate-700 mb-2">
        Distribuição por status
      </div>
      <div class="h-56">
        <canvas
          ref="pieChart"
          class="w-full h-full"
        />
      </div>
    </article>
  </section>
</template>

<script>
import Chart from 'chart.js/auto'

export default {
  name: 'PurchaseReportCharts',
  props: {
    charts: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      lineInstance: null,
      barInstance: null,
      pieInstance: null
    }
  },
  watch: {
    charts: {
      deep: true,
      handler() {
        this.renderCharts()
      }
    }
  },
  mounted() {
    this.renderCharts()
  },
  beforeUnmount() {
    this.destroyCharts()
  },
  methods: {
    destroyCharts() {
      this.lineInstance?.destroy()
      this.barInstance?.destroy()
      this.pieInstance?.destroy()
      this.lineInstance = null
      this.barInstance = null
      this.pieInstance = null
    },
    renderCharts() {
      this.destroyCharts()

      const line = this.charts.line || { labels: [], data: [], datasetLabel: 'Evolução' }
      const bar = this.charts.bar || { labels: [], data: [], datasetLabel: 'Volume' }
      const pie = this.charts.pie || { labels: ['Sem dados'], data: [1] }

      if (this.$refs.lineChart) {
        this.lineInstance = new Chart(this.$refs.lineChart, {
          type: 'line',
          data: {
            labels: line.labels || [],
            datasets: [{
              label: line.datasetLabel || 'Evolução mensal',
              data: line.data || [],
              borderWidth: 2,
              tension: 0.25,
              fill: false
            }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        })
      }

      if (this.$refs.barChart) {
        this.barInstance = new Chart(this.$refs.barChart, {
          type: 'bar',
          data: {
            labels: bar.labels || [],
            datasets: [{
              label: bar.datasetLabel || 'Fornecedor x volume',
              data: bar.data || [],
              borderWidth: 1,
              borderRadius: 8
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
          }
        })
      }

      if (this.$refs.pieChart) {
        this.pieInstance = new Chart(this.$refs.pieChart, {
          type: 'pie',
          data: {
            labels: pie.labels || ['Sem dados'],
            datasets: [{
              data: pie.data || [1]
            }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        })
      }
    }
  }
}
</script>

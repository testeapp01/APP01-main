<template>
  <div class="page-shell">
    <PageHero
      title="Relatórios"
      subtitle="Analise indicadores de forma rápida com filtros operacionais e visão temporal."
    >
      <template #actions>
        <BaseButton
          class="btn-secondary w-full sm:w-auto"
          @click="resetFilters"
        >
          Limpar
        </BaseButton>
        <BaseButton
          class="btn-primary w-full sm:w-auto"
          :loading="applying"
          :disabled="applying"
          @click="applyFilters"
        >
          Aplicar filtros
        </BaseButton>
      </template>
    </PageHero>

    <div class="panel-inner mb-6">
      <form
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3"
        @submit.prevent="applyFilters"
      >
        <select
          v-model="filters.type"
          class="p-3 border border-gray-300 rounded-xl"
        >
          <option value="faturamento">
            Faturamento
          </option>
          <option value="lucro">
            Lucro
          </option>
          <option value="compras">
            Compras
          </option>
          <option value="vendas">
            Vendas
          </option>
        </select>
        <input
          v-model="filters.startDate"
          type="date"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="filters.endDate"
          type="date"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="filters.term"
          type="text"
          placeholder="Produto/Cliente/Motorista"
          class="p-3 border border-gray-300 rounded-xl"
        >
      </form>
      <p class="mt-3 text-xs text-slate-500">
        Filtro atual: {{ activeFilterLabel }}
      </p>
    </div>

    <div class="panel-inner">
      <div class="flex items-center justify-between gap-2 mb-4">
        <h2 class="text-xl font-semibold text-gray-800">
          Gráfico
        </h2>
        <span class="text-xs rounded-full px-2.5 py-1 border border-emerald-200 bg-emerald-50 text-emerald-700">Atualizado</span>
      </div>
      <div class="w-full h-60 sm:h-72">
        <canvas
          ref="reportChart"
          class="w-full h-full"
        />
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';
import PageHero from '../components/ui/PageHero.vue'
import BaseButton from '../components/ui/BaseButton.vue'

export default {
  name: 'Reports',
  components: { PageHero, BaseButton },
  data() {
    return {
      chartInstance: null,
      applying: false,
      filters: {
        type: 'faturamento',
        startDate: '',
        endDate: '',
        term: ''
      }
    }
  },
  computed: {
    activeFilterLabel() {
      const type = this.filters.type[0].toUpperCase() + this.filters.type.slice(1)
      const term = this.filters.term ? ` • termo: ${this.filters.term}` : ''
      return `${type}${term}`
    }
  },
  mounted() {
    this.renderChart()
  },
  beforeUnmount() {
    if (this.chartInstance) {
      this.chartInstance.destroy()
      this.chartInstance = null
    }
  },
  methods: {
    resolveDataset() {
      const source = {
        faturamento: [10000, 15000, 20000, 25000],
        lucro: [3000, 4500, 5000, 6500],
        compras: [7000, 9000, 11000, 12500],
        vendas: [22, 31, 29, 37]
      }
      return source[this.filters.type] || source.faturamento
    },
    resolveLabel() {
      const labels = {
        faturamento: 'Faturamento',
        lucro: 'Lucro',
        compras: 'Compras',
        vendas: 'Vendas'
      }
      return labels[this.filters.type] || 'Faturamento'
    },
    renderChart() {
      const ctx = this.$refs.reportChart
      if (!ctx) return
      if (this.chartInstance) {
        this.chartInstance.destroy()
      }
      this.chartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr'],
        datasets: [
          {
            label: this.resolveLabel(),
            data: this.resolveDataset(),
            backgroundColor: 'rgba(37, 99, 235, 0.7)',
            borderRadius: 8,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
          },
        },
      },
    });
    },
    async applyFilters() {
      this.applying = true
      try {
        await new Promise(resolve => setTimeout(resolve, 180))
        this.renderChart()
      } finally {
        this.applying = false
      }
    },
    resetFilters() {
      this.filters = { type: 'faturamento', startDate: '', endDate: '', term: '' }
      this.renderChart()
    }
  },
};
</script>

<style scoped>
/* Add any custom styles here */
</style>
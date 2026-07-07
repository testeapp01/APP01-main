<template>
  <div class="panel-inner mb-4 report-filters-shell">
    <form @submit.prevent="$emit('apply')">
      <div class="mb-3">
        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Busca</label>
        <input
          :value="filters.q || ''"
          type="text"
          placeholder="Fornecedor, produto, motorista ou status"
          class="p-3 border border-gray-300 rounded-xl w-full"
          @input="updateFilter('q', $event.target.value)"
        >
      </div>

      <div class="mb-3 flex flex-wrap gap-2 quick-ranges">
        <button
          type="button"
          class="rounded-full border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
          @click="setQuickRange(7)"
        >
          Últimos 7 dias
        </button>
        <button
          type="button"
          class="rounded-full border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
          @click="setQuickRange(30)"
        >
          Últimos 30 dias
        </button>
        <button
          type="button"
          class="rounded-full border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
          @click="setQuickRange(90)"
        >
          Últimos 90 dias
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <input
          :value="filters.from || ''"
          type="date"
          class="p-3 border border-gray-300 rounded-xl"
          @input="updateFilter('from', $event.target.value)"
        >
        <input
          :value="filters.to || ''"
          type="date"
          class="p-3 border border-gray-300 rounded-xl"
          @input="updateFilter('to', $event.target.value)"
        >
        <select
          :value="filters.fornecedor_id || ''"
          class="p-3 border border-gray-300 rounded-xl"
          @change="updateFilter('fornecedor_id', $event.target.value)"
        >
          <option value="">
            Todos os fornecedores
          </option>
          <option
            v-for="fornecedor in options.fornecedores || []"
            :key="`fornecedor-${fornecedor.id}`"
            :value="fornecedor.id"
          >
            {{ fornecedor.razao_social }}
          </option>
        </select>
      </div>

      <button
        type="button"
        class="mt-3 text-sm font-medium text-emerald-700 hover:text-emerald-800"
        @click="advancedOpen = !advancedOpen"
      >
        {{ advancedOpen ? 'Ocultar filtros avançados' : 'Mostrar filtros avançados' }}
      </button>

      <div
        v-if="advancedOpen"
        class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3"
      >
        <select
          :value="filters.produto_id || ''"
          class="p-3 border border-gray-300 rounded-xl"
          @change="updateFilter('produto_id', $event.target.value)"
        >
          <option value="">
            Todos os produtos
          </option>
          <option
            v-for="produto in options.produtos || []"
            :key="`produto-${produto.id}`"
            :value="produto.id"
          >
            {{ produto.nome }}
          </option>
        </select>

        <select
          :value="filters.motorista_id || ''"
          class="p-3 border border-gray-300 rounded-xl"
          @change="updateFilter('motorista_id', $event.target.value)"
        >
          <option value="">
            Todos os motoristas
          </option>
          <option
            v-for="motorista in options.motoristas || []"
            :key="`motorista-${motorista.id}`"
            :value="motorista.id"
          >
            {{ motorista.nome }}
          </option>
        </select>

        <select
          :value="filters.status || ''"
          class="p-3 border border-gray-300 rounded-xl"
          @change="updateFilter('status', $event.target.value)"
        >
          <option value="">
            Todos os status
          </option>
          <option
            v-for="status in options.status || []"
            :key="`status-${status}`"
            :value="status"
          >
            {{ status }}
          </option>
        </select>

        <select
          :value="filters.uf || ''"
          class="p-3 border border-gray-300 rounded-xl"
          @change="updateFilter('uf', $event.target.value)"
        >
          <option value="">
            Todas as UFs
          </option>
          <option
            v-for="uf in options.ufs || []"
            :key="`uf-${uf}`"
            :value="uf"
          >
            {{ uf }}
          </option>
        </select>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  name: 'PurchaseReportFilters',
  props: {
    filters: {
      type: Object,
      default: () => ({})
    },
    options: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update-filter', 'apply', 'reset'],
  data() {
    return {
      advancedOpen: false
    }
  },
  methods: {
    updateFilter(key, value) {
      this.$emit('update-filter', { key, value })
    },
    setQuickRange(days) {
      const today = new Date()
      const start = new Date(today)
      start.setDate(today.getDate() - days + 1)

      const toIsoDate = (date) => {
        const y = date.getFullYear()
        const m = String(date.getMonth() + 1).padStart(2, '0')
        const d = String(date.getDate()).padStart(2, '0')
        return `${y}-${m}-${d}`
      }

      this.updateFilter('from', toIsoDate(start))
      this.updateFilter('to', toIsoDate(today))
      this.$emit('apply')
    }
  }
}
</script>

<style scoped>
.report-filters-shell :is(input, select, button):focus-visible {
  outline: none;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.16);
}

.quick-ranges button {
  min-height: 36px;
}

@media (max-width: 767px) {
  .report-filters-shell {
    padding: 0.9rem;
  }

  .quick-ranges button {
    flex: 1 1 calc(50% - 0.5rem);
    justify-content: center;
  }
}
</style>

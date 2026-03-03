<template>
  <div class="panel-inner mb-4">
    <form
      class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3"
      @submit.prevent="$emit('apply')"
    >
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
          Fornecedor
        </option>
        <option
          v-for="fornecedor in options.fornecedores || []"
          :key="`fornecedor-${fornecedor.id}`"
          :value="fornecedor.id"
        >
          {{ fornecedor.razao_social }}
        </option>
      </select>

      <select
        :value="filters.produto_id || ''"
        class="p-3 border border-gray-300 rounded-xl"
        @change="updateFilter('produto_id', $event.target.value)"
      >
        <option value="">
          Produto
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
          Motorista
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
          Status
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
          UF
        </option>
        <option
          v-for="uf in options.ufs || []"
          :key="`uf-${uf}`"
          :value="uf"
        >
          {{ uf }}
        </option>
      </select>

      <input
        :value="filters.q || ''"
        type="text"
        placeholder="Busca dinâmica por fornecedor, produto, motorista ou status"
        class="p-3 border border-gray-300 rounded-xl md:col-span-2"
        @input="updateFilter('q', $event.target.value)"
      >
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
  methods: {
    updateFilter(key, value) {
      this.$emit('update-filter', { key, value })
    }
  }
}
</script>

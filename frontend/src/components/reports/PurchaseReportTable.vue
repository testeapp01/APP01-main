<template>
  <section class="panel-inner">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
      <h2 class="text-lg font-semibold text-slate-800">
        Visão consolidada por pedido
      </h2>
      <div class="text-xs text-slate-500">
        {{ pagination.total || 0 }} registros
      </div>
    </div>

    <div
      v-if="loading"
      class="md:hidden rounded-xl border border-slate-200 bg-white/85 p-4 text-center text-sm text-slate-500"
    >
      Carregando relatório...
    </div>

    <div
      v-else-if="rows.length === 0"
      class="md:hidden rounded-xl border border-slate-200 bg-white/85 p-5 text-center text-sm text-slate-500"
    >
      Nenhum registro encontrado para os filtros informados.
    </div>

    <div
      v-else
      class="md:hidden space-y-3"
    >
      <article
        v-for="row in rows"
        :key="`m-row-${row.compra_grupo_id}`"
        class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-[0_10px_22px_rgba(15,23,42,0.07)]"
        @click="$emit('select-row', row)"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">
              Pedido #{{ row.compra_cabecalho_id || '-' }}
            </p>
            <p class="text-sm font-semibold text-slate-800 mt-1">
              {{ row.fornecedor || 'Fornecedor não informado' }}
            </p>
          </div>
          <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
            {{ row.status_textual || 'AGUARDANDO' }}
          </span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
          <div class="text-slate-500">
            Data
          </div>
          <div class="text-right text-slate-700 font-medium">
            {{ date(row.data_compra) }}
          </div>

          <div class="text-slate-500">
            Produto
          </div>
          <div class="text-right text-slate-700 font-medium truncate">
            {{ row.produto || 'Não informado' }}
          </div>

          <div class="text-slate-500">
            Quantidade
          </div>
          <div class="text-right text-slate-700 font-medium">
            {{ number(row.quantidade) }}
          </div>

          <div class="text-slate-500">
            Custo total
          </div>
          <div class="text-right text-slate-800 font-semibold">
            {{ money(row.custo_total) }}
          </div>
        </div>
      </article>
    </div>

    <div class="hidden md:block overflow-auto max-h-[560px] rounded-xl border border-slate-200">
      <table class="min-w-[1100px] w-full text-sm">
        <thead>
          <tr class="bg-slate-50">
            <th
              v-for="col in columns"
              :key="col.key"
              class="sticky top-0 z-10 bg-slate-50 text-left px-3 py-2 font-semibold text-slate-600 border-b border-slate-200 cursor-pointer select-none"
              @click="sort(col.key)"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <span
                  v-if="sortBy === col.key"
                  class="text-xs"
                >
                  {{ sortDir === 'ASC' ? '▲' : '▼' }}
                </span>
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-if="loading"
          >
            <td
              :colspan="columns.length"
              class="px-3 py-5 text-center text-slate-500"
            >
              Carregando relatório...
            </td>
          </tr>

          <tr
            v-for="row in rows"
            :key="`row-${row.compra_grupo_id}`"
            class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
            @click="$emit('select-row', row)"
          >
            <td class="px-3 py-2">
              {{ row.compra_id }}
            </td>
            <td class="px-3 py-2">
              {{ row.compra_cabecalho_id || '-' }}
            </td>
            <td class="px-3 py-2">
              {{ date(row.data_compra) }}
            </td>
            <td class="px-3 py-2">
              {{ row.fornecedor || 'Não informado' }}
            </td>
            <td class="px-3 py-2">
              {{ row.produto || 'Não informado' }}
            </td>
            <td class="px-3 py-2">
              {{ row.motorista || 'Não informado' }}
            </td>
            <td class="px-3 py-2">
              {{ row.tipo_caminhao || 'Não informado' }}
            </td>
            <td class="px-3 py-2">
              {{ number(row.quantidade) }}
            </td>
            <td class="px-3 py-2">
              {{ money(row.valor_unitario) }}
            </td>
            <td class="px-3 py-2">
              {{ money(row.custo_total) }}
            </td>
            <td class="px-3 py-2">
              {{ money(row.comissao_total) }}
            </td>
            <td class="px-3 py-2">
              {{ money(row.custo_final_real) }}
            </td>
            <td class="px-3 py-2">
              {{ row.status_textual || 'AGUARDANDO' }}
            </td>
            <td class="px-3 py-2">
              {{ date(row.data_envio_prevista) }}
            </td>
            <td class="px-3 py-2">
              {{ date(row.data_entrega_prevista) }}
            </td>
            <td class="px-3 py-2">
              {{ row.itens_count || 0 }}
            </td>
            <td class="px-3 py-2">
              {{ money(row.valor_total_agregado) }}
            </td>
          </tr>

          <tr v-if="!loading && rows.length === 0">
            <td
              :colspan="columns.length"
              class="px-3 py-8 text-center text-slate-500"
            >
              Nenhum registro encontrado para os filtros informados.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4">
      <div class="text-xs text-slate-500">
        Página {{ pagination.page || 1 }} de {{ pagination.pages || 1 }}
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <select
          :value="pagination.per_page || 20"
          class="px-2 py-1.5 border border-slate-300 rounded-lg text-sm"
          @change="$emit('per-page-change', Number($event.target.value))"
        >
          <option :value="10">
            10
          </option>
          <option :value="20">
            20
          </option>
          <option :value="50">
            50
          </option>
          <option :value="100">
            100
          </option>
        </select>
        <button
          class="btn-secondary"
          :disabled="(pagination.page || 1) <= 1"
          @click="$emit('page-change', (pagination.page || 1) - 1)"
        >
          Anterior
        </button>
        <button
          class="btn-secondary"
          :disabled="(pagination.page || 1) >= (pagination.pages || 1)"
          @click="$emit('page-change', (pagination.page || 1) + 1)"
        >
          Próxima
        </button>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'PurchaseReportTable',
  props: {
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    pagination: { type: Object, default: () => ({}) },
    sortBy: { type: String, default: 'data_compra' },
    sortDir: { type: String, default: 'DESC' }
  },
  emits: ['sort', 'page-change', 'per-page-change', 'select-row'],
  computed: {
    columns() {
      return [
        { key: 'compra_id', label: 'ID Compra' },
        { key: 'compra_cabecalho_id', label: 'ID Pedido' },
        { key: 'data_compra', label: 'Data compra' },
        { key: 'fornecedor', label: 'Fornecedor' },
        { key: 'produto', label: 'Produto(s)' },
        { key: 'motorista', label: 'Motorista(s)' },
        { key: 'tipo_caminhao', label: 'Tipo caminhão' },
        { key: 'quantidade', label: 'Quantidade' },
        { key: 'valor_unitario', label: 'Valor unitário' },
        { key: 'custo_total', label: 'Custo total' },
        { key: 'comissao_total', label: 'Comissão total' },
        { key: 'custo_final_real', label: 'Custo final real' },
        { key: 'status_textual', label: 'Status' },
        { key: 'data_envio_prevista', label: 'Envio previsto' },
        { key: 'data_entrega_prevista', label: 'Entrega prevista' },
        { key: 'itens_count', label: 'Itens' },
        { key: 'valor_total_agregado', label: 'Valor total agregado' }
      ]
    }
  },
  methods: {
    sort(column) {
      this.$emit('sort', column)
    },
    money(value) {
      return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0))
    },
    number(value) {
      return new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 }).format(Number(value || 0))
    },
    date(value) {
      if (!value) return '-'
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return String(value)
      return date.toLocaleDateString('pt-BR')
    }
  }
}
</script>

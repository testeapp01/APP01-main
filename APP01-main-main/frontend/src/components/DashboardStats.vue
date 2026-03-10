<template>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="card p-4">
      <div class="text-sm muted">
        Vendas Totais
      </div>
      <div class="text-2xl font-bold">
        R$ {{ totals.sales }}
      </div>
    </div>
    <div class="card p-4">
      <div class="text-sm muted">
        Compras Totais
      </div>
      <div class="text-2xl font-bold">
        R$ {{ totals.purchases }}
      </div>
    </div>
    <div class="card p-4">
      <div class="text-sm muted">
        Clientes Ativos
      </div>
      <div class="text-2xl font-bold">
        {{ totals.clients }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DashboardStats',
  props: { totals: { type: Object, default: () => ({ sales: 0, purchases: 0, clients: 0 }) } }
}
</script>

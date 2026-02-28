<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-semibold">
          Clientes
        </h2>
        <p class="text-sm muted">
          Gerencie os clientes cadastrados
        </p>
      </div>
      <div class="flex items-center gap-2">
        <input
          v-model="localQuery"
          placeholder="Buscar clientes"
          class="p-2 border rounded w-full max-w-sm"
          @input="onSearch"
        >
        <BaseButton
          class="ml-2"
          @click="refresh"
        >
          Atualizar
        </BaseButton>
      </div>
    </div>

    <BaseTable>
      <template #head>
        <tr class="text-sm text-muted">
          <th class="p-2">
            Nome
          </th>
          <th class="p-2">
            Telefone
          </th>
          <th class="p-2">
            Cidade
          </th>
        </tr>
      </template>

      <template #body>
        <tr v-if="store.loading">
          <td
            colspan="3"
            class="p-4"
          >
            Carregando...
          </td>
        </tr>
        <tr
          v-for="c in store.clients"
          v-else
          :key="c.id"
          class="hover:bg-gray-50 dark:hover:bg-slate-800"
        >
          <td class="p-2">
            {{ c.name || c.nome }}
          </td>
          <td class="p-2">
            {{ c.phone || c.telefone }}
          </td>
          <td class="p-2">
            {{ c.city || c.cidade || '-' }}
          </td>
        </tr>
      </template>
    </BaseTable>
  </div>
</template>

<script>
import { ref } from 'vue'
import BaseTable from '../BaseTable.vue'
import BaseButton from '../BaseButton.vue'
import { useClientStore } from '../../stores/clients'

export default {
  name: 'ClientTable',
  components: { BaseTable, BaseButton },
  setup() {
    const store = useClientStore()
    const localQuery = ref(store.query)

    function refresh() {
      store.fetchClients()
    }
    function onSearch() {
      store.setQuery(localQuery.value)
      store.fetchClients()
    }

    store.fetchClients()

    return { store, localQuery, refresh, onSearch }
  }
}
</script>

<style scoped>
.muted { color: rgba(100,116,139,1); }
</style>
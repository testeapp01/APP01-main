<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-semibold">
          Motoristas
        </h2>
        <p class="text-sm muted">
          Gerencie motoristas e viagens
        </p>
      </div>
      <div class="flex items-center gap-2">
        <input
          v-model="localQuery"
          placeholder="Buscar motoristas"
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
            CNH
          </th>
          <th class="p-2">
            Telefone
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
          v-for="d in store.drivers"
          v-else
          :key="d.id"
          class="hover:bg-gray-50 dark:hover:bg-slate-800"
        >
          <td class="p-2">
            {{ d.name || d.nome }}
          </td>
          <td class="p-2">
            {{ d.cnh || '-' }}
          </td>
          <td class="p-2">
            {{ d.phone || d.telefone || '-' }}
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
import { useDriverStore } from '../../stores/drivers'

export default {
  name: 'DriverTable',
  components: { BaseTable, BaseButton },
  setup() {
    const store = useDriverStore()
    const localQuery = ref(store.query)

    function refresh() { store.fetchDrivers() }
    function onSearch() {
      store.setQuery(localQuery.value)
      store.fetchDrivers()
    }

    store.fetchDrivers()

    return { store, localQuery, refresh, onSearch }
  }
}
</script>

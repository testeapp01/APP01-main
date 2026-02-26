<template>
  <aside class="w-64 bg-slate-900 text-white min-h-screen p-4 hidden md:block">
    <div class="mb-6">
      <h2 class="text-xl font-bold">Hortifrut</h2>
      <p class="text-sm text-slate-400">Gestão interna</p>
    </div>

    <nav class="space-y-2">
      <a v-for="item in menuItems" :key="item.label" :href="item.href" 
         class="block px-3 py-2 rounded hover:bg-slate-800 transition relative"
         :class="{ 'bg-slate-700': selectedItem === item.label }"
         @click="selectItem(item.label)">
        {{ item.label }}
        <span v-if="loadingItem === item.label" class="absolute right-2 top-2 h-3 w-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
      </a>
      <button @click="logout" class="w-full text-left block px-3 py-2 rounded hover:bg-slate-800 mt-4 text-sm">Sair</button>
    </nav>
  </aside>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { ref } from 'vue';

export default {
  setup() {
    const auth = useAuthStore();
    const menuItems = [
      { label: 'Dashboard', href: '#/' },
      { label: 'Vendas', href: '#/vendas' },
      { label: 'Compras', href: '#/compras' },
      { label: 'Estoque', href: '#/estoque' },
      { label: 'Clientes', href: '#/clientes' },
      { label: 'Fornecedores', href: '#/fornecedores' },
      { label: 'Motoristas', href: '#/motoristas' },
      { label: 'Relatórios', href: '#/relatorios' },
      { label: 'Configurações', href: '#/configuracoes' },
    ];

    const selectedItem = ref(null);
    const loadingItem = ref(null);

    function selectItem(label) {
      if (loadingItem.value) return; // Prevent multiple clicks
      selectedItem.value = label;
      loadingItem.value = label;
      setTimeout(() => {
        loadingItem.value = null;
      }, 1000); // Simulate loading time
    }

    function logout() {
      auth.clear();
    }

    return { logout, menuItems, selectedItem, loadingItem, selectItem };
  },
};
</script>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

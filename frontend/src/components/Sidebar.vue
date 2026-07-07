<template>
  <aside
    :class="[
      'sidebar app-sidebar-shell w-64 flex flex-col h-full bg-white border-r border-slate-200 overflow-y-auto no-scrollbar transform top-0 left-0 fixed z-30 transition-transform duration-200 md:static',
      isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
    ]"
    :inert="!isOpen && isMobile"
  >
    <div class="h-full flex flex-col">
      <!-- Logo Section -->
      <div class="flex items-center justify-between p-4 border-b border-slate-200">
        <router-link
          to="/"
          class="flex items-center gap-2 hover:opacity-75 transition-opacity"
        >
          <img
            src="/logo-symbol.png"
            alt="Safrion"
            class="h-8 w-8 object-contain"
          >
          <div>
            <div class="text-sm font-bold leading-tight">Safrion</div>
            <div class="text-xs text-slate-400">Workspace</div>
          </div>
        </router-link>
        <button
          class="md:hidden flex items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-100 transition-colors"
          @click="$emit('close')"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="w-5 h-5 text-slate-500"
          >
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        <!-- DASHBOARD (Top-level, no section) -->
        <div class="mb-4">
          <router-link
            to="/"
            class="nav-link"
            active-class="active"
            @click="closeOnMobile"
          >
            <IconSet name="dashboard" :size="20" class="flex-shrink-0" />
            <span>Dashboard</span>
          </router-link>
        </div>

        <!-- CADASTROS -->
        <div>
          <h3 class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Cadastros
          </h3>
          <div class="mt-1 space-y-1">
            <router-link
              to="/clientes"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="clientes" :size="18" class="flex-shrink-0" />
              <span>Clientes</span>
            </router-link>
            <router-link
              to="/fornecedores"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="fornecedores" :size="18" class="flex-shrink-0" />
              <span>Fornecedores</span>
            </router-link>
            <router-link
              to="/produtos"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="produtos" :size="18" class="flex-shrink-0" />
              <span>Produtos</span>
            </router-link>
            <router-link
              to="/motoristas"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="motoristas" :size="18" class="flex-shrink-0" />
              <span>Motoristas</span>
            </router-link>
            <router-link
              to="/usuarios"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="usuarios" :size="18" class="flex-shrink-0" />
              <span>Usuários</span>
            </router-link>
          </div>
        </div>

        <!-- OPERAÇÕES -->
        <div class="mt-6">
          <h3 class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Operações
          </h3>
          <div class="mt-1 space-y-1">
            <router-link
              to="/vendas"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="pedidos" :size="18" class="flex-shrink-0" />
              <span>Vendas</span>
              <span class="ml-auto text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">3</span>
            </router-link>
            <router-link
              to="/compras"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="compras" :size="18" class="flex-shrink-0" />
              <span>Compras</span>
              <span class="ml-auto text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">3</span>
            </router-link>
            <router-link
              to="/estoque"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="estoque" :size="18" class="flex-shrink-0" />
              <span>Estoque</span>
            </router-link>
          </div>
        </div>

        <!-- RELATÓRIOS -->
        <div class="mt-6">
          <h3 class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Relatórios
          </h3>
          <div class="mt-1 space-y-1">
            <router-link
              to="/relatorios"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="relatorios" :size="18" class="flex-shrink-0" />
              <span>Relatórios</span>
            </router-link>
          </div>
        </div>

        <!-- INTEGRAÇÕES -->
        <div class="mt-6">
          <h3 class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Integrações
          </h3>
          <div class="mt-1 space-y-1">
            <router-link
              to="/integracoes"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="integracoes" :size="18" class="flex-shrink-0" />
              <span>Integrações</span>
            </router-link>
          </div>
        </div>

        <!-- CONFIGURAÇÕES -->
        <div class="mt-6">
          <h3 class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Sistema
          </h3>
          <div class="mt-1 space-y-1">
            <router-link
              to="/usuarios-empresas"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="usuarios" :size="18" class="flex-shrink-0" />
              <span>Usuários por Empresa</span>
            </router-link>
            <router-link
              to="/configuracoes"
              class="nav-link"
              active-class="active"
              @click="closeOnMobile"
            >
              <IconSet name="configuracoes" :size="18" class="flex-shrink-0" />
              <span>Configurações</span>
            </router-link>
          </div>
        </div>
      </nav>

      <!-- Footer -->
      <div class="border-t border-slate-200 p-3 space-y-2">
        <button
          class="w-full px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors text-left flex items-center gap-3"
          @click="logout"
        >
          <IconSet name="logout" :size="18" class="flex-shrink-0" />
          <span>Sair</span>
        </button>
      </div>
    </div>
  </aside>
</template>

<script>
import IconSet from './icons/IconSet.vue'

export default {
  name: 'Sidebar',
  components: {
    IconSet
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
    isMobile: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['close'],
  methods: {
    closeOnMobile() {
      if (this.isMobile) {
        this.$emit('close')
      }
    },
    logout() {
      localStorage.removeItem('hf_token')
      localStorage.removeItem('hf_mock_email')
      this.$router.push('/login')
    },
  },
}
</script>

<style scoped>
.nav-link {
  @apply flex items-center gap-3 px-3 py-2.5 text-sm text-slate-600 rounded-lg transition-all duration-200 relative;
}

.nav-link:hover {
  @apply bg-slate-50 text-slate-900;
}

.nav-link.active {
  @apply bg-green-50 text-green-700 font-medium shadow-sm;
}

.nav-link.active::before {
  content: '';
  @apply absolute left-0 top-0 bottom-0 w-1 bg-green-600 rounded-r-full;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>

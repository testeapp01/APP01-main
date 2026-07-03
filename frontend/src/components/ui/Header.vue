<template>
  <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-40 flex items-center px-6 gap-4">
    <!-- Sidebar Toggle (Mobile) -->
    <button
      class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-slate-100 transition-colors"
      @click="$emit('toggle-sidebar')"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        class="w-6 h-6 text-slate-600"
      >
        <line x1="3" y1="6" x2="21" y2="6" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <line x1="3" y1="18" x2="21" y2="18" />
      </svg>
    </button>

    <!-- Breadcrumb -->
    <div class="flex-1 flex items-center gap-2">
      <nav class="flex items-center gap-2 text-sm">
        <a href="/" class="text-slate-500 hover:text-slate-700 transition-colors">Safrion</a>
        <span class="text-slate-300">/</span>
        <span
          v-for="(crumb, idx) in breadcrumbs"
          :key="idx"
          class="flex items-center gap-2"
        >
          <router-link
            v-if="idx < breadcrumbs.length - 1"
            :to="crumb.href"
            class="text-slate-500 hover:text-slate-700 transition-colors"
          >
            {{ crumb.label }}
          </router-link>
          <span v-else class="text-slate-900 font-medium">
            {{ crumb.label }}
          </span>
          <span v-if="idx < breadcrumbs.length - 1" class="text-slate-300">/</span>
        </span>
      </nav>
    </div>

    <!-- Search Global -->
    <button
      class="hidden sm:flex items-center gap-2 px-3 h-10 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors text-sm text-slate-500 hover:text-slate-600 group"
      @click="$emit('open-command-palette')"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        class="w-4 h-4"
      >
        <circle cx="11" cy="11" r="8" />
        <path d="m21 21-4.35-4.35" />
      </svg>
      <span>Buscar...</span>
      <kbd class="ml-auto text-xs px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-400 group-hover:text-slate-500">
        ⌘K
      </kbd>
    </button>

    <!-- Actions -->
    <div class="flex items-center gap-3">
      <!-- Notifications -->
      <div class="relative">
        <button
          class="relative flex items-center justify-center w-10 h-10 rounded-lg hover:bg-slate-100 transition-colors"
          @click="showNotifications = !showNotifications"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="w-6 h-6 text-slate-600"
          >
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
          </svg>
          <span v-if="notificationCount > 0" class="absolute top-1 right-1 flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold">
            {{ notificationCount > 9 ? '9+' : notificationCount }}
          </span>
        </button>

        <!-- Notifications Dropdown -->
        <div
          v-if="showNotifications"
          class="absolute right-0 mt-2 w-80 bg-white rounded-lg border border-slate-200 shadow-lg z-50 overflow-hidden"
        >
          <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Notificações</h3>
          </div>
          <div class="max-h-96 overflow-y-auto">
            <div class="p-4 text-sm text-slate-500 text-center">
              Nenhuma notificação no momento
            </div>
          </div>
        </div>
      </div>

      <!-- Theme Toggle -->
      <button
        class="flex items-center justify-center w-10 h-10 rounded-lg hover:bg-slate-100 transition-colors"
        @click="toggleTheme"
      >
        <svg
          v-if="isDark"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          class="w-5 h-5 text-slate-600"
        >
          <circle cx="12" cy="12" r="5" />
          <path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m3.08 3.08l4.24 4.24M1 12h6m6 0h6m-14.78 7.78l4.24-4.24m3.08-3.08l4.24-4.24" />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          class="w-5 h-5 text-slate-600"
        >
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
      </button>

      <!-- Profile -->
      <div class="relative">
        <button
          class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-green-600 hover:shadow-lg transition-shadow text-white font-semibold"
          @click="showProfile = !showProfile"
        >
          {{ userInitial }}
        </button>

        <!-- Profile Dropdown -->
        <div
          v-if="showProfile"
          class="absolute right-0 mt-2 w-64 bg-white rounded-lg border border-slate-200 shadow-lg z-50 overflow-hidden"
        >
          <div class="p-4 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">{{ userName }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ userEmail }}</div>
          </div>
          <div class="p-2 space-y-1">
            <a
              href="/configuracoes"
              class="block px-3 py-2 text-sm text-slate-700 rounded-lg hover:bg-slate-100 transition-colors"
            >
              ⚙️ Configurações
            </a>
            <button
              class="w-full text-left px-3 py-2 text-sm text-slate-700 rounded-lg hover:bg-slate-100 transition-colors"
              @click="logout"
            >
              🚪 Sair
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'Header',
  emits: ['toggle-sidebar', 'open-command-palette'],
  data() {
    const auth = useAuthStore()
    return {
      showNotifications: false,
      showProfile: false,
      isDark: false,
    }
  },
  computed: {
    breadcrumbs() {
      const breadcrumbs = [
        { label: 'Dashboard', href: '/' },
      ]

      const routePath = this.$route.path
      const routeName = this.$route.name

      const breadcrumbMap = {
        'Vendas': { label: 'Pedidos', href: '/vendas' },
        'Compras': { label: 'Compras', href: '/compras' },
        'Produtos': { label: 'Produtos', href: '/produtos' },
        'Clientes': { label: 'Clientes', href: '/clientes' },
        'Fornecedores': { label: 'Fornecedores', href: '/fornecedores' },
        'Motoristas': { label: 'Motoristas', href: '/motoristas' },
        'Relatorios': { label: 'Relatórios', href: '/relatorios' },
        'Configuracoes': { label: 'Configurações', href: '/configuracoes' },
      }

      if (routeName && breadcrumbMap[routeName]) {
        breadcrumbs.push(breadcrumbMap[routeName])
      }

      return breadcrumbs
    },
    notificationCount() {
      return 0
    },
    userName() {
      return localStorage.getItem('hf_mock_email')?.split('@')[0] || 'Usuário'
    },
    userEmail() {
      return localStorage.getItem('hf_mock_email') || 'user@safrion.store'
    },
    userInitial() {
      return this.userName.charAt(0).toUpperCase()
    },
  },
  methods: {
    toggleTheme() {
      this.isDark = !this.isDark
      document.documentElement.classList.toggle('dark', this.isDark)
      localStorage.setItem('safrion-theme', this.isDark ? 'dark' : 'light')
    },
    logout() {
      localStorage.removeItem('hf_token')
      localStorage.removeItem('hf_mock_email')
      this.$router.push('/login')
    },
  },
  mounted() {
    this.isDark = localStorage.getItem('safrion-theme') === 'dark'
    document.addEventListener('click', (e) => {
      if (!e.target.closest('[data-notifications]')) {
        this.showNotifications = false
      }
      if (!e.target.closest('[data-profile]')) {
        this.showProfile = false
      }
    })
  },
}
</script>

<style scoped>
button {
  user-select: none;
}
</style>

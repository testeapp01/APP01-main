<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-900 flex">
    <!-- Sidebar -->
      <aside
        :class="[ 'sidebar w-64 flex flex-col h-full p-4 overflow-y-auto no-scrollbar transform top-0 left-0 fixed z-30 transition-transform duration-200', sidebarOpen ? 'translate-x-0' : '-translate-x-full', 'md:translate-x-0 md:static md:shadow-none' ]"
        :aria-hidden="!sidebarOpen && isMobile"
      >
        <div class="h-full flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="app-logo">S</div>
              <div>
                <div class="text-lg font-bold">Safrion</div>
                <div class="text-xs muted">v1</div>
              </div>
            </div>
            <button class="md:hidden ml-auto text-slate-500" @click="toggleSidebar" aria-label="Fechar menu">✕</button>
          </div>

          <nav class="flex-1">
            <ul class="space-y-3">
              <li>
                <router-link to="/" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-7 9 7" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V9h6v12" />
                    </svg>
                  </span>
                  <span class="nav-label">Dashboard</span>
                </router-link>
              </li>
              <li>
                <router-link to="/vendas" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7 14v7M12 10v11M17 7v14" />
                    </svg>
                  </span>
                  <span class="nav-label">Vendas</span>
                </router-link>
              </li>
              <li>
                <router-link to="/compras" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7 13h10l3-8H6.4" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7 13l-1 4h13" />
                    </svg>
                  </span>
                  <span class="nav-label">Compras</span>
                </router-link>
              </li>
              <li>
                <router-link to="/estoque" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 16V8l-9-5-9 5v8l9 5 9-5z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18" />
                    </svg>
                  </span>
                  <span class="nav-label">Produtos</span>
                </router-link>
              </li>

              <li>
                <router-link to="/clientes" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-2a4 4 0 00-3-3.87" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7 20v-2a4 4 0 013-3.87" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 7a4 4 0 110-8 4 4 0 010 8z" />
                    </svg>
                  </span>
                  <span class="nav-label">Clientes</span>
                </router-link>
              </li>
              <li>
                <router-link to="/fornecedores" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 7a4 4 0 110-8 4 4 0 010 8z" />
                    </svg>
                  </span>
                  <span class="nav-label">Fornecedores</span>
                </router-link>
              </li>
              <li>
                <router-link to="/motoristas" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-2a4 4 0 00-3-3.87" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7 20v-2a4 4 0 013-3.87" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 7a4 4 0 110-8 4 4 0 010 8z" />
                    </svg>
                  </span>
                  <span class="nav-label">Motoristas</span>
                </router-link>
              </li>
              
              <li>
                <router-link to="/relatorios" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M7 12v5M17 4v13" />
                    </svg>
                  </span>
                  <span class="nav-label">Relatórios</span>
                </router-link>
              </li>
              <li>
                <router-link to="/configuracoes" class="nav-item" @click="closeOnMobile">
                  <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11.983 2.1c.36 0 .712.02 1.055.06l.4 1.5a8 8 0 012.4.9l1.3-.5.9 1.5-1.2 1a8 8 0 01.1 2.3l1.5.4v1.9l-1.5.4a8 8 0 01-.1 2.3l1.2 1-.9 1.5-1.3-.5a8 8 0 01-2.4.9l-.4 1.5a11.9 11.9 0 01-2.11 0l-.4-1.5a8 8 0 01-2.4-.9l-1.3.5-.9-1.5 1.2-1a8 8 0 01-.1-2.3l-1.5-.4v-1.9l1.5-.4a8 8 0 01.1-2.3l-1.2-1 .9-1.5 1.3.5a8 8 0 012.4-.9l.4-1.5c.343-.04.695-.06 1.055-.06z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" />
                    </svg>
                  </span>
                  <span class="nav-label">Configurações</span>
                </router-link>
              </li>
            </ul>
          </nav>
        </div>

        <div class="mt-6 text-sm muted">&copy; 2026 Hortifrut</div>
      </div>
    </aside>

    <!-- Main content area -->
    <div class="flex-1 flex flex-col main-layout">
      <ToastProvider />
      <!-- Backdrop for mobile when sidebar is open -->
      <div v-if="sidebarOpen" class="fixed inset-0 bg-black/40 z-20 md:hidden" @click="toggleSidebar" aria-hidden="true"></div>
      <!-- topbar removed -->

      <main class="flex-1 p-6 content-wrap main-shift">
          <div class="app-container">
          <transition name="fade" mode="out-in">
            <div class="flex-1 border-t border-l border-sidebar-border mt-4 rounded-tl-xl overflow-y-auto bg-background text-foreground p-10">
              <router-view @update:title="updateTitle"></router-view>
            </div>
          </transition>
        </div>
      </main>

      <footer class="p-4 bg-gray-800 text-center">
        <div class="mt-6 text-sm muted">© 2026 Hortifrut</div>
      </footer>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, getCurrentInstance } from 'vue'
import { useAuthStore } from './stores/auth'

export default {
  setup() {
    const auth = useAuthStore()
    const sidebarOpen = ref(false)
    const pageTitle = ref('Dashboard')
    const isMobile = ref(false)

    function updateIsMobile() {
      isMobile.value = (typeof window !== 'undefined') && window.innerWidth < 768
    }

    onMounted(() => {
      updateIsMobile()
      window.addEventListener('resize', updateIsMobile)
    })
    onUnmounted(() => {
      window.removeEventListener('resize', updateIsMobile)
    })

    function toggleSidebar() {
      sidebarOpen.value = !sidebarOpen.value
    }
    function closeOnMobile() {
      if (isMobile.value) sidebarOpen.value = false
    }
    function updateTitle(title) {
      if (title) pageTitle.value = title
    }

    // Theme control
    const theme = ref((typeof window !== 'undefined' && localStorage.getItem('theme')) || 'light')
    function toggleTheme() {
      const gp = getCurrentInstance().appContext.config.globalProperties.$theme
      gp.toggle()
      theme.value = gp.current
    }

    return { auth, sidebarOpen, toggleSidebar, closeOnMobile, pageTitle, updateTitle, isMobile, theme, toggleTheme }
  },
}
</script>

<style>
body {
  font-family: 'Inter', sans-serif;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}

/* ensure the fixed sidebar sits above main content on small screens */
@media (max-width: 767px) {
  aside { width: 16rem; }
}
</style>
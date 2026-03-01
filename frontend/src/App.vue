<template>
  <div
    v-if="isPublicView"
    class="min-h-screen bg-gray-50 dark:bg-slate-900"
  >
    <router-view
      v-slot="{ Component }"
      @update:title="updateTitle"
    >
      <transition
        name="fade"
        mode="out-in"
      >
        <component
          :is="Component"
          :key="$route.fullPath"
        />
      </transition>
    </router-view>
  </div>

  <div
    v-else
    class="min-h-screen bg-gray-50 dark:bg-slate-900 flex"
  >
    <!-- Sidebar -->
    <aside
      :class="[ 'sidebar w-64 flex flex-col h-full p-4 overflow-y-auto no-scrollbar transform top-0 left-0 fixed z-30 transition-transform duration-200', sidebarOpen ? 'translate-x-0' : '-translate-x-full', 'md:translate-x-0 md:static md:shadow-none' ]"
      :aria-hidden="!sidebarOpen && isMobile"
    >
      <div class="h-full flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <img
                src="/brand-logo.svg"
                alt="Safrion"
                class="h-10 w-10 rounded-xl object-contain border border-slate-200 bg-white p-1"
              >
              <div>
                <div class="text-lg font-bold">
                  Safrion
                </div>
                <div class="text-xs muted">
                  v1
                </div>
              </div>
            </div>
            <button
              class="md:hidden ml-auto text-slate-500"
              aria-label="Fechar menu"
              @click="toggleSidebar"
            >
              ✕
            </button>
          </div>

          <nav class="flex-1">
            <ul class="space-y-3">
              <li>
                <router-link
                  to="/"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 12l9-7 9 7"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 21V9h6v12"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Dashboard</span>
                </router-link>
              </li>
              <li>
                <router-link
                  to="/vendas"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 3v18"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7 14v7M12 10v11M17 7v14"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Vendas</span>
                </router-link>
              </li>
              <li>
                <router-link
                  to="/compras"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 3h2l.4 2"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7 13h10l3-8H6.4"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7 13l-1 4h13"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Compras</span>
                </router-link>
              </li>
              <li>
                <router-link
                  to="/estoque"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 16V8l-9-5-9 5v8l9 5 9-5z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3v18"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Produtos</span>
                </router-link>
              </li>

              <li>
                <router-link
                  to="/clientes"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M17 20v-2a4 4 0 00-3-3.87"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7 20v-2a4 4 0 013-3.87"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 7a4 4 0 110-8 4 4 0 010 8z"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Clientes</span>
                </router-link>
              </li>
              <li>
                <router-link
                  to="/fornecedores"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 7a4 4 0 110-8 4 4 0 010 8z"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Fornecedores</span>
                </router-link>
              </li>
              <li>
                <router-link
                  to="/motoristas"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M17 20v-2a4 4 0 00-3-3.87"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7 20v-2a4 4 0 013-3.87"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 7a4 4 0 110-8 4 4 0 010 8z"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Motoristas</span>
                </router-link>
              </li>
              
              <li>
                <router-link
                  to="/relatorios"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 3v18h18"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 7v10M7 12v5M17 4v13"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Relatórios</span>
                </router-link>
              </li>
              <li>
                <router-link
                  to="/configuracoes"
                  class="nav-item"
                  @click="closeOnMobile"
                >
                  <span class="nav-icon">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M11.983 2.1c.36 0 .712.02 1.055.06l.4 1.5a8 8 0 012.4.9l1.3-.5.9 1.5-1.2 1a8 8 0 01.1 2.3l1.5.4v1.9l-1.5.4a8 8 0 01-.1 2.3l1.2 1-.9 1.5-1.3-.5a8 8 0 01-2.4.9l-.4 1.5a11.9 11.9 0 01-2.11 0l-.4-1.5a8 8 0 01-2.4-.9l-1.3.5-.9-1.5 1.2-1a8 8 0 01-.1-2.3l-1.5-.4v-1.9l1.5-.4a8 8 0 01.1-2.3l-1.2-1 .9-1.5 1.3.5a8 8 0 012.4-.9l.4-1.5c.343-.04.695-.06 1.055-.06z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"
                      />
                    </svg>
                  </span>
                  <span class="nav-label">Configurações</span>
                </router-link>
              </li>
            </ul>
          </nav>
        </div>

        <div class="mt-6 text-sm muted">
          &copy; 2026 Safrion
        </div>
      </div>
    </aside>

    <!-- Main content area -->
    <div class="flex-1 flex flex-col main-layout min-w-0">
      <ToastProvider />
      <!-- Backdrop for mobile when sidebar is open -->
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/40 z-20 md:hidden"
        aria-hidden="true"
        @click="toggleSidebar"
      />

      <header class="md:hidden sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-200 px-3 py-2 flex items-center gap-3">
        <button
          class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-300 text-slate-700"
          aria-label="Abrir menu"
          @click="toggleSidebar"
        >
          ☰
        </button>
        <img
          src="/brand-logo.svg"
          alt="Safrion"
          class="h-7 w-7 rounded-md object-contain border border-slate-200 bg-white p-[2px]"
        >
        <div class="text-sm font-semibold text-slate-800 truncate">
          {{ pageTitle }}
        </div>
      </header>

      <main class="flex-1 p-3 md:p-6 content-wrap main-shift min-w-0 w-full">
        <div class="app-container w-full max-w-[1680px] mx-auto space-y-3 md:space-y-4">
          <section class="hidden md:flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div>
              <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">
                Workspace
              </p>
              <p class="text-sm font-semibold text-slate-800">
                {{ pageTitle }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold border border-emerald-200">
                ● Online
              </span>

              <div class="relative">
                <button
                  type="button"
                  class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                  aria-label="Notificações"
                  @click="toggleNotifications"
                >
                  🔔
                  <span
                    v-if="notifications.length"
                    class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                  >
                    {{ notifications.length > 9 ? '9+' : notifications.length }}
                  </span>
                </button>

                <div
                  v-if="notificationsOpen"
                  class="absolute right-0 mt-2 w-[320px] rounded-xl border border-slate-200 bg-white shadow-lg p-2 z-30"
                >
                  <p class="px-2 py-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Notificações
                  </p>
                  <div
                    v-if="!notifications.length"
                    class="px-2 py-3 text-sm text-slate-500"
                  >
                    Sem alertas de envio/entrega.
                  </div>
                  <button
                    v-for="note in notifications"
                    :key="note.key"
                    type="button"
                    class="w-full text-left px-2 py-2 rounded-lg hover:bg-slate-50"
                    @click="goToNotification(note)"
                  >
                    <p class="text-sm font-semibold text-slate-800">{{ note.title }}</p>
                    <p class="text-xs text-slate-500">{{ note.subtitle }}</p>
                  </button>
                </div>
              </div>

              <div class="relative">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                  @click="toggleProfile"
                >
                  <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                    {{ userInitials }}
                  </span>
                  Perfil
                </button>

                <div
                  v-if="profileOpen"
                  class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white shadow-lg p-2 z-30"
                >
                  <p class="px-2 pt-1 text-sm font-semibold text-slate-800">{{ auth.user?.name || 'Usuário' }}</p>
                  <p class="px-2 pb-2 text-xs text-slate-500">Sessão expira em 10 min sem interação.</p>
                  <button
                    type="button"
                    class="w-full text-left px-2 py-2 rounded-lg text-red-600 hover:bg-red-50"
                    @click="logoutNow"
                  >
                    Sair
                  </button>
                </div>
              </div>
            </div>
          </section>
          <div class="app-surface flex-1 border border-sidebar-border md:border-l md:border-t rounded-xl md:rounded-2xl bg-background text-foreground p-4 sm:p-6 md:p-8">
            <router-view
              v-slot="{ Component }"
              @update:title="updateTitle"
            >
              <transition
                name="fade"
                mode="out-in"
              >
                <component
                  :is="Component"
                  :key="$route.fullPath"
                />
              </transition>
            </router-view>
          </div>
        </div>
      </main>

      <footer class="px-4 py-3 bg-white/85 border-t border-slate-200 text-center">
        <div class="text-xs text-slate-500">
          © 2026 Safrion
        </div>
      </footer>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, getCurrentInstance, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import api from './services/api'

export default {
  setup() {
    const auth = useAuthStore()
    const route = useRoute()
    const sidebarOpen = ref(false)
    const pageTitle = ref('Dashboard')
    const isMobile = ref(false)
    const isPublicView = computed(() => route.path === '/login' || route.path === '/sessao-expirada')
    const profileOpen = ref(false)
    const notificationsOpen = ref(false)
    const notifications = ref([])
    const lastInteractionAt = ref(Date.now())
    const idleLimitMs = 10 * 60 * 1000
    let idleTimer = null
    let notificationsTimer = null

    const userInitials = computed(() => {
      const name = auth.user?.name || 'Usuário'
      return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('') || 'US'
    })

    const formatDate = (value) => {
      if (!value) return '-'
      const [year, month, day] = String(value).slice(0, 10).split('-')
      return year && month && day ? `${day}/${month}/${year}` : value
    }

    const daysUntil = (value) => {
      if (!value) return Number.POSITIVE_INFINITY
      const target = new Date(`${String(value).slice(0, 10)}T23:59:59`)
      const now = new Date()
      return Math.floor((target.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    }

    const noteSubtitle = (label, date, days) => {
      if (days < 0) return `${label} atrasado (${formatDate(date)})`
      if (days === 0) return `${label} hoje (${formatDate(date)})`
      return `${label} em ${days} dia(s) (${formatDate(date)})`
    }

    const markInteraction = () => {
      lastInteractionAt.value = Date.now()
    }

    const buildNotifications = (items, kind) => {
      const mapped = []
      for (const row of items || []) {
        if (!row?.id) continue
        const envioDays = daysUntil(row.data_envio_prevista)
        if (Number.isFinite(envioDays) && envioDays <= 2) {
          mapped.push({
            key: `${kind}-${row.id}-envio`,
            route: kind === 'Compra' ? '/compras' : '/vendas',
            title: `${kind} #${row.id} • Envio`,
            subtitle: noteSubtitle('Envio previsto', row.data_envio_prevista, envioDays),
            days: envioDays,
          })
        }

        const entregaDays = daysUntil(row.data_entrega_prevista)
        if (Number.isFinite(entregaDays) && entregaDays <= 2) {
          mapped.push({
            key: `${kind}-${row.id}-entrega`,
            route: kind === 'Compra' ? '/compras' : '/vendas',
            title: `${kind} #${row.id} • Entrega`,
            subtitle: noteSubtitle('Entrega prevista', row.data_entrega_prevista, entregaDays),
            days: entregaDays,
          })
        }
      }
      return mapped
    }

    const loadNotifications = async () => {
      if (isPublicView.value || !auth.token) {
        notifications.value = []
        return
      }
      try {
        const [comprasRes, vendasRes] = await Promise.all([
          api.get('/api/v1/compras', { params: { page: 1, per_page: 100 } }),
          api.get('/api/v1/vendas', { params: { page: 1, per_page: 100 } }),
        ])
        const compraItems = buildNotifications(comprasRes.data?.items || [], 'Compra')
        const vendaItems = buildNotifications(vendasRes.data?.items || [], 'Venda')
        notifications.value = [...compraItems, ...vendaItems]
          .sort((a, b) => a.days - b.days)
          .slice(0, 12)
      } catch {
        notifications.value = []
      }
    }

    function updateIsMobile() {
      isMobile.value = (typeof window !== 'undefined') && window.innerWidth < 768
    }

    onMounted(() => {
      updateIsMobile()
      window.addEventListener('resize', updateIsMobile)
      ;['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach((evt) => {
        window.addEventListener(evt, markInteraction, { passive: true })
      })
      idleTimer = window.setInterval(() => {
        if (isPublicView.value || !auth.token) return
        if (Date.now() - lastInteractionAt.value >= idleLimitMs) {
          auth.expireSession()
        }
      }, 30000)
      loadNotifications()
      notificationsTimer = window.setInterval(loadNotifications, 60000)
    })
    onUnmounted(() => {
      window.removeEventListener('resize', updateIsMobile)
      ;['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach((evt) => {
        window.removeEventListener(evt, markInteraction)
      })
      if (idleTimer) window.clearInterval(idleTimer)
      if (notificationsTimer) window.clearInterval(notificationsTimer)
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

    function toggleProfile() {
      profileOpen.value = !profileOpen.value
      if (profileOpen.value) notificationsOpen.value = false
    }

    function toggleNotifications() {
      notificationsOpen.value = !notificationsOpen.value
      if (notificationsOpen.value) profileOpen.value = false
    }

    function logoutNow() {
      auth.clear(true)
    }

    function goToNotification(note) {
      profileOpen.value = false
      notificationsOpen.value = false
      if (note?.route) {
        markInteraction()
        window.location.assign(note.route)
      }
    }

    watch(
      () => route.fullPath,
      () => {
        closeOnMobile()
        profileOpen.value = false
        notificationsOpen.value = false
        loadNotifications()
      }
    )

    watch(
      () => sidebarOpen.value,
      (open) => {
        if (typeof document !== 'undefined' && isMobile.value) {
          document.body.style.overflow = open ? 'hidden' : ''
        }
      }
    )

    // Theme control
    const theme = ref((typeof window !== 'undefined' && localStorage.getItem('theme')) || 'light')
    function toggleTheme() {
      const gp = getCurrentInstance().appContext.config.globalProperties.$theme
      gp.toggle()
      theme.value = gp.current
    }

    return {
      auth,
      sidebarOpen,
      toggleSidebar,
      closeOnMobile,
      pageTitle,
      updateTitle,
      isMobile,
      theme,
      toggleTheme,
      isPublicView,
      profileOpen,
      notificationsOpen,
      notifications,
      userInitials,
      toggleProfile,
      toggleNotifications,
      logoutNow,
      goToNotification,
    }
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
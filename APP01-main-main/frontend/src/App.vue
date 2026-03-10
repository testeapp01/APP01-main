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

    <footer class="px-4 py-3 text-center">
      <div class="text-xs text-slate-500">
        © 2026 Safrion. Todos os direitos reservados.
      </div>
    </footer>
  </div>

  <div
    v-else
    class="min-h-screen app-shell flex"
  >
    <!-- Sidebar -->
    <aside
      ref="sidebarEl"
      :class="[ 'sidebar app-sidebar-shell w-64 flex flex-col h-full p-4 overflow-y-auto no-scrollbar transform top-0 left-0 fixed z-30 transition-transform duration-200', sidebarOpen ? 'translate-x-0' : '-translate-x-full', 'md:translate-x-0 md:static md:shadow-none' ]"
      :inert="!sidebarOpen && isMobile"
    >
      <div class="h-full flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <img
                src="/logo-symbol.png"
                alt="Safrion"
                class="h-11 w-11 object-contain drop-shadow-[0_2px_6px_rgba(15,23,42,0.18)]"
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
              class="md:hidden ml-auto inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-300 text-slate-500 bg-white/90"
              aria-label="Fechar menu"
              @click="toggleSidebar"
            >
              ✕
            </button>
          </div>

          <nav class="flex-1">
            <ul class="space-y-3">
              <li class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                Geral
              </li>
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

              <li class="my-1 border-t border-slate-200" />
              <li class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                Operações
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
                  to="/produtos"
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

              <li class="my-1 border-t border-slate-200" />
              <li class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                Cadastros
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

              <li class="my-1 border-t border-slate-200" />
              <li class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                Gestão
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
    <div class="flex-1 flex flex-col main-layout app-main-shell min-w-0">
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
          ref="menuButtonEl"
          class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-300 text-slate-700"
          aria-label="Abrir menu"
          @click="toggleSidebar"
        >
          ☰
        </button>
        <img
          src="/logo-symbol.png"
          alt="Safrion"
          class="h-8 w-8 object-contain drop-shadow-[0_1px_4px_rgba(15,23,42,0.2)]"
        >
        <div class="text-sm font-semibold text-slate-800 truncate">
          {{ pageTitle }}
        </div>
      </header>

      <main class="flex-1 p-3 md:p-6 content-wrap main-shift min-w-0 w-full">
        <div class="app-container w-full max-w-[1680px] mx-auto space-y-3 md:space-y-4">
          <section class="hidden md:flex items-center justify-between workspace-strip px-4 py-3">
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

              <div
                ref="notificationsMenuEl"
                class="relative notifications-anchor"
              >
                <button
                  ref="notificationsButtonEl"
                  type="button"
                  class="notif-trigger"
                  aria-label="Notificações"
                  :aria-expanded="notificationsOpen ? 'true' : 'false'"
                  @click="toggleNotifications"
                >
                  <span
                    class="notif-trigger-icon"
                    aria-hidden="true"
                  >
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        d="M14.5 18a2.5 2.5 0 0 1-5 0"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                      <path
                        d="M18.5 16h-13c1.4-1.2 2.2-2.95 2.2-4.8V9.7a4.3 4.3 0 1 1 8.6 0v1.5c0 1.85.8 3.6 2.2 4.8Z"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </span>
                  <span
                    v-if="unreadNotificationsCount"
                    class="notif-badge"
                  >
                    {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
                  </span>
                </button>

                <div
                  v-if="notificationsOpen"
                  ref="notificationsPanelEl"
                  class="notifications-panel"
                  role="dialog"
                  aria-label="Central de notificações"
                >
                  <div class="notifications-panel-header">
                    <div>
                      <p class="notifications-title">
                        Central de alertas
                      </p>
                      <p class="notifications-subtitle">
                        Envios e entregas próximos do prazo
                      </p>
                    </div>
                    <button
                      v-if="notifications.length"
                      type="button"
                      class="notifications-action"
                      @click="markNotificationsAsRead"
                    >
                      Limpar alerta
                    </button>
                  </div>

                  <div
                    v-if="!notifications.length"
                    class="notifications-empty"
                  >
                    Nenhum alerta pendente no momento.
                  </div>

                  <div
                    v-else
                    class="notifications-scroll"
                  >
                    <button
                      v-for="note in notifications"
                      :key="note.key"
                      type="button"
                      class="notification-note"
                      :class="[
                        `tone-${note.tone}`,
                        { 'is-read': isNotificationRead(note) }
                      ]"
                      @click="goToNotification(note)"
                    >
                      <span
                        class="notification-note-dot"
                        aria-hidden="true"
                      />
                      <div class="notification-note-body">
                        <p class="notification-note-title">
                          {{ note.title }}
                        </p>
                        <p class="notification-note-subtitle">
                          {{ note.subtitle }}
                        </p>
                      </div>
                      <span class="notification-note-chip">
                        {{ note.tag }}
                      </span>
                    </button>
                  </div>
                </div>
              </div>

              <div
                ref="profileMenuEl"
                class="relative"
              >
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
                  <p class="px-2 pt-1 text-sm font-semibold text-slate-800">
                    {{ auth.user?.name || 'Usuário' }}
                  </p>
                  <p class="px-2 pb-2 text-xs text-slate-500">
                    Sessão expira em 10 min sem interação.
                  </p>
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
          <div class="app-surface flex-1 p-4 sm:p-6 md:p-8">
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
          © 2026 Safrion. Todos os direitos reservados.
        </div>
      </footer>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, getCurrentInstance, watch, computed, nextTick } from 'vue'
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
    const sidebarEl = ref(null)
    const menuButtonEl = ref(null)
    const profileOpen = ref(false)
    const profileMenuEl = ref(null)
    const notificationsOpen = ref(false)
    const notificationsMenuEl = ref(null)
    const notificationsButtonEl = ref(null)
    const notificationsPanelEl = ref(null)
    const notifications = ref([])
    const readNotificationKeys = ref({})
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

    const noteMeta = (days) => {
      if (days < 0) return { tone: 'danger', tag: 'Atrasado' }
      if (days === 0) return { tone: 'warning', tag: 'Hoje' }
      return { tone: 'info', tag: 'Próximo' }
    }

    const unreadNotificationsCount = computed(() => {
      return notifications.value.reduce((total, note) => {
        return total + (readNotificationKeys.value[note.key] ? 0 : 1)
      }, 0)
    })

    const markInteraction = () => {
      lastInteractionAt.value = Date.now()
    }

    const isNotificationRead = (note) => {
      if (!note?.key) return true
      return !!readNotificationKeys.value[note.key]
    }

    const pruneReadNotifications = (items) => {
      const activeKeys = new Set((items || []).map((note) => note.key).filter(Boolean))
      const nextRead = {}
      for (const key of Object.keys(readNotificationKeys.value)) {
        if (activeKeys.has(key)) {
          nextRead[key] = true
        }
      }
      readNotificationKeys.value = nextRead
    }

    const markNotificationsAsRead = (items = notifications.value) => {
      const nextRead = { ...readNotificationKeys.value }
      let changed = false
      for (const note of items || []) {
        if (!note?.key) continue
        if (!nextRead[note.key]) {
          nextRead[note.key] = true
          changed = true
        }
      }
      if (changed) {
        readNotificationKeys.value = nextRead
      }
    }

    const buildNotifications = (items, kind) => {
      const mapped = []
      for (const row of items || []) {
        if (!row?.id) continue
        const envioDays = daysUntil(row.data_envio_prevista)
        if (Number.isFinite(envioDays) && envioDays <= 2) {
          const meta = noteMeta(envioDays)
          mapped.push({
            key: `${kind}-${row.id}-envio`,
            route: kind === 'Compra' ? '/compras' : '/vendas',
            title: `${kind} #${row.id} • Envio`,
            subtitle: noteSubtitle('Envio previsto', row.data_envio_prevista, envioDays),
            days: envioDays,
            tone: meta.tone,
            tag: meta.tag,
          })
        }

        const entregaDays = daysUntil(row.data_entrega_prevista)
        if (Number.isFinite(entregaDays) && entregaDays <= 2) {
          const meta = noteMeta(entregaDays)
          mapped.push({
            key: `${kind}-${row.id}-entrega`,
            route: kind === 'Compra' ? '/compras' : '/vendas',
            title: `${kind} #${row.id} • Entrega`,
            subtitle: noteSubtitle('Entrega prevista', row.data_entrega_prevista, entregaDays),
            days: entregaDays,
            tone: meta.tone,
            tag: meta.tag,
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
        const merged = [...compraItems, ...vendaItems]
          .sort((a, b) => a.days - b.days)
          .slice(0, 12)
        notifications.value = merged
        pruneReadNotifications(merged)
      } catch {
        notifications.value = []
        readNotificationKeys.value = {}
      }
    }

    const handleOutsideMenus = (event) => {
      if (typeof document === 'undefined') return
      const target = event.target
      if (!(target instanceof Node)) return

      if (notificationsOpen.value && notificationsMenuEl.value && !notificationsMenuEl.value.contains(target)) {
        notificationsOpen.value = false
      }

      if (profileOpen.value && profileMenuEl.value && !profileMenuEl.value.contains(target)) {
        profileOpen.value = false
      }
    }

    const handleEscapeKey = (event) => {
      if (event.key !== 'Escape') return
      notificationsOpen.value = false
      profileOpen.value = false
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
      window.addEventListener('pointerdown', handleOutsideMenus)
      window.addEventListener('keydown', handleEscapeKey)
      loadNotifications()
      notificationsTimer = window.setInterval(loadNotifications, 60000)
    })
    onUnmounted(() => {
      window.removeEventListener('resize', updateIsMobile)
      ;['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach((evt) => {
        window.removeEventListener(evt, markInteraction)
      })
      window.removeEventListener('pointerdown', handleOutsideMenus)
      window.removeEventListener('keydown', handleEscapeKey)
      if (idleTimer) window.clearInterval(idleTimer)
      if (notificationsTimer) window.clearInterval(notificationsTimer)
    })

    const returnFocusToMenuIfNeeded = () => {
      if (!isMobile.value || sidebarOpen.value) return
      const active = typeof document !== 'undefined' ? document.activeElement : null
      if (active && sidebarEl.value && sidebarEl.value.contains(active)) {
        menuButtonEl.value?.focus()
      }
    }

    function toggleSidebar() {
      sidebarOpen.value = !sidebarOpen.value
      if (!sidebarOpen.value) {
        nextTick(returnFocusToMenuIfNeeded)
      }
    }
    function closeOnMobile() {
      if (isMobile.value) {
        sidebarOpen.value = false
        nextTick(returnFocusToMenuIfNeeded)
      }
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
      if (notificationsOpen.value) {
        profileOpen.value = false
        markNotificationsAsRead()
      }
    }

    function logoutNow() {
      auth.clear(true)
    }

    function goToNotification(note) {
      profileOpen.value = false
      notificationsOpen.value = false
      markNotificationsAsRead([note])
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
      sidebarEl,
      menuButtonEl,
      profileOpen,
      profileMenuEl,
      notificationsOpen,
      notificationsMenuEl,
      notificationsButtonEl,
      notificationsPanelEl,
      notifications,
      unreadNotificationsCount,
      isNotificationRead,
      markNotificationsAsRead,
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
  font-family: 'Manrope', ui-sans-serif, system-ui, -apple-system;
}

.app-shell {
  position: relative;
  isolation: isolate;
  background: radial-gradient(circle at 20% 10%, rgba(16, 185, 129, 0.1), transparent 34%),
    radial-gradient(circle at 85% 16%, rgba(14, 165, 233, 0.1), transparent 32%),
    linear-gradient(160deg, #f8fafc 0%, #ecfdf5 54%, #eef2ff 100%);
}

.app-sidebar-shell {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.88));
  border-right: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 8px 0 30px rgba(15, 23, 42, 0.05);
  backdrop-filter: blur(8px);
}

.app-main-shell {
  position: relative;
}

.workspace-strip {
  border-radius: 16px;
  border: 1px solid rgba(148, 163, 184, 0.24);
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.75));
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(7px);
}

.app-surface {
  border: 1px solid rgba(148, 163, 184, 0.24);
  border-radius: 20px;
  background: linear-gradient(160deg, rgba(255, 255, 255, 0.94), rgba(255, 255, 255, 0.86));
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.07);
  color: #0f172a;
  backdrop-filter: blur(8px);
}

.notifications-anchor {
  position: relative;
  z-index: 35;
}

.notif-trigger {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.85rem;
  border: 1px solid rgba(148, 163, 184, 0.28);
  background: linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.9));
  color: #0f172a;
  box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
  transition: transform 0.15s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.notif-trigger:hover {
  transform: translateY(-1px);
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 14px 24px rgba(16, 185, 129, 0.16);
}

.notif-trigger:focus-visible {
  outline: 2px solid rgba(16, 185, 129, 0.5);
  outline-offset: 2px;
}

.notif-trigger-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 0.7rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #047857;
  background: linear-gradient(150deg, rgba(16, 185, 129, 0.16), rgba(5, 150, 105, 0.08));
}

.notif-trigger-icon svg {
  width: 1.2rem;
  height: 1.2rem;
}

.notif-badge {
  position: absolute;
  top: -0.4rem;
  right: -0.45rem;
  min-width: 1.25rem;
  height: 1.25rem;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.3rem;
  border: 2px solid #ffffff;
  background: linear-gradient(140deg, #ef4444, #dc2626);
  color: #ffffff;
  font-size: 0.66rem;
  font-weight: 800;
  line-height: 1;
}

.notifications-panel {
  position: absolute;
  right: 0;
  margin-top: 0.7rem;
  width: min(24rem, calc(100vw - 2rem));
  border-radius: 1rem;
  border: 1px solid rgba(148, 163, 184, 0.26);
  background: linear-gradient(160deg, rgba(255, 255, 255, 0.97), rgba(248, 250, 252, 0.94));
  box-shadow: 0 28px 48px rgba(15, 23, 42, 0.16);
  overflow: hidden;
  z-index: 50;
}

.notifications-panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.85rem 0.9rem 0.65rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
  background: linear-gradient(180deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0));
}

.notifications-title {
  margin: 0;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #065f46;
}

.notifications-subtitle {
  margin: 0.15rem 0 0;
  font-size: 0.72rem;
  color: #64748b;
}

.notifications-action {
  border: 0;
  background: transparent;
  color: #0f766e;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.notifications-action:hover {
  color: #065f46;
}

.notifications-empty {
  padding: 1rem;
  font-size: 0.86rem;
  color: #64748b;
}

.notifications-scroll {
  max-height: 22rem;
  overflow-y: auto;
  padding: 0.65rem;
  display: grid;
  gap: 0.5rem;
}

.notification-note {
  width: 100%;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.8rem;
  background: #ffffff;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.65rem;
  align-items: center;
  padding: 0.58rem 0.62rem;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.15s ease;
}

.notification-note:hover {
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
  transform: translateY(-1px);
}

.notification-note.is-read {
  opacity: 0.72;
}

.notification-note-dot {
  width: 0.62rem;
  height: 0.62rem;
  border-radius: 9999px;
  background: #22c55e;
}

.notification-note-body {
  min-width: 0;
}

.notification-note-title {
  margin: 0;
  color: #0f172a;
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notification-note-subtitle {
  margin: 0.15rem 0 0;
  color: #64748b;
  font-size: 0.74rem;
  line-height: 1.25;
}

.notification-note-chip {
  border-radius: 9999px;
  padding: 0.18rem 0.45rem;
  font-size: 0.64rem;
  font-weight: 700;
  line-height: 1;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border: 1px solid rgba(148, 163, 184, 0.28);
  color: #334155;
  background: rgba(248, 250, 252, 0.95);
}

.notification-note.tone-danger .notification-note-dot {
  background: #ef4444;
}

.notification-note.tone-danger .notification-note-chip {
  color: #991b1b;
  border-color: rgba(248, 113, 113, 0.4);
  background: rgba(254, 226, 226, 0.9);
}

.notification-note.tone-warning .notification-note-dot {
  background: #f59e0b;
}

.notification-note.tone-warning .notification-note-chip {
  color: #92400e;
  border-color: rgba(251, 191, 36, 0.45);
  background: rgba(254, 243, 199, 0.9);
}

.notification-note.tone-info .notification-note-dot {
  background: #0ea5e9;
}

.notification-note.tone-info .notification-note-chip {
  color: #155e75;
  border-color: rgba(56, 189, 248, 0.4);
  background: rgba(224, 242, 254, 0.9);
}

.nav-item {
  border: 1px solid transparent;
}

.nav-item:hover {
  border-color: rgba(16, 185, 129, 0.2);
}

.nav-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 10px;
  background: rgba(16, 185, 129, 0.1);
  color: #047857;
}

.nav-label {
  font-weight: 600;
}

.router-link-active .nav-icon,
.nav-item.active .nav-icon {
  background: linear-gradient(145deg, #10b981, #059669);
  color: #ffffff;
  box-shadow: 0 8px 18px rgba(16, 185, 129, 0.25);
}

.router-link-active .nav-label,
.nav-item.active .nav-label {
  color: #065f46;
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
  .app-surface {
    border-radius: 14px;
  }

  .main-layout > header {
    padding-top: max(0.5rem, env(safe-area-inset-top));
  }

  .main-layout > footer {
    padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
  }
}
</style>
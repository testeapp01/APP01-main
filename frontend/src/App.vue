<template>
  <!-- Login/Auth View -->
  <div
    v-if="isPublicView"
    class="min-h-screen bg-slate-50"
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

  <!-- Main App View -->
  <div
    v-else
    class="min-h-screen bg-slate-50 flex flex-col"
  >
    <!-- Header -->
    <Header
      @toggle-sidebar="sidebarOpen = !sidebarOpen"
      @open-command-palette="$refs.commandPalette?.open()"
    />

    <!-- Main Container -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar -->
      <Sidebar
        :is-open="sidebarOpen"
        :is-mobile="isMobile"
        @close="sidebarOpen = false"
      />

      <!-- Content Area -->
      <main class="flex-1 overflow-y-auto">
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
      </main>
    </div>

    <!-- Toast Provider -->
    <ToastProvider />

    <!-- Command Palette -->
    <CommandPalette
      ref="commandPalette"
      @execute-action="handleCommandAction"
    />
  </div>
</template>

<script>
import Header from './components/ui/Header.vue'
import Sidebar from './components/Sidebar.vue'
import ToastProvider from './components/ui/ToastProvider.vue'
import CommandPalette from './components/ui/CommandPalette.vue'

export default {
  name: 'App',
  components: {
    Header,
    Sidebar,
    ToastProvider,
    CommandPalette,
  },
  data() {
    return {
      sidebarOpen: false,
      isMobile: false,
    }
  },
  computed: {
    isPublicView() {
      return this.$route.meta?.publicOnly === true
    },
  },
  methods: {
    updateTitle() {
      // Title updated via route meta
    },
    handleCommandAction(action) {
      // Handle command palette actions
      if (action === 'toggleTheme') {
        document.documentElement.classList.toggle('dark')
        localStorage.setItem('safrion-theme', 
          document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        )
      } else if (action === 'logout') {
        const { useAuthStore } = require('./stores/auth')
        useAuthStore().logout()
      }
    },
    handleKeyboardShortcuts(e) {
      // Cmd+D = Dashboard
      if ((e.metaKey || e.ctrlKey) && e.key === 'd') {
        e.preventDefault()
        this.$router.push('/')
      }
      // Cmd+O = Orders/Pedidos
      if ((e.metaKey || e.ctrlKey) && e.key === 'o') {
        e.preventDefault()
        this.$router.push('/vendas')
      }
      // Cmd+P = Purchases/Compras
      if ((e.metaKey || e.ctrlKey) && e.key === 'p') {
        e.preventDefault()
        this.$router.push('/compras')
      }
    },
    checkMobile() {
      this.isMobile = window.innerWidth < 768
      if (!this.isMobile) {
        this.sidebarOpen = false
      }
    },
  },
  watch: {
    $route() {
      this.sidebarOpen = false
    },
  },
  mounted() {
    window.addEventListener('resize', this.checkMobile)
    this.checkMobile()

    // Load theme preference
    const savedTheme = localStorage.getItem('safrion-theme')
    if (savedTheme === 'dark') {
      document.documentElement.classList.add('dark')
    }

    // Add keyboard shortcuts
    window.addEventListener('keydown', this.handleKeyboardShortcuts)
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.checkMobile)
    window.removeEventListener('keydown', this.handleKeyboardShortcuts)
  },
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 150ms ease-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

main {
  scrollbar-gutter: stable;
}
</style>

<style>
:root {
  --leaf-500: #16A34A;
  --leaf-600: #0F766E;
  --bg-surface: #F8FAFC;
  --card-bg: #FFFFFF;
  --muted: #64748B;
  --border: #E2E8F0;
  --shadow-soft: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-card: 0 4px 12px rgba(0, 0, 0, 0.08);
  --shadow-hover: 0 12px 24px rgba(0, 0, 0, 0.12);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
}

* {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  margin: 0;
  padding: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background-color: #f8fafc;
}

h1, h2, h3, h4, h5, h6 {
  margin: 0;
  font-weight: 600;
}

button {
  cursor: pointer;
  border: none;
  font-family: inherit;
}

input, textarea, select {
  font-family: inherit;
}

/* Prevent layout shift when scrollbar appears */
html {
  overflow-y: scroll;
}

/* Remove default list styles */
ul, ol {
  list-style: none;
  margin: 0;
  padding: 0;
}

a {
  text-decoration: none;
  color: inherit;
}
</style>

<template>
  <Teleport to="body">
    <transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[101] bg-black/50"
        @click="close()"
      >
        <div
          class="fixed left-1/2 top-1/4 -translate-x-1/2 w-full max-w-lg"
          @click.stop
        >
          <div class="bg-white rounded-lg shadow-xl border border-slate-200">
            <input
              ref="searchInput"
              v-model="query"
              type="text"
              placeholder="Buscar ações, páginas..."
              class="w-full px-4 py-3 outline-none text-sm border-b border-slate-200"
              @keydown="onKeyDown"
            >

            <div v-if="filteredResults.length > 0" class="max-h-96 overflow-y-auto">
              <div v-for="(group, groupIdx) in groupedResults" :key="group.category">
                <div class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase bg-slate-50">
                  {{ group.category }}
                </div>
                <button
                  v-for="(item, itemIdx) in group.items"
                  :key="item.label"
                  :class="[
                    'w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors border-l-2',
                    calculateIndex(groupIdx, itemIdx) === selectedIndex
                      ? 'bg-green-50 border-l-green-600 text-slate-900'
                      : 'border-l-transparent text-slate-700 hover:bg-slate-50'
                  ]"
                  @click="selectItem(item)"
                >
                  <IconSet :name="item.icon" :size="18" class="flex-shrink-0" />
                  <span class="flex-1">{{ item.label }}</span>
                  <span class="text-xs text-slate-400">{{ item.shortcut }}</span>
                </button>
              </div>
            </div>

            <div v-else class="px-4 py-8 text-center text-slate-500 text-sm">
              Nenhum resultado
            </div>

            <div class="text-xs text-slate-500 px-3 py-2 border-t border-slate-200 flex justify-center gap-4 bg-slate-50">
              <span>↵ Selecionar</span>
              <span>↑↓ Navegar</span>
              <span>Esc Fechar</span>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script>
import IconSet from '../icons/IconSet.vue'

export default {
  name: 'CommandPalette',
  components: {
    IconSet
  },
  emits: ['execute-action'],
  data() {
    return {
      isOpen: false,
      query: '',
      selectedIndex: 0,
      allCommands: [
        { label: 'Dashboard', category: 'Navegação', icon: 'dashboard', href: '/', shortcut: '⌘+D' },
        { label: 'Pedidos', category: 'Navegação', icon: 'pedidos', href: '/vendas', shortcut: '⌘+O' },
        { label: 'Compras', category: 'Navegação', icon: 'compras', href: '/compras', shortcut: '⌘+P' },
        { label: 'Produtos', category: 'Navegação', icon: 'produtos', href: '/produtos', shortcut: '⌘+T' },
        { label: 'Clientes', category: 'Navegação', icon: 'clientes', href: '/clientes' },
        { label: 'Fornecedores', category: 'Navegação', icon: 'fornecedores', href: '/fornecedores' },
        { label: 'Categorias', category: 'Navegação', icon: 'categorias', href: '/categorias' },
        { label: 'Relatórios', category: 'Navegação', icon: 'relatorios', href: '/relatorios' },
        { label: 'Configurações', category: 'Navegação', icon: 'configuracoes', href: '/configuracoes' },
        { label: 'Modo Escuro', category: 'Sistema', icon: 'tema', action: 'toggleTheme', shortcut: '⌘+I' },
        { label: 'Sair', category: 'Sistema', icon: 'logout', action: 'logout' },
      ],
    }
  },
  computed: {
    filteredResults() {
      if (!this.query.trim()) {
        return this.allCommands
      }
      const q = this.query.toLowerCase()
      return this.allCommands.filter(cmd =>
        cmd.label.toLowerCase().includes(q) ||
        cmd.category.toLowerCase().includes(q)
      )
    },
    groupedResults() {
      const groups = {}
      this.filteredResults.forEach(item => {
        if (!groups[item.category]) {
          groups[item.category] = { category: item.category, items: [] }
        }
        groups[item.category].items.push(item)
      })
      return Object.values(groups)
    },
    flattenedResults() {
      return this.groupedResults.flatMap(g => g.items)
    },
  },
  methods: {
    open() {
      this.isOpen = true
      this.query = ''
      this.selectedIndex = 0
      this.$nextTick(() => {
        this.$refs.searchInput?.focus()
      })
    },
    close() {
      this.isOpen = false
    },
    onKeyDown(e) {
      if (e.key === 'Escape') {
        this.close()
      } else if (e.key === 'ArrowDown') {
        e.preventDefault()
        this.selectedIndex = Math.min(this.selectedIndex + 1, (this.flattenedResults?.length || 0) - 1)
      } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        this.selectedIndex = Math.max(this.selectedIndex - 1, 0)
      } else if (e.key === 'Enter') {
        e.preventDefault()
        const item = this.flattenedResults[this.selectedIndex]
        if (item) {
          this.selectItem(item)
        }
      }
    },
    calculateIndex(groupIdx, itemIdx) {
      let index = 0
      for (let i = 0; i < groupIdx; i++) {
        if (this.groupedResults[i]) {
          index += this.groupedResults[i].items.length
        }
      }
      return index + itemIdx
    },
    selectItem(item) {
      if (item.href) {
        this.$router.push(item.href)
      } else if (item.action) {
        this.$emit('execute-action', item.action)
      }
      this.close()
    },
  },
  mounted() {
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        this.open()
      }
    })
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
</style>

<template>
  <div
    class="relative inline-block text-left"
    @click.stop
  >
    <button
      ref="triggerRef"
      type="button"
      class="h-9 w-9 rounded-lg border border-slate-300 bg-white text-slate-700 shadow-sm hover:border-slate-400 hover:bg-slate-50 hover:shadow-md hover:text-slate-900 transition-all font-bold"
      aria-label="Abrir ações"
      @click.stop="toggle"
    >
      ⋯
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        class="fixed z-[9999] w-48 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
        :style="menuStyle"
        @click.stop
      >
        <button
          v-for="item in visibleItems"
          :key="item.key"
          type="button"
          :class="[
            'block w-full px-3 py-2.5 text-left text-sm font-medium transition-colors',
            item.danger ? 'text-red-600 hover:bg-red-50' : 'text-slate-700 hover:bg-slate-50'
          ]"
          @click="select(item.key)"
        >
          {{ item.label }}
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script>
export default {
  name: 'ActionDropdown',
  props: {
    items: {
      type: Array,
      default: () => [],
    },
    menuHeight: {
      type: Number,
      default: 220,
    },
  },
  emits: ['select'],
  data() {
    return {
      open: false,
      menuStyle: {},
    }
  },
  computed: {
    visibleItems() {
      return (this.items || []).filter(item => !item.hidden)
    },
  },
  mounted() {
    document.addEventListener('click', this.onDocumentClick)
    window.addEventListener('scroll', this.onDocumentClick, true)
    window.addEventListener('resize', this.onDocumentClick)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onDocumentClick)
    window.removeEventListener('scroll', this.onDocumentClick, true)
    window.removeEventListener('resize', this.onDocumentClick)
  },
  methods: {
    toggle() {
      this.open = !this.open
      if (!this.open) return

      this.$nextTick(() => {
        const trigger = this.$refs.triggerRef
        if (!trigger) return

        const rect = trigger.getBoundingClientRect()
        const menuH = this.menuHeight
        const spaceBelow = window.innerHeight - rect.bottom

        if (spaceBelow < menuH && rect.top > spaceBelow) {
          // abrir para cima
          this.menuStyle = {
            top: `${rect.top - menuH + window.scrollY}px`,
            left: `${rect.right - 192 + window.scrollX}px`,
          }
        } else {
          // abrir para baixo
          this.menuStyle = {
            top: `${rect.bottom + 4 + window.scrollY}px`,
            left: `${rect.right - 192 + window.scrollX}px`,
          }
        }
      })
    },
    select(key) {
      this.open = false
      this.$emit('select', key)
    },
    onDocumentClick() {
      this.open = false
    },
  },
}
</script>

<template>
  <div
    class="relative inline-block text-left"
    @click.stop
  >
    <button
      type="button"
      class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-600 shadow-[0_2px_8px_rgba(0,0,0,0.06)] hover:border-slate-300 hover:shadow-[0_4px_12px_rgba(0,0,0,0.1)] hover:text-slate-700 transition-all"
      aria-label="Abrir ações"
      @click.stop="toggle($event)"
    >
      ⋯
    </button>

    <div
      v-if="open"
      :class="[
        'z-20 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-[0_12px 32px_rgba(0,0,0,0.12)] mt-2 md:mt-0 md:absolute md:right-0',
        direction === 'up' ? 'md:bottom-full md:mb-2' : 'md:top-full md:mt-2'
      ]"
    >
      <button
        v-for="item in visibleItems"
        :key="item.key"
        type="button"
        :class="[
          'block w-full px-3 py-2.5 text-left text-sm font-medium transition-colors',
          item.danger ? 'text-red-600 hover:bg-red-50/80' : 'text-slate-700 hover:bg-slate-50'
        ]"
        @click="select(item.key)"
      >
        {{ item.label }}
      </button>
    </div>
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
      direction: 'down',
    }
  },
  computed: {
    visibleItems() {
      return (this.items || []).filter(item => !item.hidden)
    },
  },
  mounted() {
    document.addEventListener('click', this.onDocumentClick)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onDocumentClick)
  },
  methods: {
    toggle(event) {
      this.open = !this.open
      if (!this.open) return

      this.$nextTick(() => {
        const trigger = event?.currentTarget
        if (!trigger) {
          this.direction = 'down'
          return
        }

        const rect = trigger.getBoundingClientRect()
        const spaceBelow = window.innerHeight - rect.bottom
        const spaceAbove = rect.top
        this.direction = (spaceBelow < this.menuHeight && spaceAbove > spaceBelow) ? 'up' : 'down'
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

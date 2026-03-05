<template>
  <div
    class="relative inline-block text-left"
    @click.stop
  >
    <button
      type="button"
      class="h-9 w-9 rounded-xl border border-slate-200/80 bg-white/90 text-slate-700 shadow-[0_6px_14px_rgba(15,23,42,0.07)] hover:bg-white"
      aria-label="Abrir ações"
      @click.stop="toggle($event)"
    >
      ⋯
    </button>

    <div
      v-if="open"
      :class="[
        'z-20 w-52 overflow-hidden rounded-xl border border-slate-200/80 bg-white/95 shadow-[0_18px_34px_rgba(15,23,42,0.12)] mt-2 md:mt-0 md:absolute md:right-0 backdrop-blur',
        direction === 'up' ? 'md:bottom-full md:mb-2' : 'md:top-full md:mt-2'
      ]"
    >
      <button
        v-for="item in visibleItems"
        :key="item.key"
        type="button"
        :class="[
          'block w-full px-3 py-2 text-left text-sm',
          item.danger ? 'text-red-600 hover:bg-red-50' : 'text-slate-700 hover:bg-emerald-50/60'
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

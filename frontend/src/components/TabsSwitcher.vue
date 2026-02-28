<template>
  <div>
    <div class="flex gap-2 border-b mb-4">
      <button
        v-for="t in tabs"
        :key="t"
        :class="['px-3 py-2', active === t ? 'font-semibold border-b-2 border-brand-500' : 'text-muted']"
        @click="select(t)"
      >
        {{ t }}
      </button>
    </div>
    <div>
      <slot :active="active" />
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
export default {
  name: 'TabsSwitcher',
  props: { tabs: { type: Array, default: () => [] }, modelValue: { type: String, default: '' } },
  emits: ['update:modelValue'],
  setup(props, ctx) {
    const active = ref(props.modelValue || props.tabs[0] || '')
    function select(t) { active.value = t; ctx.emit('update:modelValue', t) }
    return { active, select }
  }
}
</script>

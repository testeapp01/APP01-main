<template>
  <button
    :class="['inline-flex items-center justify-center gap-2', variantClass, sizeClass, { 'opacity-50 cursor-not-allowed': disabled } ]"
    :disabled="disabled"
    @click="$emit('click')"
  >
    <slot />
  </button>
</template>

<script>
export default {
  name: 'BaseButton',
  props: {
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false }
  },
  computed: {
    variantClass() {
      if (this.variant === 'primary') return 'btn-primary'
      if (this.variant === 'secondary') return 'btn-secondary'
      if (this.variant === 'ghost') return 'bg-transparent'
      return 'btn-primary'
    },
    sizeClass() {
      if (this.size === 'sm') return 'px-3 py-1 text-sm'
      if (this.size === 'lg') return 'px-5 py-2 text-base'
      return 'px-4 py-2 text-sm'
    }
  }
}
</script>

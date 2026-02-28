<template>
  <button
    v-bind="$attrs"
    :class="classes"
    :disabled="isDisabled"
    :aria-busy="loading ? 'true' : 'false'"
    @click="$emit('click')"
  >
    <span
      v-if="loading"
      class="btn-spinner"
      aria-hidden="true"
    />
    <slot />
  </button>
</template>

<script>
export default {
  name: 'BaseButton',
  inheritAttrs: false,
  props: {
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false }
  },
  emits: ['click'],
  computed: {
    isDisabled() {
      return this.disabled || this.loading
    },
    classes() {
      const base = 'rounded-xl px-4 py-2 min-h-[44px] text-sm sm:text-base font-medium transition transform active:scale-[0.98] inline-flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed'
      const variants = {
        primary: 'btn-primary',
        secondary: 'btn-secondary',
        ghost: 'btn-secondary',
        danger: 'btn-destructive',
        destructive: 'btn-destructive'
      }
      return `${base} ${variants[this.variant] || variants.primary}`
    }
  }
}
</script>

<style scoped>
button { outline: none }
.btn-spinner {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid rgba(255,255,255,0.45);
  border-top-color: currentColor;
  animation: spin .7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

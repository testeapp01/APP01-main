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
      const base = 'rounded-xl min-h-[44px] font-semibold tracking-[0.01em] transition-all duration-200 active:scale-[0.98] inline-flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200/70'
      const variants = {
        primary: 'btn-primary',
        secondary: 'btn-secondary',
        ghost: 'btn-secondary',
        danger: 'btn-destructive',
        destructive: 'btn-destructive'
      }
      const sizes = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-sm sm:text-base',
        lg: 'px-5 py-2.5 text-base'
      }
      return `${base} ${sizes[this.size] || sizes.md} ${variants[this.variant] || variants.primary}`
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

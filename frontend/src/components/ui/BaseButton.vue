<template>
  <button v-bind="$attrs" :class="classes" @click="$emit('click')"><slot/></button>
</template>

<script>
export default {
  name: 'BaseButton',
  inheritAttrs: false,
  props: {
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' }
  },
  computed: {
    classes() {
      const base = 'rounded-xl px-4 py-2 font-medium transition transform active:scale-[0.98]'
      // Use the global utility classes defined in styles.css to keep consistent gradient/colors
      const variants = {
        primary: 'btn-primary',
        danger: 'bg-red-600 hover:bg-red-700 text-white rounded-xl px-4 py-2 font-medium',
        ghost: 'btn-secondary'
      }
      return `${base} ${variants[this.variant] || variants.primary}`
    }
  }
}
</script>

<style scoped>
button { outline: none }
</style>

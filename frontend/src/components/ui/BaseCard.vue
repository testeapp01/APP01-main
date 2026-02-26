<template>
  <div :class="['card p-6', extraClass]">
    <slot />
  </div>
</template>

<script>
export default {
  name: 'BaseCard',
  props: { extraClass: { type: String, default: '' } }
}
</script>

<style scoped>
.card { border-radius: 1rem; }
</style>

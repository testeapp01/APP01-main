<template>
  <div :class="['base-card p-5 sm:p-6', extraClass]">
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
.base-card {
  border-radius: 12px;
  border: 1px solid #E2E8F0;
  background: #FFFFFF;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: box-shadow 0.3s ease, border-color 0.3s ease;
}

.base-card:hover {
  border-color: #CBD5E1;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}
</style>

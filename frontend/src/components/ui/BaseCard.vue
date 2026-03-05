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
  border-radius: 16px;
  border: 1px solid rgba(148, 163, 184, 0.24);
  background: linear-gradient(160deg, rgba(255, 255, 255, 0.94), rgba(255, 255, 255, 0.84));
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(6px);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.base-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 36px rgba(15, 23, 42, 0.1);
}
</style>

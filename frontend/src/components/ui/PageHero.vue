<template>
  <section class="hero-shell">
    <div class="hero-content">
      <div>
        <h1 class="hero-title">
          {{ title }}
        </h1>
        <p
          v-if="subtitle"
          class="hero-subtitle"
        >
          {{ subtitle }}
        </p>
      </div>
      <div class="hero-actions">
        <slot name="actions" />
      </div>
    </div>
    <div
      v-if="$slots.context"
      class="hero-context"
    >
      <slot name="context" />
    </div>
  </section>
</template>

<script>
export default {
  name: 'PageHero',
  props: {
    title: { type: String, required: true },
    subtitle: { type: String, default: '' }
  }
}
</script>

<style scoped>
.hero-shell {
  background: linear-gradient(145deg, rgba(16,185,129,0.10), rgba(255,255,255,0.95));
  border: 1px solid rgba(16,185,129,0.18);
  border-radius: 16px;
  padding: 1rem;
  margin-bottom: 1rem;
}
.hero-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.hero-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #111827;
  letter-spacing: -0.02em;
}
.hero-subtitle {
  margin-top: 0.25rem;
  font-size: 0.88rem;
  color: #6B7280;
}
.hero-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.hero-context {
  margin-top: 0.85rem;
}
@media (min-width: 768px) {
  .hero-shell { padding: 1.25rem; margin-bottom: 1.25rem; }
  .hero-content { flex-direction: row; align-items: center; justify-content: space-between; }
  .hero-title { font-size: 1.65rem; }
}
</style>

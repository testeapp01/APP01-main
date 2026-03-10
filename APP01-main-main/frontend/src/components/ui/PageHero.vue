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
  background: linear-gradient(145deg, rgba(16,185,129,0.12), rgba(14,165,233,0.08) 55%, rgba(255,255,255,0.9));
  border: 1px solid rgba(148, 163, 184, 0.24);
  border-radius: 18px;
  padding: 1rem;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(6px);
  margin-bottom: 1rem;
}
.hero-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.hero-title {
  font-family: var(--font-display, 'Sora', 'Manrope', sans-serif);
  font-size: 1.25rem;
  font-weight: 800;
  color: #111827;
  letter-spacing: -0.02em;
}
.hero-subtitle {
  margin-top: 0.25rem;
  font-size: 0.92rem;
  color: #475569;
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

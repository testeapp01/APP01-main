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
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
  border: 1px solid #E2E8F0;
  border-radius: 16px;
  padding: 2rem 1.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  margin-bottom: 1.5rem;
}

.hero-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.hero-title {
  font-family: var(--font-display, 'Sora', 'Manrope', sans-serif);
  font-size: 2rem;
  font-weight: 800;
  color: #0F172A;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.hero-subtitle {
  margin-top: 0.25rem;
  font-size: 1rem;
  color: #64748B;
  line-height: 1.5;
  font-weight: 500;
}

.hero-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  align-items: center;
}

.hero-context {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #E2E8F0;
}

@media (min-width: 768px) {
  .hero-shell { 
    padding: 2rem 1.75rem; 
    margin-bottom: 1.75rem;
  }
  .hero-content { 
    flex-direction: row; 
    align-items: flex-start; 
    justify-content: space-between;
  }
  .hero-title { font-size: 2.25rem; }
  .hero-subtitle { font-size: 1.05rem; }
}
</style>

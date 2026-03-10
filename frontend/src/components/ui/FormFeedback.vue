<template>
  <div
    v-if="message"
    :class="[
      'form-feedback rounded-xl border px-3 py-2 text-sm transition-all duration-200 ease-in-out',
      toneClass
    ]"
  >
    <span
      class="form-feedback-icon"
      aria-hidden="true"
    >{{ icon }}</span>
    {{ message }}
  </div>
</template>

<script>
export default {
  name: 'FormFeedback',
  props: {
    message: { type: String, default: '' },
    type: { type: String, default: 'info' }
  },
  computed: {
    icon() {
      const icons = {
        success: '✓',
        error: '⚠',
        info: 'ℹ'
      }
      return icons[this.type] || icons.info
    },
    toneClass() {
      const tones = {
        success: 'bg-emerald-50/90 border-emerald-200 text-emerald-800',
        error: 'bg-red-50/90 border-red-200 text-red-800',
        info: 'bg-sky-50/90 border-sky-200 text-sky-800'
      }
      return tones[this.type] || tones.info
    }
  }
}
</script>

<style scoped>
.form-feedback {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  line-height: 1.35;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.55);
}

.form-feedback-icon {
  height: 1.1rem;
  width: 1.1rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  background: rgba(255,255,255,0.7);
  border: 1px solid rgba(148,163,184,0.32);
  flex: none;
}
</style>

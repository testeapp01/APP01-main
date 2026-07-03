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
  align-items: flex-start;
  gap: 0.75rem;
  line-height: 1.5;
  border-radius: 8px;
  border-width: 1px;
  padding: 0.875rem 1rem;
  font-weight: 500;
  font-size: 0.95rem;
}

.form-feedback-icon {
  height: 1.25rem;
  width: 1.25rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  flex: none;
  margin-top: 0.05rem;
}
</style>

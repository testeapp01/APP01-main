
<template>
  <transition name="modal-fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center" @keydown.esc="onEsc" tabindex="-1" ref="modalRoot">
      <div class="absolute inset-0 bg-black/40" @click="close" aria-hidden="true"></div>
      <div
        class="bg-white dark:bg-slate-800 rounded-lg shadow-lg z-10 w-full max-w-2xl p-4 outline-none"
        role="dialog"
        aria-modal="true"
        :aria-label="title || undefined"
        ref="dialog"
        @keydown.tab.prevent="trapFocus"
      >
        <header v-if="$slots.header || title" class="flex items-center justify-between mb-4">
          <slot name="header">
            <h3 class="text-lg font-semibold">{{ title }}</h3>
          </slot>
          <button @click="close" aria-label="Fechar" class="ml-2 text-xl">✕</button>
        </header>
        <section>
          <slot />
        </section>
        <footer v-if="$slots.footer" class="mt-4">
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </transition>
</template>


<script>
export default {
  name: 'BaseModal',
  props: {
    show: { type: Boolean, default: false },
    title: { type: String, default: '' }
  },
  emits: ['update:show', 'close'],
  watch: {
    show(val) {
      if (val) {
        this.$nextTick(() => {
          this.focusDialog();
        });
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    }
  },
  mounted() {
    if (this.show) this.focusDialog();
  },
  beforeUnmount() {
    document.body.style.overflow = '';
  },
  methods: {
    close() {
      this.$emit('update:show', false);
      this.$emit('close');
    },
    onEsc(e) {
      if (this.show) this.close();
    },
    focusDialog() {
      this.$refs.dialog?.focus();
    },
    trapFocus(e) {
      // Simple focus trap for modal
      const focusable = this.$refs.dialog?.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (!focusable?.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (document.activeElement === last) {
        first.focus();
      } else {
        last.focus();
      }
    }
  }
}
</script>
<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.2s;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}
</style>

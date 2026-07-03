
<template>
  <transition name="modal-fade">
    <div
      v-if="show"
      ref="modalRoot"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      tabindex="-1"
      @keydown.esc="onEsc"
    >
      <div
        class="absolute inset-0 bg-black/30"
        aria-hidden="true"
        @click="close"
      />
      <div
        ref="dialog"
        class="bg-white rounded-lg shadow-2xl z-10 w-full max-w-2xl outline-none"
        role="dialog"
        aria-modal="true"
        :aria-label="title || undefined"
        @keydown.tab.prevent="trapFocus"
      >
        <header
          v-if="$slots.header || title"
          class="flex items-center justify-between px-6 py-5 border-b border-slate-200"
        >
          <slot name="header">
            <h3 class="text-xl font-bold text-slate-900">
              {{ title }}
            </h3>
          </slot>
          <button
            aria-label="Fechar"
            class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
            @click="close"
          >
            ✕
          </button>
        </header>
        <section class="px-6 py-5">
          <slot />
        </section>
        <footer
          v-if="$slots.footer"
          class="border-t border-slate-200 px-6 py-4 bg-slate-50 rounded-b-lg flex items-center justify-end gap-3"
        >
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

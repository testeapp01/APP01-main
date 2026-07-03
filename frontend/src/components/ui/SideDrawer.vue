<template>
  <teleport to="body">
    <transition name="drawer-fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[100]"
      >
        <div
          class="absolute inset-0 drawer-backdrop"
          aria-hidden="true"
          @click="$emit('close')"
        />
        <aside class="absolute right-0 top-0 h-full drawer-panel">
          <header class="drawer-header">
            <div>
              <span class="drawer-badge">Novo registro</span>
              <h3 class="drawer-title">
                {{ title }}
              </h3>
              <p class="drawer-subtitle">
                Preencha os dados com segurança. As validações são aplicadas em tempo real.
              </p>
            </div>
            <button
              class="drawer-close"
              aria-label="Fechar painel"
              @click="$emit('close')"
            >
              ✕
            </button>
          </header>
          <div class="drawer-body">
            <div class="drawer-inner">
              <slot />
            </div>
          </div>
        </aside>
      </div>
    </transition>
  </teleport>
</template>

<script>
export default {
  name: 'SideDrawer',
  props: {
    open: { type: Boolean, default: false },
    title: { type: String, default: '' }
  },
  emits: ['close']
}
</script>

<style scoped>
.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: all 0.22s ease-in-out;
}
.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
}
.drawer-fade-enter-from .drawer-panel,
.drawer-fade-leave-to .drawer-panel {
  transform: translateX(28px);
}

.drawer-backdrop {
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(2px);
}

.drawer-panel {
  width: var(--drawer-width, 100vw);
  max-width: 100vw;
  background: #FFFFFF;
  border-left: 1px solid #E2E8F0;
  box-shadow: -8px 0 24px rgba(0, 0, 0, 0.12);
  display: flex;
  flex-direction: column;
}

.drawer-header {
  padding: 1.5rem;
  border-bottom: 1px solid #E2E8F0;
  background: #FFFFFF;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.drawer-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 6px;
  padding: 0.35rem 0.75rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #166534;
  background: #DCFCE7;
  border: 1px solid #BBFBBA;
  margin-bottom: 0.75rem;
}

.drawer-title {
  margin: 0;
  color: #0F172A;
  font-size: 1.25rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.drawer-subtitle {
  margin: 0.5rem 0 0;
  color: #64748B;
  font-size: 0.875rem;
  line-height: 1.5;
}

.drawer-close {
  height: 2rem;
  width: 2rem;
  border-radius: 8px;
  border: 1px solid #E2E8F0;
  color: #64748B;
  background: #FFFFFF;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  font-size: 1.25rem;
  line-height: 1;
}

.drawer-close:hover {
  background: #F8FAFC;
  color: #0F172A;
  border-color: #CBD5E1;
}

.drawer-close:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
  border-color: #16A34A;
}

.drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1.1rem calc(1.15rem + env(safe-area-inset-bottom));
}

.drawer-inner {
  background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(241,245,249,0.6) 100%);
  border: 1px solid rgba(148,163,184,0.2);
  border-radius: 1rem;
  padding: 1rem;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.45);
}

.drawer-body :deep(.drawer-form) {
  display: grid;
  gap: 0.9rem;
}

.drawer-body :deep(.drawer-form label) {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #475569;
}

.drawer-body :deep(.drawer-form input),
.drawer-body :deep(.drawer-form select),
.drawer-body :deep(.drawer-form textarea) {
  min-height: 44px;
  border-radius: 0.9rem;
  border: 1px solid rgba(148,163,184,0.42);
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.75);
}

.drawer-body :deep(.drawer-form .drawer-actions) {
  position: sticky;
  bottom: -0.5rem;
  margin-top: 0.5rem;
  padding-top: 0.85rem;
  padding-bottom: max(0.35rem, env(safe-area-inset-bottom));
  background: linear-gradient(180deg, rgba(247,250,252,0.05) 0%, rgba(247,250,252,0.92) 35%);
}

@media (max-width: 639px) {
  .drawer-header {
    padding: 1rem 1rem 0.9rem;
  }

  .drawer-title {
    font-size: 1rem;
  }

  .drawer-body {
    padding: 0.75rem;
  }

  .drawer-inner {
    border-radius: 0.85rem;
    padding: 0.85rem;
  }
}

@media (min-width: 640px) {
  .drawer-panel {
    --drawer-width: min(92vw, 760px);
  }
}

@media (min-width: 1024px) {
  .drawer-panel {
    --drawer-width: min(64vw, 860px);
  }
}

@media (min-width: 1440px) {
  .drawer-panel {
    --drawer-width: min(52vw, 920px);
  }
}

@media (min-width: 1800px) {
  .drawer-panel {
    --drawer-width: min(46vw, 980px);
  }
}
</style>

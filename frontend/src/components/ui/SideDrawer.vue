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
  background: radial-gradient(circle at top right, rgba(16,185,129,0.18), transparent 50%), rgba(2,6,23,0.48);
  backdrop-filter: blur(4px);
}

.drawer-panel {
  width: var(--drawer-width, 100vw);
  max-width: 100vw;
  background: linear-gradient(180deg, #ffffff 0%, #f7fbfa 100%);
  border-left: 1px solid rgba(148,163,184,0.35);
  box-shadow: -10px 0 42px rgba(2,6,23,0.22);
  display: flex;
  flex-direction: column;
}

.drawer-header {
  padding: 1.15rem 1.25rem 1rem;
  border-bottom: 1px solid rgba(148,163,184,0.28);
  background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(240,253,250,0.85) 100%);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.drawer-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.65rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: #047857;
  background: rgba(16,185,129,0.16);
  border: 1px solid rgba(16,185,129,0.28);
  margin-bottom: 0.55rem;
}

.drawer-title {
  margin: 0;
  color: #0f172a;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.drawer-subtitle {
  margin: 0.45rem 0 0;
  color: #64748b;
  font-size: 0.84rem;
  line-height: 1.35;
}

.drawer-close {
  height: 2.35rem;
  width: 2.35rem;
  border-radius: 0.85rem;
  border: 1px solid rgba(148,163,184,0.45);
  color: #475569;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all .16s ease;
}

.drawer-close:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: rgba(16,185,129,0.45);
  transform: translateY(-1px);
}

.drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1.1rem 1.15rem;
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

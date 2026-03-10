<template>
  <div class="login-page">
    <div
      class="login-bg"
      aria-hidden="true"
    >
      <span class="shape shape-a" />
      <span class="shape shape-b" />
      <span class="shape shape-c" />
    </div>

    <main class="login-layout">
      <section
        class="brand-panel"
        aria-hidden="true"
      >
        <div class="brand-logo-wrap">
          <img
            src="/logo-wordmark.png"
            alt=""
            class="brand-wordmark"
          >
        </div>

        <div class="brand-content">
          <p class="brand-eyebrow">
            Plataforma Safrion
          </p>
          <h1 class="brand-title">
            Acesso ao painel operacional
          </h1>
          <p class="brand-copy">
            Gestao de vendas, compras e relatorios com fluxo rapido e seguro.
          </p>
        </div>
      </section>

      <section class="login-card-wrap">
        <div class="card-stage">
          <Transition
            appear
            name="card-fade-slide"
          >
            <LoginCard
              :email="form.email"
              :password="form.password"
              :remember="remember"
              :show-password="showPassword"
              :errors="errors"
              :error-message="errorMessage"
              :is-submitting="isSubmitting"
              :can-submit="canSubmit"
              @update:email="updateEmail"
              @update:password="updatePassword"
              @update:remember="updateRemember"
              @toggle-password="togglePassword"
              @blur:email="onEmailBlur"
              @blur:password="onPasswordBlur"
              @submit="onSubmit"
            />
          </Transition>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import LoginCard from '../components/LoginCard.vue'
import { useAuth } from '../composables/useAuth'

const {
  form,
  errors,
  remember,
  showPassword,
  isSubmitting,
  errorMessage,
  canSubmit,
  updateEmail,
  updatePassword,
  updateRemember,
  togglePassword,
  onEmailBlur,
  onPasswordBlur,
  onSubmit,
} = useAuth()
</script>

<style scoped>
.login-page {
  --accent: #0f9d58;
  --accent-strong: #0b7f47;
  --ink: #0f172a;
  --muted: #475569;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  background: radial-gradient(circle at 20% 20%, rgba(15, 157, 88, 0.12), transparent 36%),
    radial-gradient(circle at 85% 15%, rgba(14, 116, 144, 0.12), transparent 32%),
    linear-gradient(160deg, #f8fafc 0%, #ecfdf5 48%, #eef2ff 100%);
}

.login-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.shape {
  position: absolute;
  border-radius: 999px;
  filter: blur(2px);
}

.shape-a {
  width: 280px;
  height: 280px;
  background: rgba(16, 185, 129, 0.16);
  top: -120px;
  left: -80px;
}

.shape-b {
  width: 260px;
  height: 260px;
  background: rgba(56, 189, 248, 0.14);
  right: -70px;
  top: 80px;
}

.shape-c {
  width: 360px;
  height: 360px;
  background: rgba(134, 239, 172, 0.16);
  bottom: -170px;
  right: 20%;
}

.login-layout {
  min-height: 100vh;
  padding: 1.25rem;
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  align-items: center;
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
}

.brand-panel {
  display: none;
}

.login-card-wrap {
  display: grid;
  place-items: center;
}

.card-stage {
  width: min(100%, 560px);
  min-height: 430px;
  border-radius: 1.75rem;
  padding: 1.2rem;
  display: grid;
  place-items: center;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.32), rgba(255, 255, 255, 0.14));
  border: 1px solid rgba(255, 255, 255, 0.44);
  box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.03);
}

.card-fade-slide-enter-active {
  transition: opacity 260ms ease, transform 280ms ease;
}

.card-fade-slide-enter-from {
  opacity: 0;
  transform: translateY(14px);
}

@media (min-width: 980px) {
  .login-layout {
    grid-template-columns: minmax(380px, 1fr) minmax(420px, 470px);
    gap: 3.5rem;
    padding: 2.6rem 2.2rem;
  }

  .brand-panel {
    display: grid;
    align-content: center;
    justify-items: start;
    gap: 2rem;
    padding: 1.2rem 0.6rem;
    max-width: 31rem;
    justify-self: start;
    animation: brand-enter 460ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
  }

  .brand-logo-wrap {
    width: 100%;
    display: flex;
    align-items: center;
  }

  .brand-content {
    display: grid;
    gap: 1.75rem;
    margin-top: -9.4rem;
  }

  .brand-wordmark {
    width: clamp(12.5rem, 25vw, 16.5rem);
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 6px 12px rgba(15, 23, 42, 0.12));
  }

  .brand-eyebrow {
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.11em;
    font-size: 0.72rem;
    color: #0f9d58;
    font-weight: 700;
  }

  .brand-title {
    margin: 0;
    max-width: 14ch;
    font-size: clamp(2rem, 3.2vw, 2.9rem);
    line-height: 1.01;
    color: #0f172a;
    font-weight: 800;
  }

  .brand-copy {
    margin: 0;
    max-width: 31ch;
    color: #334155;
    font-size: 1.06rem;
    line-height: 1.55;
  }

  .card-stage {
    justify-self: end;
  }

  @keyframes brand-enter {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
}

@media (max-width: 979px) {
  .card-stage {
    min-height: auto;
    padding: 0;
    border: 0;
    box-shadow: none;
    background: transparent;
  }
}
</style>

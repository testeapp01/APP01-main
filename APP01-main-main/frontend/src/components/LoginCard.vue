<template>
  <article
    class="login-card"
    aria-labelledby="login-title"
  >
    <header class="card-header">
      <div class="card-headline">
        <p class="chip-label">
          Workspace
        </p>
        <h2
          id="login-title"
          class="card-title"
        >
          Entrar
        </h2>
      </div>
    </header>

    <p class="card-subtitle">
      Use seu e-mail corporativo para acessar o ambiente.
    </p>

    <Transition name="fade-slide">
      <div
        v-if="errorMessage"
        class="alert-error"
        role="alert"
        aria-live="assertive"
      >
        {{ errorMessage }}
      </div>
    </Transition>

    <form
      class="login-form"
      novalidate
      @submit.prevent="$emit('submit')"
    >
      <div class="field-group">
        <label
          for="email"
          class="field-label"
        >E-mail</label>
        <div
          class="field-control"
          :class="{ 'has-error': !!errors.email }"
        >
          <span
            class="field-icon"
            aria-hidden="true"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path d="M3 6h18v12H3z" />
              <path d="M3 7l9 6 9-6" />
            </svg>
          </span>
          <input
            id="email"
            class="field-input"
            type="email"
            autocomplete="email"
            inputmode="email"
            placeholder="voce@safrion.store"
            :value="email"
            :aria-invalid="errors.email ? 'true' : 'false'"
            :aria-describedby="errors.email ? 'email-error' : undefined"
            @input="$emit('update:email', $event.target.value)"
            @blur="$emit('blur:email')"
          >
        </div>
        <p
          v-if="errors.email"
          id="email-error"
          class="field-error"
        >
          {{ errors.email }}
        </p>
      </div>

      <div class="field-group">
        <label
          for="password"
          class="field-label"
        >Senha</label>
        <div
          class="field-control"
          :class="{ 'has-error': !!errors.password }"
        >
          <span
            class="field-icon"
            aria-hidden="true"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <rect
                x="4"
                y="10"
                width="16"
                height="10"
                rx="2"
              />
              <path d="M8 10V8a4 4 0 018 0v2" />
            </svg>
          </span>
          <input
            id="password"
            class="field-input password-input"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="Digite sua senha"
            :value="password"
            :aria-invalid="errors.password ? 'true' : 'false'"
            :aria-describedby="errors.password ? 'password-error' : undefined"
            @input="$emit('update:password', $event.target.value)"
            @blur="$emit('blur:password')"
          >
          <button
            class="toggle-password"
            type="button"
            :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
            @click="$emit('toggle-password')"
          >
            <svg
              v-if="!showPassword"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z" />
              <circle
                cx="12"
                cy="12"
                r="3"
              />
            </svg>
            <svg
              v-else
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path d="M3 3l18 18" />
              <path d="M10.6 10.6a2 2 0 102.8 2.8" />
              <path d="M6.7 6.7A13.5 13.5 0 002 12s3.6 6 10 6a10 10 0 004.7-1.1" />
              <path d="M9.9 4.4A10.7 10.7 0 0112 4c6.4 0 10 6 10 6a15 15 0 01-3.1 3.9" />
            </svg>
          </button>
        </div>
        <p
          v-if="errors.password"
          id="password-error"
          class="field-error"
        >
          {{ errors.password }}
        </p>
      </div>

      <div class="row-actions">
        <label class="remember-toggle">
          <input
            class="remember-input"
            type="checkbox"
            :checked="remember"
            @change="$emit('update:remember', $event.target.checked)"
          >
          <span
            class="remember-box"
            aria-hidden="true"
          />
          <span class="remember-text">Lembrar e-mail neste dispositivo</span>
        </label>
      </div>

      <button
        class="submit-btn"
        type="submit"
        :disabled="isSubmitting || !canSubmit"
      >
        <span
          v-if="isSubmitting"
          class="spinner"
          aria-hidden="true"
        />
        <span>{{ isSubmitting ? 'Entrando...' : 'Acessar painel' }}</span>
      </button>
    </form>
  </article>
</template>

<script setup>
defineProps({
  email: { type: String, required: true },
  password: { type: String, required: true },
  remember: { type: Boolean, required: true },
  showPassword: { type: Boolean, required: true },
  errors: {
    type: Object,
    required: true,
  },
  errorMessage: { type: String, default: '' },
  isSubmitting: { type: Boolean, required: true },
  canSubmit: { type: Boolean, required: true },
})

defineEmits([
  'update:email',
  'update:password',
  'update:remember',
  'toggle-password',
  'blur:email',
  'blur:password',
  'submit',
])
</script>

<style scoped>
.login-card {
  width: min(100%, 450px);
  border-radius: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.72);
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.72));
  backdrop-filter: blur(14px);
  box-shadow: 0 24px 52px rgba(15, 23, 42, 0.16);
  padding: 1.45rem;
}

.card-header {
  display: block;
}

.card-headline {
  display: grid;
  gap: 0.15rem;
}

.chip-label {
  margin: 0;
  color: #64748b;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
}

.card-title {
  margin: 0;
  color: #0f172a;
  font-size: 1.45rem;
  font-weight: 700;
}

.card-subtitle {
  margin: 0.86rem 0 1.12rem;
  color: #475569;
  font-size: 0.95rem;
}

.alert-error {
  background: rgba(254, 226, 226, 0.85);
  border: 1px solid rgba(248, 113, 113, 0.35);
  color: #b91c1c;
  border-radius: 0.9rem;
  padding: 0.65rem 0.8rem;
  margin-bottom: 0.9rem;
  font-size: 0.88rem;
}

.login-form {
  display: grid;
  gap: 0.8rem;
}

.field-group {
  display: grid;
  gap: 0.38rem;
}

.field-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
}

.field-control {
  position: relative;
  border-radius: 0.95rem;
  border: 1px solid rgba(148, 163, 184, 0.55);
  background: rgba(255, 255, 255, 0.78);
  transition: border-color 160ms ease, box-shadow 160ms ease;
}

.field-control:focus-within {
  border-color: rgba(15, 157, 88, 0.75);
  box-shadow: 0 0 0 4px rgba(15, 157, 88, 0.16);
}

.field-control.has-error {
  border-color: rgba(239, 68, 68, 0.75);
}

.field-icon {
  position: absolute;
  left: 0.8rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: #64748b;
}

.field-icon svg {
  width: 100%;
  height: 100%;
}

.field-input {
  width: 100%;
  border: 0;
  background: transparent;
  outline: 0;
  padding: 0.78rem 0.8rem 0.78rem 2.45rem;
  color: #0f172a;
  font-size: 0.95rem;
}

.password-input {
  padding-right: 2.9rem;
}

.toggle-password {
  position: absolute;
  right: 0.45rem;
  top: 50%;
  transform: translateY(-50%);
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 0.7rem;
  display: grid;
  place-items: center;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  transition: background-color 140ms ease, color 140ms ease;
}

.toggle-password:hover,
.toggle-password:focus-visible {
  background: rgba(15, 157, 88, 0.12);
  color: #0b7f47;
}

.toggle-password svg {
  width: 1rem;
  height: 1rem;
}

.field-error {
  margin: 0;
  color: #b91c1c;
  font-size: 0.78rem;
}

.row-actions {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  margin-top: 0.15rem;
}

.remember-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  cursor: pointer;
  user-select: none;
}

.remember-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.remember-box {
  width: 1.1rem;
  height: 1.1rem;
  border-radius: 0.33rem;
  border: 1.4px solid #94a3b8;
  background: rgba(255, 255, 255, 0.8);
  position: relative;
  transition: all 140ms ease;
}

.remember-input:checked + .remember-box {
  background: #0f9d58;
  border-color: #0f9d58;
}

.remember-input:checked + .remember-box::after {
  content: '';
  position: absolute;
  width: 0.3rem;
  height: 0.56rem;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  left: 0.35rem;
  top: 0.16rem;
  transform: rotate(45deg);
}

.remember-text {
  color: #334155;
  font-size: 0.83rem;
  line-height: 1.2;
}

.submit-btn {
  margin-top: 0.3rem;
  width: 100%;
  border: 0;
  border-radius: 0.95rem;
  background: linear-gradient(135deg, #0f9d58, #34a853);
  color: #fff;
  font-weight: 700;
  font-size: 0.95rem;
  padding: 0.85rem 1rem;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: 0.55rem;
  cursor: pointer;
  box-shadow: 0 10px 20px rgba(15, 157, 88, 0.32);
  transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-1px) scale(1.01);
  box-shadow: 0 13px 24px rgba(15, 157, 88, 0.35);
  filter: brightness(1.02);
}

.submit-btn:disabled {
  opacity: 0.72;
  cursor: not-allowed;
}

.spinner {
  width: 0.95rem;
  height: 0.95rem;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: #fff;
  border-radius: 999px;
  animation: spin 700ms linear infinite;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (min-width: 980px) {
  .login-card {
    padding: 1.75rem;
  }
}

@media (max-width: 420px) {
  .login-card {
    padding: 1.1rem;
    border-radius: 1.2rem;
  }

  .card-title {
    font-size: 1.28rem;
  }
}
</style>

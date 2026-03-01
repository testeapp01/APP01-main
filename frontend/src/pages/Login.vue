<template>
  <div class="page-shell px-4 sm:px-0 py-6 sm:py-10">
    <div class="auth-card">
      <div class="flex items-center gap-3 mb-4">
        <img
          src="/brand-logo.svg"
          alt="Hortifrut"
          class="h-11 w-11 rounded-xl object-contain border border-slate-200 bg-white p-1"
        >
        <div>
          <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">
            Hortifrut
          </p>
          <p class="text-sm font-semibold text-slate-700">
            Painel de gestão
          </p>
        </div>
      </div>
      <h2 class="text-xl sm:text-2xl font-bold mb-2">
        Login
      </h2>
      <p class="text-sm text-slate-500 mb-4">
        Acesse sua conta para continuar no workspace.
      </p>
      <form
        class="space-y-3"
        @submit.prevent="submit"
      >
        <input
          v-model="email"
          placeholder="Email"
          type="email"
          class="w-full p-3 border border-gray-300 rounded-xl"
          required
        >
        <input
          v-model="password"
          placeholder="Senha"
          type="password"
          class="w-full p-3 border border-gray-300 rounded-xl"
          required
        >
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-0 justify-between sm:items-center">
          <label class="text-sm inline-flex items-center gap-2"><input
            v-model="remember"
            type="checkbox"
          > Lembrar</label>
          <BaseButton
            class="w-full sm:w-auto btn-primary"
            :loading="submitting"
            :disabled="submitting"
            type="submit"
          >
            Entrar
          </BaseButton>
        </div>
      </form>
      <p
        v-if="error"
        class="mt-4 text-sm text-red-600"
      >
        {{ error }}
      </p>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth'
import BaseButton from '../components/ui/BaseButton.vue'

export default {
  components: { BaseButton },
  setup() {
    const auth = useAuthStore()
    return { auth }
  },
  data() { return { email: '', password: '', remember: false, error: null, submitting: false } },
  mounted() {
    if (this.auth && this.auth.token) {
      this.$router.replace('/')
    }
  },
  methods: {
    async submit() {
      this.submitting = true
      this.error = null
      try {
        await this.auth.login(this.email, this.password)
        const destination = this.$route.query.redirect || '/'
        this.$router.replace(destination)
      } catch (e) {
        this.error = e.response ? e.response.data.error : e.message
      } finally {
        this.submitting = false
      }
    }
  }
}
</script>

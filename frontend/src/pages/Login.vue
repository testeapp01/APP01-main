<template>
  <div class="max-w-md mx-auto mt-20 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">Login</h2>
    <form @submit.prevent="submit" class="space-y-3">
      <input v-model="email" placeholder="Email" type="email" class="w-full p-2 border rounded" />
      <input v-model="password" placeholder="Senha" type="password" class="w-full p-2 border rounded" />
      <div class="flex justify-between items-center">
        <label class="text-sm"><input type="checkbox" v-model="remember" /> Lembrar</label>
        <button class="px-4 py-2 bg-green-600 text-white rounded">Entrar</button>
      </div>
    </form>
    <p class="mt-4 text-sm text-red-600" v-if="error">{{ error }}</p>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth'

export default {
  data() { return { email: '', password: '', remember: false, error: null } },
  setup() {
    const auth = useAuthStore()
    return { auth }
  },
  mounted() {
    if (this.auth && this.auth.token) {
      window.location.hash = '#/'
    }
  },
  methods: {
    async submit() {
      try {
        await this.auth.login(this.email, this.password)
        window.location.hash = '#/'
      } catch (e) {
        this.error = e.response ? e.response.data.error : e.message
      }
    }
  }
}
</script>

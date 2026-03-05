<template>
  <div class="page-shell px-4 sm:px-0 py-6 sm:py-10">
    <div class="auth-card">
      <div class="flex items-center gap-3 mb-4">
        <img
          src="/logo-symbol.png"
          alt="Safrion"
          class="h-12 w-12 object-contain drop-shadow-[0_3px_8px_rgba(15,23,42,0.2)]"
        >
        <div>
          <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">
            Safrion
          </p>
          <p class="text-sm font-semibold text-slate-700">
            Segurança da sessão
          </p>
        </div>
      </div>

      <h2 class="text-xl sm:text-2xl font-bold mb-2">
        Sessão expirada
      </h2>
      <p class="text-sm text-slate-500 mb-5">
        Sua sessão foi encerrada por inatividade. Faça login novamente para continuar.
      </p>

      <router-link
        to="/login"
        class="btn-primary inline-flex items-center justify-center w-full"
      >
        Ir para Login
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SessaoExpirada'
}
</script>

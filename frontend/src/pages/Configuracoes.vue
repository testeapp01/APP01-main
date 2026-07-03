<template>
  <div class="page-shell page-fade section-stack">
    <PageHero
      title="Configurações"
      subtitle="Gerencie workspace, preferências pessoais e integrações."
    />

    <div class="settings-grid">
      <!-- WORKSPACE SETTINGS -->
      <div class="panel-inner content-card">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200">
          <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold text-lg">
            S
          </div>
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Workspace</h2>
            <p class="text-sm text-slate-500">safrion.workspace</p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">
                Nome do Workspace
              </label>
              <input
                v-model="workspace.name"
                type="text"
                placeholder="Safrion"
                class="w-full px-3 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-all"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">
                Domínio
              </label>
              <input
                v-model="workspace.domain"
                type="text"
                placeholder="safrion.app"
                class="w-full px-3 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-all"
              >
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Descrição
            </label>
            <textarea
              v-model="workspace.description"
              placeholder="Descreva seu workspace..."
              rows="3"
              class="w-full px-3 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-all resize-none"
            />
          </div>

          <div class="flex justify-end pt-2">
            <BaseButton
              class="btn-primary"
              :loading="savingWorkspace"
              @click="saveWorkspace"
            >
              Salvar Workspace
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- PREFERÊNCIAS DE USUÁRIO -->
      <div class="panel-inner content-card">
        <h2 class="text-lg font-semibold mb-6 pb-4 border-b border-slate-200">
          Preferências Pessoais
        </h2>

        <div class="space-y-5">
          <!-- Theme -->
          <div class="flex items-center justify-between p-4 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors">
            <div>
              <h3 class="font-medium text-slate-900">Tema</h3>
              <p class="text-sm text-slate-500">Escolha entre claro e escuro</p>
            </div>
            <select
              v-model="preferences.theme"
              class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
            >
              <option value="light">Claro</option>
              <option value="dark">Escuro</option>
              <option value="system">Sistema</option>
            </select>
          </div>

          <!-- Densidade de UI -->
          <div class="flex items-center justify-between p-4 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors">
            <div>
              <h3 class="font-medium text-slate-900">Densidade de UI</h3>
              <p class="text-sm text-slate-500">Compacta ou confortável</p>
            </div>
            <select
              v-model="preferences.density"
              class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
            >
              <option value="comfortable">Confortável</option>
              <option value="compact">Compacta</option>
            </select>
          </div>

          <!-- Animações -->
          <div class="flex items-center justify-between p-4 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors">
            <div>
              <h3 class="font-medium text-slate-900">Animações</h3>
              <p class="text-sm text-slate-500">Ativar/desativar movimentos</p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="preferences.animations"
                type="checkbox"
                class="w-4 h-4 rounded border-slate-300 text-green-600 focus:ring-green-500"
              >
              <span class="text-sm text-slate-600">{{ preferences.animations ? 'Ativado' : 'Desativado' }}</span>
            </label>
          </div>

          <!-- Notificações -->
          <div class="flex items-center justify-between p-4 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors">
            <div>
              <h3 class="font-medium text-slate-900">Notificações</h3>
              <p class="text-sm text-slate-500">Receber alertas do sistema</p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="preferences.notifications"
                type="checkbox"
                class="w-4 h-4 rounded border-slate-300 text-green-600 focus:ring-green-500"
              >
              <span class="text-sm text-slate-600">{{ preferences.notifications ? 'Ativado' : 'Desativado' }}</span>
            </label>
          </div>

          <div class="flex justify-end pt-2">
            <BaseButton
              class="btn-primary"
              :loading="savingPreferences"
              @click="savePreferences"
            >
              Salvar Preferências
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- SEGURANÇA & PRIVACIDADE -->
      <div class="panel-inner content-card">
        <h2 class="text-lg font-semibold mb-6 pb-4 border-b border-slate-200">
          Segurança & Privacidade
        </h2>

        <div class="space-y-3">
          <button class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
            <div class="font-medium text-slate-900">Alterar Senha</div>
            <div class="text-sm text-slate-500">Atualize sua senha regularmente</div>
          </button>

          <button class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
            <div class="font-medium text-slate-900">Autenticação de Dois Fatores</div>
            <div class="text-sm text-slate-500">Adicione camada extra de segurança</div>
          </button>

          <button class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
            <div class="font-medium text-slate-900">Sessões Ativas</div>
            <div class="text-sm text-slate-500">Visualize dispositivos conectados</div>
          </button>

          <button class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:bg-red-50 transition-colors">
            <div class="font-medium text-red-600">Deletar Workspace</div>
            <div class="text-sm text-red-500">Ação irreversível - tenha cuidado</div>
          </button>
        </div>
      </div>

      <!-- INFORMAÇÕES DE VERSÃO -->
      <div class="panel-inner content-card bg-gradient-to-br from-slate-50 to-slate-100">
        <h2 class="text-lg font-semibold mb-4">Sobre Safrion</h2>

        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-600">Versão</span>
            <span class="font-medium text-slate-900">1.0.0-beta</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Ambiente</span>
            <span class="font-medium text-slate-900">Production</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Build</span>
            <span class="font-medium text-slate-900">2026.07.03</span>
          </div>
          <div class="pt-3 border-t border-slate-300">
            <p class="text-slate-600">
              Safrion é uma plataforma moderna de gestão operacional.
              Construída com tecnologia de ponta para empresas que querem escalar.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Message -->
    <Transition name="fade">
      <div
        v-if="savedMessage"
        class="fixed bottom-4 right-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm font-medium"
      >
        {{ savedMessage }}
      </div>
    </Transition>
  </div>
</template>

<script>
import PageHero from '../components/ui/PageHero.vue'
import BaseButton from '../components/ui/BaseButton.vue'

export default {
  name: 'Configuracoes',
  components: {
    PageHero,
    BaseButton,
  },
  data() {
    return {
      workspace: {
        name: 'Safrion',
        domain: 'safrion.app',
        description: 'Plataforma de gestão operacional',
      },
      preferences: {
        theme: 'system',
        density: 'comfortable',
        animations: true,
        notifications: true,
      },
      savingWorkspace: false,
      savingPreferences: false,
      savedMessage: '',
    }
  },
  methods: {
    async saveWorkspace() {
      this.savingWorkspace = true
      try {
        await new Promise(resolve => setTimeout(resolve, 600))
        this.savedMessage = '✓ Workspace salvo com sucesso'
        setTimeout(() => (this.savedMessage = ''), 3000)
      } finally {
        this.savingWorkspace = false
      }
    },
    async savePreferences() {
      this.savingPreferences = true
      try {
        await new Promise(resolve => setTimeout(resolve, 600))
        this.savedMessage = '✓ Preferências salvas com sucesso'
        setTimeout(() => (this.savedMessage = ''), 3000)
      } finally {
        this.savingPreferences = false
      }
    },
  },
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 200ms ease-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
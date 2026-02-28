<template>
  <div class="page-shell">
    <PageHero
      title="Configurações"
      subtitle="Ajuste parâmetros operacionais e comportamento visual do workspace."
    >
      <template #actions>
        <BaseButton
          class="btn-primary w-full sm:w-auto"
          :loading="savingAll"
          :disabled="savingAll"
          @click="saveAll"
        >
          Salvar tudo
        </BaseButton>
      </template>
    </PageHero>

    <div class="settings-grid">
      <div class="panel-inner">
        <h2 class="text-xl font-semibold mb-4">
          Comissões
        </h2>
        <form
          class="grid grid-cols-1 gap-3"
          @submit.prevent="saveCommissions"
        >
          <input
            v-model.number="commission.defaultPercent"
            type="number"
            placeholder="Percentual Padrão (%)"
            class="p-3 border border-gray-300 rounded-xl"
          >
          <input
            v-model.number="commission.fixedValue"
            type="number"
            placeholder="Comissão Fixa (R$)"
            class="p-3 border border-gray-300 rounded-xl"
          >
          <input
            v-model.number="commission.extraPerBag"
            type="number"
            placeholder="Extra por Saco (R$)"
            class="p-3 border border-gray-300 rounded-xl"
          >
          <div class="flex justify-end">
            <BaseButton
              type="submit"
              class="btn-primary"
              :loading="savingCommissions"
              :disabled="savingCommissions"
            >
              Salvar comissões
            </BaseButton>
          </div>
        </form>
      </div>

      <div class="panel-inner">
        <h2 class="text-xl font-semibold mb-4">
          Layout
        </h2>
        <form
          class="grid grid-cols-1 gap-4"
          @submit.prevent="saveLayout"
        >
          <label class="switch-label">
            <span
              class="switch"
              :class="{ active: layout.darkMode }"
            >
              <input
                v-model="layout.darkMode"
                type="checkbox"
              >
              <span class="knob" />
            </span>
            <span class="text-sm status-text">{{ layout.darkMode ? 'Modo escuro ativo' : 'Modo claro ativo' }}</span>
          </label>
          <div class="flex justify-end">
            <BaseButton
              type="submit"
              class="btn-primary"
              :loading="savingLayout"
              :disabled="savingLayout"
            >
              Salvar layout
            </BaseButton>
          </div>
        </form>
      </div>
    </div>

    <p
      v-if="savedMessage"
      class="mt-4 text-sm text-emerald-700"
    >
      {{ savedMessage }}
    </p>
  </div>
</template>

<script>
import PageHero from '../components/ui/PageHero.vue'
import BaseButton from '../components/ui/BaseButton.vue'

export default {
  name: 'Settings',
  components: { PageHero, BaseButton },
  data() {
    return {
      commission: {
        defaultPercent: 0,
        fixedValue: 0,
        extraPerBag: 0
      },
      layout: {
        darkMode: false
      },
      savingCommissions: false,
      savingLayout: false,
      savingAll: false,
      savedMessage: ''
    }
  },
  methods: {
    async saveCommissions() {
      this.savingCommissions = true
      this.savedMessage = ''
      try {
        await new Promise(resolve => setTimeout(resolve, 180))
        this.savedMessage = 'Configurações de comissão salvas com sucesso.'
      } finally {
        this.savingCommissions = false
      }
    },
    async saveLayout() {
      this.savingLayout = true
      this.savedMessage = ''
      try {
        await new Promise(resolve => setTimeout(resolve, 180))
        this.savedMessage = 'Configurações de layout salvas com sucesso.'
      } finally {
        this.savingLayout = false
      }
    },
    async saveAll() {
      this.savingAll = true
      this.savedMessage = ''
      try {
        await Promise.all([this.saveCommissions(), this.saveLayout()])
        this.savedMessage = 'Todas as configurações foram salvas.'
      } finally {
        this.savingAll = false
      }
    }
  }
};
</script>

<style scoped>
/* Add any custom styles here */
</style>
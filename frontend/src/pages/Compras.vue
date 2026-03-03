<template>
  <div class="page-shell">
    <PageHero
      title="Compras"
      subtitle="Controle entradas, parceiros e comissões em um fluxo operacional único."
    >
      <template #actions>
        <BaseButton
          class="btn-secondary w-full sm:w-auto"
          @click="loadCompras"
        >
          Atualizar
        </BaseButton>
        <BaseButton
          class="btn-primary w-full sm:w-auto"
          @click="openCreateModal"
        >
          Adicionar Compra
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Total de Compras
        </div>
        <div class="saas-kpi-value">
          {{ totalCount }}
        </div>
        <div class="saas-kpi-help">
          Registros no período atual
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Página Atual
        </div>
        <div class="saas-kpi-value">
          {{ currentPage }}
        </div>
        <div class="saas-kpi-help">
          Navegação operacional
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Itens por Página
        </div>
        <div class="saas-kpi-value">
          {{ pageSize }}
        </div>
        <div class="saas-kpi-help">
          Escala de visualização
        </div>
      </article>
    </section>

    <div
      v-if="visibleCompras.length > 0"
      class="panel-inner"
    >
      <BaseTable
        :columns="tableCols"
        :rows="paginatedCompras"
      >
        <template #numero_pedido="{ row }">
          #{{ row.id }}
        </template>
        <template #tipo_operacao="{ row }">
          {{ row.tipo_operacao || '-' }}
        </template>
        <template #fornecedor="{ row }">
          {{ row.fornecedor || '-' }}
        </template>
        <template #cliente="{ row }">
          {{ row.cliente || '-' }}
        </template>
        <template #motorista="{ row }">
          {{ row.motorista || '-' }}
        </template>
        <template #itens_count="{ row }">
          {{ row.itens_count ?? '-' }}
        </template>
        <template #valor_total="{ row }">
          {{ row.valor_total !== undefined && row.valor_total !== null ? ('R$ ' + Number(row.valor_total).toFixed(2)) : '-' }}
        </template>
        <template #status="{ row }">
          {{ row.status || '-' }}
        </template>
        <template #data_envio_prevista="{ row }">
          {{ formatDate(row.data_envio_prevista) }}
        </template>
        <template #data_entrega_prevista="{ row }">
          {{ formatDate(row.data_entrega_prevista) }}
        </template>
        <template #acoes="{ row }">
          <div
            class="relative inline-block text-left"
            @click.stop
          >
            <button
              type="button"
              class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
              aria-label="Abrir ações"
              @click.stop="toggleActionsMenu(row.id, $event)"
            >
              ⋯
            </button>

            <div
              v-if="openActionsMenuId === row.id"
              :class="[
                'absolute right-0 z-20 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg',
                openActionsMenuDirection === 'up' ? 'bottom-full mb-2' : 'top-full mt-2'
              ]"
            >
              <button
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="openItems(row.id); closeActionsMenu()"
              >
                Itens
              </button>
              <button
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="openEditModal(row); closeActionsMenu()"
              >
                Editar
              </button>
              <button
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="printOrder(row); closeActionsMenu()"
              >
                Imprimir
              </button>
              <button
                v-if="!(row.status && String(row.status).toLowerCase() === 'recebida')"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="confirmDelivery(row); closeActionsMenu()"
              >
                Confirmar entrega
              </button>
              <button
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                @click="deletePurchase(row); closeActionsMenu()"
              >
                Excluir
              </button>
            </div>
          </div>
        </template>
      </BaseTable>
    </div>
    <ListState
      :loading="loading"
      :has-data="visibleCompras.length > 0"
      loading-text="Carregando compras..."
      empty-title="Nenhuma compra encontrada."
      empty-message="Adicione compras para começar a registrar entradas."
      action-label="Adicionar Compra"
      @action="openCreateModal"
    />

    <div
      v-if="visibleCompras.length > 0"
      class="mt-4"
    >
      <div class="panel-inner flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-sm muted">
          Mostrando {{ (currentPage-1)*pageSize + 1 }} - {{ Math.min(currentPage*pageSize, totalCount) }} de {{ totalCount }}
        </div>
        <div class="page-pagination">
          <BaseButton
            class="btn-secondary"
            :disabled="currentPage<=1"
            @click="prevPage"
          >
            Anterior
          </BaseButton>
          <template
            v-for="p in Math.min(5, totalPages)"
            :key="p"
          >
            <button
              type="button"
              class="page-number"
              :class="{ 'is-active': currentPage===p }"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
          </template>
          <BaseButton
            class="btn-secondary"
            :disabled="currentPage>=totalPages"
            @click="nextPage"
          >
            Próximo
          </BaseButton>
        </div>
      </div>
    </div>

    <SideDrawer
      :open="showCreateModal"
      title="Adicionar Compra"
      @close="closeCreateModal"
    >
      <form
        class="drawer-form space-y-5"
        @submit.prevent="createPurchase"
      >
        <FormFeedback
          :message="purchaseFeedback.message"
          :type="purchaseFeedback.type"
        />
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de Compra</label>
          <div class="flex gap-3">
            <button
              type="button"
              :class="['px-4 py-2 rounded-lg border', novaCompra.tipo === 'venda' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300']"
              @click="novaCompra.tipo = 'venda'"
            >
              Venda
            </button>
            <button
              type="button"
              :class="['px-4 py-2 rounded-lg border', novaCompra.tipo === 'revenda' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300']"
              @click="novaCompra.tipo = 'revenda'"
            >
              Revenda
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Fornecedor</label>
          <select
            v-model.number="novaCompra.fornecedor_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
          >
            <option
              :value="null"
              disabled
            >
              Escolha um fornecedor
            </option>
            <option
              v-for="f in fornecedores"
              :key="f.id"
              :value="f.id"
            >
              {{ f.razao_social }}
            </option>
          </select>
        </div>

        <div v-if="novaCompra.tipo === 'venda'">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Cliente</label>
          <select
            v-model.number="novaCompra.cliente_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
          >
            <option
              :value="null"
              disabled
            >
              Escolha um cliente
            </option>
            <option
              v-for="c in clientes"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nome }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Produto</label>
          <select
            v-model.number="novaCompra.produto_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
            @change="onProdutoChange"
          >
            <option
              :value="null"
              disabled
            >
              Escolha um produto
            </option>
            <option
              v-for="p in produtos"
              :key="p.id"
              :value="p.id"
            >
              {{ p.nome }}
            </option>
          </select>
        </div>

        <div v-if="novaCompra.tipo === 'venda'">
          <label class="text-sm text-gray-600 mb-1 block">Motorista</label>
          <select
            v-model.number="novaCompra.motorista_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select"
            required
          >
            <option
              :value="null"
              disabled
            >
              Escolha um motorista
            </option>
            <option
              v-for="m in motoristas"
              :key="m.id"
              :value="m.id"
            >
              {{ m.nome }}
            </option>
          </select>
        </div>

        <!-- Quantidade e Valor Unitário lado a lado -->
        <div class="flex flex-col md:flex-row gap-4 md:gap-6 items-start w-full mb-4">
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Quantidade</label>
            <input
              v-model.number="novaCompra.quantidade"
              type="number"
              placeholder="Quantidade"
              class="p-3 border border-gray-300 rounded-xl w-full"
              min="1"
              required
            >
          </div>
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Valor Unitário (R$)</label>
            <input
              v-model.number="novaCompra.valor_unitario"
              type="number"
              placeholder="Valor Unitário"
              class="p-3 border border-gray-300 rounded-xl w-full"
              step="0.01"
              required
            >
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Data de Envio</label>
            <input
              v-model="novaCompra.data_envio_prevista"
              type="date"
              class="p-3 border border-gray-300 rounded-xl w-full"
            >
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Data de Entrega</label>
            <input
              v-model="novaCompra.data_entrega_prevista"
              type="date"
              class="p-3 border border-gray-300 rounded-xl w-full"
            >
          </div>
        </div>
        <!-- Comissões centralizadas abaixo -->
        <div
          v-if="novaCompra.tipo === 'venda'"
          class="flex flex-col lg:flex-row justify-center gap-4 lg:gap-6 w-full mb-2"
        >
          <div class="bg-gray-50 rounded-2xl p-4 border-2 border-blue-300 shadow-sm flex flex-col gap-2 w-full lg:max-w-[320px]">
            <div class="font-semibold text-gray-700 flex items-center gap-2 text-base mb-1">
              <span class="flex w-7 h-7 bg-blue-100 text-blue-600 rounded-full items-center justify-center mr-2 text-xl">💰</span>
              Comissão Intermediação
            </div>
            <div class="flex items-center gap-3 mb-1">
              <label class="text-xs text-gray-600">Tipo:</label>
              <button
                type="button"
                :class="['relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none', novaCompra.comissao_intermediador_em_dinheiro ? 'bg-green-500' : 'bg-gray-300']"
                @click="novaCompra.comissao_intermediador_em_dinheiro = !novaCompra.comissao_intermediador_em_dinheiro"
              >
                <span :class="['absolute left-0 top-0 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200', novaCompra.comissao_intermediador_em_dinheiro ? 'translate-x-6' : '']" />
                <span
                  v-if="!novaCompra.comissao_intermediador_em_dinheiro"
                  class="absolute left-1 top-1 text-xs font-bold"
                >%</span>
                <span
                  v-if="novaCompra.comissao_intermediador_em_dinheiro"
                  class="absolute right-1 top-1 text-xs font-bold"
                >R$</span>
              </button>
              <span class="text-xs text-gray-700 font-semibold">{{ novaCompra.comissao_intermediador_em_dinheiro ? 'R$' : '%' }}</span>
            </div>
            <input
              v-model.number="novaCompra.comissao_intermediador"
              type="number"
              min="0"
              :max="novaCompra.comissao_intermediador_em_dinheiro ? null : 100"
              :step="novaCompra.comissao_intermediador_em_dinheiro ? '0.01' : '0.01'"
              placeholder="Comissão"
              class="p-2 border border-blue-300 rounded-xl w-full text-base"
            >
            <div class="text-xs text-gray-600 mt-1">
              <span v-if="novaCompra.comissao_intermediador_em_dinheiro">
                Ganho fixo: <span class="font-bold text-green-700">R$ {{ (novaCompra.comissao_intermediador || 0).toFixed(2) }}</span>
              </span>
              <span v-else>
                Comissão: <span class="font-bold">{{ novaCompra.comissao_intermediador || 0 }}%</span> <br>
                Valor: <span class="font-bold text-green-700">R$ {{ ((novaCompra.quantidade * novaCompra.valor_unitario) * (novaCompra.comissao_intermediador || 0) / 100).toFixed(2) }}</span>
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
              Total para o cliente: <span class="font-bold">R$ {{ (novaCompra.quantidade * novaCompra.valor_unitario).toFixed(2) }}</span>
            </div>
          </div>
          <div class="bg-gray-50 rounded-2xl p-4 border-2 border-green-300 shadow-sm flex flex-col gap-2 w-full lg:max-w-[320px]">
            <div class="font-semibold text-gray-700 flex items-center gap-2 text-base mb-1">
              <span class="flex w-7 h-7 bg-green-100 text-green-600 rounded-full items-center justify-center mr-2 text-xl">🚚</span>
              Comissão Motorista
            </div>
            <div class="flex items-center gap-3 mb-1">
              <label class="text-xs text-gray-600">Tipo:</label>
              <button
                type="button"
                :class="['relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none', novaCompra.comissao_motorista_em_dinheiro ? 'bg-green-500' : 'bg-gray-300']"
                @click="novaCompra.comissao_motorista_em_dinheiro = !novaCompra.comissao_motorista_em_dinheiro"
              >
                <span :class="['absolute left-0 top-0 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200', novaCompra.comissao_motorista_em_dinheiro ? 'translate-x-6' : '']" />
                <span
                  v-if="!novaCompra.comissao_motorista_em_dinheiro"
                  class="absolute left-1 top-1 text-xs font-bold"
                >%</span>
                <span
                  v-if="novaCompra.comissao_motorista_em_dinheiro"
                  class="absolute right-1 top-1 text-xs font-bold"
                >R$</span>
              </button>
              <span class="text-xs text-gray-700 font-semibold">{{ novaCompra.comissao_motorista_em_dinheiro ? 'R$' : '%' }}</span>
            </div>
            <input
              v-model.number="novaCompra.comissao_motorista"
              type="number"
              min="0"
              :max="novaCompra.comissao_motorista_em_dinheiro ? null : 100"
              :step="novaCompra.comissao_motorista_em_dinheiro ? '0.01' : '0.01'"
              placeholder="Comissão"
              class="p-2 border border-green-300 rounded-xl w-full text-base"
            >
            <div class="text-xs text-gray-600 mt-1">
              <span v-if="novaCompra.comissao_motorista_em_dinheiro">
                Ganho fixo: <span class="font-bold text-green-700">R$ {{ (novaCompra.comissao_motorista || 0).toFixed(2) }}</span>
              </span>
              <span v-else>
                Comissão: <span class="font-bold">{{ novaCompra.comissao_motorista || 0 }}%</span> <br>
                Valor: <span class="font-bold text-green-700">R$ {{ ((novaCompra.quantidade * novaCompra.valor_unitario) * (novaCompra.comissao_motorista || 0) / 100).toFixed(2) }}</span>
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
              Total para o cliente: <span class="font-bold">R$ {{ (novaCompra.quantidade * novaCompra.valor_unitario).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Duplicates and unused fields removed. Only lateralized box remains. -->
        <div class="drawer-actions flex justify-end gap-3 mt-6">
          <BaseButton
            type="button"
            class="btn-secondary"
            @click="closeCreateModal"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            class="btn-primary"
            :disabled="submittingPurchase"
            :loading="submittingPurchase"
          >
            {{ submittingPurchase ? 'Salvando...' : 'Adicionar Compra' }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>

    <SideDrawer
      :open="showEditModal"
      title="Editar Compra"
      @close="closeEditModal"
    >
      <form
        class="drawer-form space-y-4"
        @submit.prevent="saveEditPurchase"
      >
        <FormFeedback
          :message="editFeedback.message"
          :type="editFeedback.type"
        />
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de Compra</label>
          <select
            v-model="editCompra.tipo_operacao"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
          >
            <option value="venda">Venda</option>
            <option value="revenda">Revenda</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Fornecedor</label>
          <select
            v-model.number="editCompra.fornecedor_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
          >
            <option
              :value="null"
              disabled
            >
              Escolha um fornecedor
            </option>
            <option
              v-for="f in fornecedores"
              :key="f.id"
              :value="f.id"
            >
              {{ f.razao_social }}
            </option>
          </select>
        </div>

        <div v-if="editCompra.tipo_operacao === 'venda'">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Cliente</label>
          <select
            v-model.number="editCompra.cliente_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
          >
            <option
              :value="null"
            >
              Sem cliente
            </option>
            <option
              v-for="c in clientes"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nome }}
            </option>
          </select>
        </div>

        <div v-if="editCompra.tipo_operacao === 'venda'">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Motorista</label>
          <select
            v-model.number="editCompra.motorista_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
          >
            <option
              :value="null"
            >
              Sem motorista
            </option>
            <option
              v-for="m in motoristas"
              :key="m.id"
              :value="m.id"
            >
              {{ m.nome }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Data de Envio</label>
            <input
              v-model="editCompra.data_envio_prevista"
              type="date"
              class="p-3 border border-gray-300 rounded-xl w-full"
            >
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Data de Entrega</label>
            <input
              v-model="editCompra.data_entrega_prevista"
              type="date"
              class="p-3 border border-gray-300 rounded-xl w-full"
            >
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
          <select
            v-model="editCompra.status"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
          >
            <option value="NEGOCIADA">NEGOCIADA</option>
            <option value="RECEBIDA">RECEBIDA</option>
          </select>
        </div>

        <div class="drawer-actions flex justify-end gap-3 mt-6">
          <BaseButton
            type="button"
            class="btn-secondary"
            @click="closeEditModal"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            class="btn-primary"
            :disabled="submittingEdit"
            :loading="submittingEdit"
          >
            {{ submittingEdit ? 'Salvando...' : 'Salvar Edição' }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>
  </div>
</template>

<script>
import api from '../services/api'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable from '../components/ui/BaseTable.vue'
import SideDrawer from '../components/ui/SideDrawer.vue'
import PageHero from '../components/ui/PageHero.vue'
import ListState from '../components/ui/ListState.vue'
import FormFeedback from '../components/ui/FormFeedback.vue'

export default {
  components: { BaseButton, BaseTable, SideDrawer, PageHero, ListState, FormFeedback },
  data() {
    return {
      compras: [],
      fornecedores: [],
      produtos: [],
      motoristas: [],
      clientes: [],
      novaCompra: {
        tipo: 'venda',
        fornecedor_id: null,
        cliente_id: null,
        produto_id: null,
        motorista_id: null,
        comissao_intermediador: null,
        comissao_motorista: null,
        comissao_intermediador_em_dinheiro: true,
        comissao_motorista_em_dinheiro: true,
        data_envio_prevista: '',
        data_entrega_prevista: '',
        quantidade: 0,
        valor_unitario: 0,
      },
      loading: false,
      showCreateModal: false,
      showEditModal: false,
      submittingPurchase: false,
      submittingEdit: false,
      purchaseFeedback: { message: '', type: 'info' },
      editFeedback: { message: '', type: 'info' },
      editCompra: {
        id: null,
        tipo_operacao: 'revenda',
        fornecedor_id: null,
        cliente_id: null,
        motorista_id: null,
        data_envio_prevista: '',
        data_entrega_prevista: '',
        status: 'NEGOCIADA',
      },
      openActionsMenuId: null,
      openActionsMenuDirection: 'down',
      pageSize: 25,
      currentPage: 1,
      totalCount: 0,
    }
  },
  computed: {
    tableCols() { return [
      { key: 'numero_pedido', label: 'Pedido' },
      { key: 'tipo_operacao', label: 'Tipo' },
      { key: 'fornecedor', label: 'Fornecedor' },
      { key: 'cliente', label: 'Cliente' },
      { key: 'motorista', label: 'Motorista' },
      { key: 'itens_count', label: 'Itens' },
      { key: 'valor_total', label: 'Valor Total' },
      { key: 'status', label: 'Status' },
      { key: 'data_envio_prevista', label: 'Envio Previsto' },
      { key: 'data_entrega_prevista', label: 'Entrega Prevista' },
      { key: 'acoes', label: 'Ações' }
    ] },
    visibleCompras() {
      return (this.compras || []).filter(c => {
        if (!c) return false
        const hasFornecedor = c.fornecedor && String(c.fornecedor).trim().length > 0
        const hasItens = c.itens_count !== undefined && c.itens_count !== null
        const hasValor = c.valor_total !== undefined && c.valor_total !== null
        return hasFornecedor || hasItens || hasValor
      })
    },
    totalPages() { return Math.max(1, Math.ceil(this.totalCount / this.pageSize)) },
    paginatedCompras() { return this.visibleCompras },
  },
  mounted() {
    this.loadCompras()
    this.loadFornecedores()
    this.loadProdutos()
    this.loadMotoristas()
    this.loadClientes()
    document.addEventListener('click', this.handleDocumentClick)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleDocumentClick)
  },
  methods: {
    toggleActionsMenu(id, event) {
      if (this.openActionsMenuId === id) {
        this.openActionsMenuId = null
        return
      }

      this.openActionsMenuId = id
      this.$nextTick(() => {
        const trigger = event?.currentTarget
        if (!trigger) {
          this.openActionsMenuDirection = 'down'
          return
        }

        const rect = trigger.getBoundingClientRect()
        const menuHeight = 240
        const spaceBelow = window.innerHeight - rect.bottom
        const spaceAbove = rect.top

        this.openActionsMenuDirection = (spaceBelow < menuHeight && spaceAbove > spaceBelow) ? 'up' : 'down'
      })
    },
    closeActionsMenu() {
      this.openActionsMenuId = null
    },
    handleDocumentClick() {
      this.closeActionsMenu()
    },
    async loadCompras() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/compras', { params: { page: this.currentPage, per_page: this.pageSize } })
        this.compras = res.data.items || []
        this.totalCount = res.data.total || 0
      } catch (e) {
        console.error('Erro ao carregar compras:', e)
        this.compras = []
        this.totalCount = 0
      } finally {
        this.loading = false
      }
    },
    async createPurchase() {
      this.submittingPurchase = true
      this.purchaseFeedback = { message: 'Salvando compra...', type: 'info' }
      try {
        if (this.novaCompra.tipo === 'venda' && !this.novaCompra.cliente_id) {
          this.purchaseFeedback = { message: 'Selecione um cliente para compras do tipo venda.', type: 'error' }
          return
        }

        if (this.novaCompra.tipo === 'venda' && !this.novaCompra.motorista_id) {
          this.purchaseFeedback = { message: 'Selecione um motorista para compras do tipo venda.', type: 'error' }
          return
        }

        let payload = {
          tipo_operacao: this.novaCompra.tipo,
          produto_id: this.novaCompra.produto_id,
          quantidade: this.novaCompra.quantidade,
          valor_unitario: this.novaCompra.valor_unitario,
          fornecedor_id: this.novaCompra.fornecedor_id,
          motorista_id: this.novaCompra.tipo === 'venda' ? this.novaCompra.motorista_id : null,
          cliente_id: this.novaCompra.tipo === 'venda' ? this.novaCompra.cliente_id : null,
          data_envio_prevista: this.novaCompra.data_envio_prevista || null,
          data_entrega_prevista: this.novaCompra.data_entrega_prevista || null,
          items: [
            {
              produto_id: this.novaCompra.produto_id,
              quantidade: this.novaCompra.quantidade,
              valor_unitario: this.novaCompra.valor_unitario,
            }
          ],
        }

        if (this.novaCompra.tipo === 'venda') {
          payload = {
            ...payload,
            comissao_intermediador: this.novaCompra.comissao_intermediador,
            comissao_motorista: this.novaCompra.comissao_motorista,
            comissao_intermediador_em_dinheiro: this.novaCompra.comissao_intermediador_em_dinheiro,
            comissao_motorista_em_dinheiro: this.novaCompra.comissao_motorista_em_dinheiro,
          }
        }
        await api.post('/api/v1/compras', payload)
        this.purchaseFeedback = { message: 'Compra criada com sucesso.', type: 'success' }
        this.novaCompra = { tipo: 'venda', fornecedor_id: null, cliente_id: null, produto_id: null, motorista_id: null, comissao_intermediador: null, comissao_motorista: null, comissao_intermediador_em_dinheiro: true, comissao_motorista_em_dinheiro: true, data_envio_prevista: '', data_entrega_prevista: '', quantidade: 0, valor_unitario: 0 }
        setTimeout(() => { this.showCreateModal = false }, 350)
        this.loadCompras()
      } catch (e) {
        console.error('Erro ao criar compra:', e)
        this.purchaseFeedback = { message: e?.response?.data?.error || 'Não foi possível salvar a compra. Revise os dados e tente novamente.', type: 'error' }
      } finally {
        this.submittingPurchase = false
      }
    },
    async loadClientes() {
      try {
        const res = await api.get('/api/v1/clientes')
        this.clientes = res.data.items || res.data || []
      } catch (e) {
        console.error('Erro ao carregar clientes', e)
        this.clientes = []
      }
    },

    async loadFornecedores() {
      try {
        const res = await api.get('/api/v1/fornecedores')
        this.fornecedores = res.data || []
      } catch (e) {
        console.error('Erro ao carregar fornecedores', e)
        this.fornecedores = []
      }
    },

    async loadProdutos() {
      try {
        const res = await api.get('/api/v1/produtos', { params: { page: 1, per_page: 1000 } })
        this.produtos = res.data.items || []
      } catch (e) {
        console.error('Erro ao carregar produtos', e)
        this.produtos = []
      }
    },

    async loadMotoristas() {
      try {
        const res = await api.get('/api/v1/motoristas')
        this.motoristas = res.data || []
      } catch (e) {
        console.error('Erro ao carregar motoristas', e)
        this.motoristas = []
      }
    },

    onProdutoChange() {
      const pid = this.novaCompra.produto_id
      const prod = this.produtos.find(p => p.id === pid)
      if (prod && prod.custo_medio !== undefined) {
        this.novaCompra.valor_unitario = parseFloat(prod.custo_medio)
      }
    },
    toInputDate(value) {
      if (!value) return ''
      return String(value).slice(0, 10)
    },
    async openEditModal(row) {
      this.editFeedback = { message: '', type: 'info' }
      const res = await api.get(`/api/v1/compras/cabecalhos/${row.id}`)
      const header = res.data?.header || {}

      this.editCompra = {
        id: row.id,
        tipo_operacao: header.tipo_operacao || 'revenda',
        fornecedor_id: header.fornecedor_id ? Number(header.fornecedor_id) : null,
        cliente_id: header.cliente_id ? Number(header.cliente_id) : null,
        motorista_id: header.motorista_id ? Number(header.motorista_id) : null,
        data_envio_prevista: this.toInputDate(header.data_envio_prevista),
        data_entrega_prevista: this.toInputDate(header.data_entrega_prevista),
        status: header.status || 'NEGOCIADA',
      }
      this.showEditModal = true
    },
    closeEditModal() {
      this.showEditModal = false
      this.submittingEdit = false
      this.editFeedback = { message: '', type: 'info' }
      this.editCompra = {
        id: null,
        tipo_operacao: 'revenda',
        fornecedor_id: null,
        cliente_id: null,
        motorista_id: null,
        data_envio_prevista: '',
        data_entrega_prevista: '',
        status: 'NEGOCIADA',
      }
    },
    async saveEditPurchase() {
      if (!this.editCompra.id) return
      this.submittingEdit = true
      this.editFeedback = { message: 'Salvando edição...', type: 'info' }

      const payload = {
        tipo_operacao: this.editCompra.tipo_operacao,
        fornecedor_id: this.editCompra.fornecedor_id,
        cliente_id: this.editCompra.tipo_operacao === 'venda' ? this.editCompra.cliente_id : null,
        motorista_id: this.editCompra.tipo_operacao === 'venda' ? this.editCompra.motorista_id : null,
        data_envio_prevista: this.editCompra.data_envio_prevista || null,
        data_entrega_prevista: this.editCompra.data_entrega_prevista || null,
        status: this.editCompra.status || 'NEGOCIADA',
      }

      try {
        await api.patch(`/api/v1/compras/cabecalhos/${this.editCompra.id}`, payload)
        this.editFeedback = { message: 'Compra atualizada com sucesso.', type: 'success' }
        setTimeout(() => { this.showEditModal = false }, 300)
        this.loadCompras()
      } catch (err) {
        this.editFeedback = { message: err?.response?.data?.error || 'Não foi possível atualizar a compra.', type: 'error' }
      } finally {
        this.submittingEdit = false
      }
    },
    async deletePurchase(row) {
      const ok = window.confirm(`Deseja realmente excluir a compra #${row.id}?`)
      if (!ok) return

      try {
        await api.delete(`/api/v1/compras/cabecalhos/${row.id}`)
        this.loadCompras()
      } catch (err) {
        alert(err?.response?.data?.error || 'Não foi possível excluir a compra.')
      }
    },
    async confirmDelivery(row) {
      const ok = window.confirm(`Confirmar entrega da compra #${row.id}?`)
      if (!ok) return

      try {
        await api.post(`/api/v1/compras/cabecalhos/${row.id}/confirmar-entrega`, {})
        this.loadCompras()
      } catch (err) {
        alert(err?.response?.data?.error || 'Não foi possível confirmar a entrega.')
      }
    },
    openCreateModal() { this.showCreateModal = true; this.purchaseFeedback = { message: '', type: 'info' } },
    closeCreateModal() { this.showCreateModal = false; this.submittingPurchase = false; this.purchaseFeedback = { message: '', type: 'info' }; this.novaCompra = { tipo: 'venda', fornecedor_id: null, cliente_id: null, produto_id: null, motorista_id: null, comissao_intermediador: null, comissao_motorista: null, comissao_intermediador_em_dinheiro: true, comissao_motorista_em_dinheiro: true, data_envio_prevista: '', data_entrega_prevista: '', quantidade: 0, valor_unitario: 0 } },
    prevPage() { if (this.currentPage > 1) { this.currentPage--; this.loadCompras() } },
    nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.loadCompras() } },
    goToPage(n) { this.currentPage = Math.min(Math.max(1, n), this.totalPages); this.loadCompras() },
    openItems(id) {
      this.$router.push(`/compras/cabecalho/${id}`)
    },
    formatDate(value) {
      if (!value) return '-'
      const [year, month, day] = String(value).slice(0, 10).split('-')
      return year && month && day ? `${day}/${month}/${year}` : value
    },
    async printOrder(row) {
      let header = row
      let items = []

      try {
        const res = await api.get(`/api/v1/compras/cabecalhos/${row.id}`)
        header = res.data?.header || row
        items = Array.isArray(res.data?.items) ? res.data.items : []
      } catch (e) {
        items = []
      }

      const renderedItems = items.length
        ? items.map((item) => {
          const quantidade = Number(item.quantidade ?? 0)
          const valorUnitario = Number(item.valor_unitario ?? 0)
          const valorTotalItem = quantidade * valorUnitario
          return `<tr>
                    <td>${item.produto || '-'}</td>
                    <td>${quantidade || '-'}</td>
                    <td>R$ ${Number.isFinite(valorUnitario) ? valorUnitario.toFixed(2) : '-'}</td>
                    <td>R$ ${Number.isFinite(valorTotalItem) ? valorTotalItem.toFixed(2) : '-'}</td>
                  </tr>`
        }).join('')
        : `<tr><td colspan="4">Nenhum item encontrado.</td></tr>`

      const html = `
        <html>
          <head>
            <title>Ordem de Compra #${row.id}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 24px; color: #0f172a; }
              h1 { margin-bottom: 6px; }
              .muted { color: #64748b; margin-bottom: 18px; }
              h2 { margin-top: 22px; margin-bottom: 8px; font-size: 18px; }
              table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
              td, th { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
              th { background: #f8fafc; width: 32%; }
            </style>
          </head>
          <body>
            <h1>Ordem de Compra #${header.id || row.id}</h1>
            <div class="muted">Emitida em ${new Date().toLocaleString('pt-BR')}</div>

            <h2>Cabeçalho da Cotação</h2>
            <table>
              <tr><th>Tipo de Operação</th><td>${header.tipo_operacao || '-'}</td></tr>
              <tr><th>Fornecedor</th><td>${header.fornecedor || '-'}</td></tr>
              <tr><th>Cliente</th><td>${header.cliente || '-'}</td></tr>
              <tr><th>Motorista</th><td>${header.motorista || '-'}</td></tr>
              <tr><th>Status</th><td>${header.status || '-'}</td></tr>
              <tr><th>Data de Envio</th><td>${this.formatDate(header.data_envio_prevista)}</td></tr>
              <tr><th>Data de Entrega</th><td>${this.formatDate(header.data_entrega_prevista)}</td></tr>
            </table>

            <h2>Itens da Cotação</h2>
            <table>
              <thead>
                <tr>
                  <th style="width: 40%;">Produto</th>
                  <th style="width: 15%;">Quantidade</th>
                  <th style="width: 20%;">Valor Unitário</th>
                  <th style="width: 25%;">Valor Total</th>
                </tr>
              </thead>
              <tbody>
                ${renderedItems}
              </tbody>
            </table>

            <table>
              <tr><th>Itens</th><td>${items.length || header.itens_count || '-'}</td></tr>
              <tr><th>Total Geral</th><td>R$ ${Number(header.valor_total || row.valor_total || 0).toFixed(2)}</td></tr>
            </table>
          </body>
        </html>
      `
      const printWindow = window.open('', '_blank', 'width=900,height=700')
      if (!printWindow) return
      printWindow.document.write(html)
      printWindow.document.close()
      printWindow.focus()
      printWindow.print()
    },
  },
}
</script>
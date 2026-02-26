<template>
  <form @submit.prevent="handleSubmit">
    <input v-model="form.nome" type="text" placeholder="Nome" class="p-2 border rounded mb-2" />
    <div class="mb-2">
      <div class="segmented" role="tablist" aria-label="Tipo documento">
        <button :class="{ active: form.doc_type === 'cpf' }" type="button" @click="form.doc_type = 'cpf'">CPF</button>
        <button :class="{ active: form.doc_type === 'cnpj' }" type="button" @click="form.doc_type = 'cnpj'">CNPJ</button>
      </div>
      <input v-model="form.cpf_cnpj" type="text" :placeholder="form.doc_type === 'cpf' ? 'CPF' : 'CNPJ'" class="p-2 border rounded mt-2 w-full" />
    </div>
    <input v-model="form.telefone" type="text" placeholder="Telefone" class="p-2 border rounded mb-2" />
    <input v-model="form.email" type="email" placeholder="Email" class="p-2 border rounded mb-2" />
    <div class="select-wrapper mb-2" style="display:inline-block; min-width:88px">
      <select v-model="form.uf" :class="{ empty: !form.uf }" class="p-2 border rounded w-full input-uf" style="min-width:72px">
        <option value="" disabled>UF</option>
        <option v-for="s in ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']" :key="s" :value="s">{{ s }}</option>
      </select>
      <span v-if="!form.uf" class="select-placeholder">UF</span>
    </div>
    <label class="switch-label mb-2">
      <label class="switch" :class="{ active: form.status }">
        <input type="checkbox" v-model="form.status" />
        <span class="knob"></span>
      </label>
      <span class="text-sm status-text">{{ form.status ? 'ATIVO' : 'INATIVO' }}</span>
    </label>
    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Salvar</button>
  </form>
</template>

<script>
export default {
  data() {
    return {
      form: {
        nome: '',
        cpf_cnpj: '',
        doc_type: 'cpf',
        telefone: '',
        email: '',
        uf: '',
        status: true,
      },
    };
  },
  methods: {
    handleSubmit() {
      // basic validation: strip non-digits and check length
      const digits = (this.form.cpf_cnpj || '').toString().replace(/\D/g, '')
      if (this.form.doc_type === 'cpf' && digits.length !== 11) {
        alert('CPF inválido — insira 11 dígitos')
        return
      }
      if (this.form.doc_type === 'cnpj' && digits.length !== 14) {
        alert('CNPJ inválido — insira 14 dígitos')
        return
      }
      this.$emit('submit', this.form)
    },
  },
};
</script>

<style scoped>
/* Add any custom styles here */
</style>
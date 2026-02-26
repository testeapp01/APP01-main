<template>
  <label class="flex flex-col text-sm">
    <span class="mb-1 text-sm">{{ label }}</span>
    <input :type="type" v-model="localValue" :placeholder="placeholder" :aria-label="label" :class="['p-2 border rounded', inputClass]" />
  </label>
</template>

<script>
export default {
  name: 'BaseInput',
  props: { modelValue: [String, Number], label: String, placeholder: String, type: { type: String, default: 'text' }, inputClass: String },
  emits: ['update:modelValue'],
  computed: {
    localValue: {
      get() { return this.modelValue },
      set(v) { this.$emit('update:modelValue', v) }
    }
  }
}
</script>

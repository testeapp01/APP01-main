<template>
  <label class="flex flex-col text-sm">
    <span class="mb-1 text-sm">{{ label }}</span>
    <input
      v-model="localValue"
      :type="type"
      :placeholder="placeholder"
      :aria-label="label"
      :class="['p-2 border rounded', inputClass]"
    >
  </label>
</template>

<script>
export default {
  name: 'BaseInput',
  props: {
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    type: { type: String, default: 'text' },
    inputClass: { type: String, default: '' }
  },
  emits: ['update:modelValue'],
  computed: {
    localValue: {
      get() { return this.modelValue },
      set(v) { this.$emit('update:modelValue', v) }
    }
  }
}
</script>

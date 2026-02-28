<template>
  <div
    class="custom-select"
    tabindex="0"
    @keydown.tab="close"
    @keydown.esc="close"
  >
    <div
      class="custom-select__input"
      :class="{ open }"
      @click="toggle"
      @blur="close"
    >
      <span
        v-if="!selectedLabel"
        class="custom-select__placeholder"
      >{{ placeholder }}</span>
      <span
        v-else
        class="custom-select__value"
      >{{ selectedLabel }}</span>
      <span class="custom-select__arrow">▼</span>
    </div>
    <ul
      v-if="open"
      class="custom-select__dropdown"
    >
      <li
        v-for="option in options"
        :key="option.value"
        :class="{ selected: option.value === modelValue }"
        @mousedown.prevent="select(option.value)"
      >
        {{ option.label }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, required: true },
  placeholder: { type: String, default: 'Selecione...' }
})
const emit = defineEmits(['update:modelValue'])
const open = ref(false)
const selectedLabel = computed(() => {
  const found = props.options.find(o => o.value === props.modelValue)
  return found ? found.label : ''
})
function toggle() { open.value = !open.value }
function close() { open.value = false }
function select(val) { emit('update:modelValue', val); close() }
watch(() => props.modelValue, () => { if (!props.modelValue) close() })
</script>

<style scoped>
.custom-select {
  position: relative;
  min-width: 160px;
  outline: none;
}
.custom-select__input {
  background: linear-gradient(90deg, #f0fdf4 0%, #f9fafb 100%);
  border: 2px solid var(--leaf-500);
  border-radius: 12px;
  padding: 12px 40px 12px 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  min-height: 44px;
  font-size: 1rem;
  color: var(--foreground);
  transition: border-color .18s;
}
.custom-select__input.open,
.custom-select__input:focus {
  border-color: var(--leaf-600);
}
.custom-select__placeholder {
  color: var(--muted);
}
.custom-select__arrow {
  margin-left: auto;
  font-size: 1.1em;
  color: var(--muted);
}
.custom-select__dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 110%;
  background: #fff;
  border: 1.5px solid var(--leaf-500);
  border-radius: 10px;
  box-shadow: 0 8px 32px rgba(15,157,88,0.10);
  z-index: 20;
  margin: 0;
  padding: 0.25em 0;
  list-style: none;
  max-height: 220px;
  overflow-y: auto;
}
.custom-select__dropdown li {
  padding: 10px 18px;
  cursor: pointer;
  font-size: 1rem;
  color: var(--foreground);
  transition: background .13s;
}
.custom-select__dropdown li.selected,
.custom-select__dropdown li:hover {
  background: var(--leaf-500);
  color: #fff;
}
</style>

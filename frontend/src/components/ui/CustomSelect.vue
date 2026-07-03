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
      <span class="custom-select__arrow">▾</span>
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
  background: #FFFFFF;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  padding: 0.65rem 2.5rem 0.65rem 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  min-height: 40px;
  font-size: 0.95rem;
  font-weight: 500;
  color: #0F172A;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.custom-select__input:hover {
  border-color: #CBD5E1;
}

.custom-select__input.open,
.custom-select__input:focus {
  border-color: #16A34A;
  box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
}

.custom-select__placeholder {
  color: #94A3B8;
}

.custom-select__arrow {
  margin-left: auto;
  font-size: 1rem;
  color: #94A3B8;
}

.custom-select__dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 110%;
  background: #FFFFFF;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
  z-index: 20;
  margin: 0;
  padding: 0.5rem 0;
  list-style: none;
  max-height: 220px;
  overflow-y: auto;
}

.custom-select__dropdown li {
  padding: 0.65rem 1rem;
  cursor: pointer;
  font-size: 0.95rem;
  color: #0F172A;
  transition: background-color 0.2s ease;
}

.custom-select__dropdown li:hover {
  background: #F8FAFC;
}

.custom-select__dropdown li.selected {
  background: #F1F5F9;
  color: #16A34A;
  font-weight: 600;
}
</style>

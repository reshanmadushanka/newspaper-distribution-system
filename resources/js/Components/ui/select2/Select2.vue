<script setup>
import Multiselect from '@vueform/multiselect'
import { cn } from '@/lib/utils'

const props = defineProps({
  modelValue: { type: [String, Number, Array], default: null },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select option' },
  searchable: { type: Boolean, default: true },
  canClear: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  appendToBody: { type: Boolean, default: true },
  class: { type: null, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])
</script>

<template>
  <div :class="cn('w-full', props.class)">
    <Multiselect
      :model-value="modelValue"
      @update:model-value="emit('update:modelValue', $event)"
      @change="emit('change', $event)"
      :options="options"
      :placeholder="placeholder"
      :searchable="searchable"
      :can-clear="canClear"
      :disabled="disabled"
      :append-to-body="appendToBody"
      :close-on-scroll="true"
      class="custom-select2"
    />
  </div>
</template>

<style>
@import "@vueform/multiselect/themes/default.css";

.custom-select2 {
  --ms-radius: 0.5rem;
  --ms-border-color: var(--color-border);
  --ms-bg: var(--color-card);
  --ms-placeholder-color: var(--color-muted-foreground);
  --ms-ring-color: var(--color-ring);
  --ms-ring-width: 2px;
  --ms-dropdown-bg: var(--color-popover);
  --ms-dropdown-border-color: var(--color-border);
  --ms-dropdown-radius: 0.5rem;
  --ms-option-bg-pointed: var(--color-accent);
  --ms-option-color-pointed: var(--color-accent-foreground);
  --ms-option-bg-selected: var(--color-primary);
  --ms-option-color-selected: var(--color-primary-foreground);
  --ms-option-bg-selected-pointed: var(--color-primary);
  --ms-option-color-selected-pointed: var(--color-primary-foreground);
  
  font-size: 0.875rem;
  min-height: 2.5rem;
}

.multiselect-dropdown {
  z-index: 60;
}

.multiselect-placeholder {
  font-size: 0.875rem;
}

.multiselect-option {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}
</style>

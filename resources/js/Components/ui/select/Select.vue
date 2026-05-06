<script setup>
import { SelectRoot } from 'reka-ui'

const props = defineProps({
  open: { type: Boolean, required: false },
  defaultOpen: { type: Boolean, required: false },
  defaultValue: { type: String, required: false },
  modelValue: { type: [String, Number], required: false },
  dir: { type: String, required: false },
  name: { type: String, required: false },
  autocomplete: { type: String, required: false },
  disabled: { type: Boolean, required: false },
  required: { type: Boolean, required: false },
})

const emit = defineEmits(['update:modelValue', 'update:open'])
</script>

<template>
  <SelectRoot
    v-bind="props"
    @update:model-value="emit('update:modelValue', $event)"
    @update:open="emit('update:open', $event)"
  >
    <slot />
  </SelectRoot>
</template>

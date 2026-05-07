<script setup>
import { SelectValue } from 'reka-ui'

const props = defineProps({
  placeholder: { type: String, required: false },
})
</script>

<template>
  <SelectValue v-bind="props">
    <slot />
  </SelectValue>
</template>

<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    class: { type: null, default: '' },
})

const emit = defineEmits(['update:modelValue'])

function onInput(event) {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
    <textarea
        :value="modelValue"
        @input="onInput"
        v-bind="$attrs"
        :class="cn('flex min-h-[80px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm outline-none transition-colors placeholder:text-muted-foreground focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50', props.class)"
    />
</template>

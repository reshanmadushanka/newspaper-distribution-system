<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: null, default: '' },
})
</script>

<template>
    <input :class="cn('flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm outline-none transition-colors placeholder:text-muted-foreground focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50', props.class)" />
</template>

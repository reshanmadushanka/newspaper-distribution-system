<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: null, default: '' },
})
</script>

<template>
    <span :class="cn('inline-flex items-center rounded-md border bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground', props.class)">
        <slot />
    </span>
</template>

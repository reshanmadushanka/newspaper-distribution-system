<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: null, default: '' },
    variant: { type: String, default: 'default' },
    size: { type: String, default: 'default' },
})

const variants = {
    default: 'bg-primary text-primary-foreground hover:bg-primary/90',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
    outline: 'border bg-card hover:bg-muted',
    ghost: 'hover:bg-muted',
    destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
}

const sizes = {
    default: 'h-10 px-4 py-2',
    sm: 'h-9 px-3',
    lg: 'h-11 px-8',
}
</script>

<template>
    <button :class="cn('inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50', variants[variant], sizes[size], props.class)">
        <slot />
    </button>
</template>

<script setup>
import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'

const page = usePage()
const flash = computed(() => page.props.flash)

// Show toast on flash message changes, including initial load
watch(flash, (newFlash) => {
    if (newFlash?.success) {
        toast.success(newFlash.success)
    }
    if (newFlash?.error) {
        toast.error(newFlash.error)
    }
}, { deep: true, immediate: true })
</script>

<template>
    <div></div>
</template>

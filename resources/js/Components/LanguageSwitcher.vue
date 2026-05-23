<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { Languages, ChevronDown } from 'lucide-vue-next'
import { ref, computed } from 'vue'

const page = usePage()
const isOpen = ref(false)

const currentLocale = computed(() => page.props.locale)
const availableLocales = computed(() => page.props.available_locales)

const switchLocale = (locale) => {
    router.patch('/locale', { locale }, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false
        }
    })
}

const getCurrentLocaleName = () => {
    return availableLocales.value[currentLocale.value] || 'English'
}
</script>

<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            @keydown.escape="isOpen = false"
            class="flex items-center gap-2 rounded-full border bg-card px-3 py-2 text-sm font-medium transition-all hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary/50"
            :class="{ 'ring-2 ring-primary/50': isOpen }"
            aria-haspopup="true"
            :aria-expanded="isOpen"
        >
            <Languages class="h-4 w-4" />
            <span class="hidden sm:inline">{{ getCurrentLocaleName() }}</span>
            <ChevronDown class="h-3 w-3 transition-transform" :class="{ 'rotate-180': isOpen }" />
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute right-0 mt-2 w-48 rounded-xl border bg-card shadow-lg shadow-black/5 py-1 z-50"
            @click.away="isOpen = false"
        >
            <button
                v-for="(name, locale) in availableLocales"
                :key="locale"
                @click="switchLocale(locale)"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm transition-colors hover:bg-secondary"
                :class="{
                    'bg-primary/10 text-primary font-semibold': currentLocale === locale,
                    'text-muted-foreground': currentLocale !== locale
                }"
            >
                <span class="flex-1 text-left">{{ name }}</span>
                <svg
                    v-if="currentLocale === locale"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>

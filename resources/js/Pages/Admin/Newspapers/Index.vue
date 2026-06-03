<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, Newspaper, Search, ChevronDown } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm'
import { useTranslation } from '@/Composables/useTranslation'
import { nextTick, onUnmounted, ref, watch } from 'vue'

const { t } = useTranslation()

const props = defineProps({
    newspapers: Object,
    filters: Object,
    canCreate: { type: Boolean, default: true },
    canEdit: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: true },
})

const search = ref(props.filters?.search ?? '')
let searchTimeout = null

const { deleting, confirmDelete } = useDeleteConfirm(t('common.cannot_undo') + ' ' + t('common.delete_confirm'))

const reloadNewspapers = () => {
    router.get('/admin/newspapers', {
        search: search.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['newspapers', 'filters'],
    })
}

watch(search, () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        reloadNewspapers()
    }, 350)
})

onUnmounted(() => {
    clearTimeout(searchTimeout)
    closeVariants()
})

const handleDelete = (newspaperId) => {
    confirmDelete(() =>
        router.delete(`/admin/newspapers/${newspaperId}`, {
            onError: (errors) => {
                Swal.fire(t('common.error') + '!', Object.values(errors)[0] || 'Failed to delete newspaper.', 'error')
            }
        })
    )
}

const languageVariant = (lang) => {
    switch (lang) {
        case 'English': return 'default'
        case 'Tamil': return 'secondary'
        case 'Sinhala': return 'outline'
        default: return 'secondary'
    }
}

const fmt = (value) => Number(value ?? 0).toFixed(2)

// Variant price popover --------------------------------------------------
const activeNewspaper = ref(null)
const popoverStyle = ref({})
const popoverEl = ref(null)

const onDocMouseDown = (e) => {
    if (popoverEl.value && !popoverEl.value.contains(e.target)) {
        closeVariants()
    }
}

const onKeydown = (e) => {
    if (e.key === 'Escape') closeVariants()
}

const closeVariants = () => {
    activeNewspaper.value = null
    document.removeEventListener('mousedown', onDocMouseDown)
    document.removeEventListener('keydown', onKeydown)
    window.removeEventListener('scroll', closeVariants, true)
    window.removeEventListener('resize', closeVariants)
}

const toggleVariants = async (newspaper, event) => {
    if (activeNewspaper.value?.id === newspaper.id) {
        closeVariants()
        return
    }

    const rect = event.currentTarget.getBoundingClientRect()
    const width = 300
    let left = Math.min(rect.right - width, window.innerWidth - width - 12)
    if (left < 12) left = 12

    popoverStyle.value = {
        top: `${rect.bottom + 6}px`,
        left: `${left}px`,
        width: `${width}px`,
    }
    activeNewspaper.value = newspaper

    await nextTick()

    // Flip above the trigger if it would overflow the bottom of the viewport.
    const height = popoverEl.value?.offsetHeight ?? 0
    if (rect.bottom + 6 + height > window.innerHeight - 12) {
        popoverStyle.value = {
            ...popoverStyle.value,
            top: `${Math.max(12, rect.top - 6 - height)}px`,
        }
    }

    document.addEventListener('mousedown', onDocMouseDown)
    document.addEventListener('keydown', onKeydown)
    window.addEventListener('scroll', closeVariants, true)
    window.addEventListener('resize', closeVariants)
}
</script>

<template>

    <Head :title="t('navigation.newspapers')" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <Newspaper class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">{{ t('navigation.newspapers') }}</h2>
                </div>
                <p class="text-muted-foreground">{{ t('newspapers.manage_description') }}</p>
            </div>
            <Link v-if="canCreate" href="/admin/newspapers/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ t('common.create') }} {{ t('navigation.newspapers') }}
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                 <div class="border-b bg-card px-6 py-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <input v-model="search" type="text" :placeholder="t('newspapers.search_placeholder')"
                                class="w-full h-9 pl-9 pr-4 rounded-lg border bg-secondary/30 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
                        </div>
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ t('common.showing') }} {{ newspapers.data.length }} {{ t('navigation.newspapers') }}
                    </div>
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">{{ t('common.name') }}</th>
                            <th class="px-6 py-4">{{ t('newspapers.language') }}</th>
                            <th class="px-6 py-4">{{ t('newspapers.frequency') }}</th>
                            <th class="px-6 py-4">{{ t('newspapers.price_variants') }}</th>
                            <th class="px-6 py-4 text-right">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="newspaper in newspapers.data" :key="newspaper.id"
                            class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-foreground">{{ newspaper.name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="languageVariant(newspaper.language)"
                                    class="rounded-full px-2 py-0 text-[10px]">
                                    {{ newspaper.language }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-medium">{{ newspaper.frequency }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-muted-foreground">
                                    <template v-if="newspaper.prices?.length">
                                        <button type="button" @click.stop="toggleVariants(newspaper, $event)"
                                            class="inline-flex items-center gap-1 rounded-full border bg-secondary/40 px-2.5 py-1 text-[11px] font-medium text-foreground transition-colors hover:bg-secondary"
                                            :class="{ 'bg-secondary ring-2 ring-primary/30': activeNewspaper?.id === newspaper.id }">
                                            <span>{{ newspaper.prices.length }} variant{{ newspaper.prices.length > 1 ? 's' : '' }}</span>
                                            <ChevronDown class="h-3 w-3 transition-transform"
                                                :class="{ 'rotate-180': activeNewspaper?.id === newspaper.id }" />
                                        </button>
                                        <span class="mt-0.5 block text-[10px] text-muted-foreground/60">
                                            From Rs. {{Math.min(...newspaper.prices.map(p =>
                                            parseFloat(p.price))).toFixed(2) }}
                                        </span>
                                    </template>
                                    <span v-else class="text-muted-foreground/50">—</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Link v-if="canEdit" :href="`/admin/newspapers/${newspaper.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <button v-if="canDelete" @click="handleDelete(newspaper.id)"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                        :disabled="deleting">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="newspapers.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-muted-foreground italic">
                                {{ search ? 'No newspapers match your search.' : 'No newspapers found. Click "Add newspaper" to get started.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="newspapers.links && newspapers.links.length > 3"
                class="px-6 py-4 border-t bg-secondary/10 flex items-center justify-center gap-2">
                <Link v-for="link in newspapers.links" :key="link.label" :href="link.url || ''" preserve-scroll>
                    <Button :variant="link.active ? 'default' : 'ghost'" size="sm" :disabled="!link.url"
                        class="h-8 min-w-[2rem] rounded-lg" v-html="link.label" />
                </Link>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="activeNewspaper" ref="popoverEl" :style="popoverStyle"
                class="fixed z-50 overflow-hidden rounded-xl border bg-card shadow-xl ring-1 ring-black/5">
                <div class="border-b bg-secondary/30 px-3 py-2">
                    <div class="truncate text-xs font-semibold text-foreground">{{ activeNewspaper.name }}</div>
                    <div class="text-[10px] uppercase tracking-wider text-muted-foreground">
                        {{ t('newspapers.price_variants') }}
                    </div>
                </div>
                <table class="w-full text-xs">
                    <thead class="text-[10px] uppercase tracking-wider text-muted-foreground">
                        <tr>
                            <th class="px-3 py-1.5 text-left font-medium">{{ t('common.label') }}</th>
                            <th class="px-3 py-1.5 text-right font-medium">{{ t('common.price') }}</th>
                            <th class="px-3 py-1.5 text-right font-medium">{{ t('common.cost_price') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="(variant, index) in activeNewspaper.prices" :key="variant.id ?? index">
                            <td class="px-3 py-1.5 text-foreground">{{ variant.label || '—' }}</td>
                            <td class="px-3 py-1.5 text-right font-semibold text-green-600">Rs. {{ fmt(variant.price) }}</td>
                            <td class="px-3 py-1.5 text-right text-muted-foreground">
                                {{ variant.cost_price != null ? `Rs. ${fmt(variant.cost_price)}` : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Teleport>
    </AdminLayout>
</template>

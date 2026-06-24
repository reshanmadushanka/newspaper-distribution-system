<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { Plus, FileText, Eye, Pencil, Trash2, Store, CalendarDays, CheckCircle2, Search, Printer, Sparkles, Tag } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { Datepicker } from '@/Components/ui/datepicker'
import { useTranslation } from '@/Composables/useTranslation'
import { computed, onMounted, onUnmounted, watch, ref } from 'vue'
import { useSessionStorage } from '@vueuse/core'
import Swal from 'sweetalert2'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm.js'
import AutoGenerateInvoiceModal from '@/Components/Admin/AutoGenerateInvoiceModal.vue'

const { t } = useTranslation()

const componentProps = defineProps({
    invoices: Object,
    filters: Object,
})

const page = usePage()
const { props } = page
const permissions = computed(() => props.auth.user?.permissions ?? [])
const currentQuery = new URLSearchParams(page.url.split('?')[1] ?? '')
const hasFilterQuery = ['search', 'date_from', 'date_to', 'invoice_type'].some((key) => currentQuery.has(key))
const rememberedFilters = useSessionStorage('admin.invoices.index.filters', {
    search: componentProps.filters?.search ?? '',
    dateRange: [
        componentProps.filters?.date_from ?? '',
        componentProps.filters?.date_to ?? '',
    ],
    invoiceType: componentProps.filters?.invoice_type ?? '',
}, { mergeDefaults: true })

if (hasFilterQuery) {
    rememberedFilters.value = {
        search: componentProps.filters?.search ?? '',
        dateRange: [
            componentProps.filters?.date_from ?? '',
            componentProps.filters?.date_to ?? '',
        ],
        invoiceType: componentProps.filters?.invoice_type ?? '',
    }
}

const search = computed({
    get: () => rememberedFilters.value.search ?? '',
    set: (value) => {
        rememberedFilters.value.search = value
    },
})

const dateRange = computed({
    get: () => Array.isArray(rememberedFilters.value.dateRange) ? rememberedFilters.value.dateRange : ['', ''],
    set: (value) => {
        rememberedFilters.value.dateRange = Array.isArray(value) ? value : ['', '']
    },
})

const invoiceType = computed({
    get: () => rememberedFilters.value.invoiceType ?? '',
    set: (value) => {
        rememberedFilters.value.invoiceType = value
    },
})
let searchTimeout = null

const canCreate = computed(() => permissions.value.includes('create invoices') || permissions.value.includes('manage invoices'))
const canUpdate = computed(() => permissions.value.includes('manage invoices'))
const canAutoGenerate = computed(() => permissions.value.includes('auto generate invoice'))

const showAutoGenerateModal = ref(false)

const openAutoGenerateModal = () => {
    showAutoGenerateModal.value = true
}

const handleAutoGenerateComplete = () => {
    reloadInvoices()
}

const { confirmDelete } = useDeleteConfirm(t('common.cannot_undo') + ' ' + t('invoices.delete_confirm'))

const handleDelete = (id) => {
    confirmDelete(() => router.delete(`/admin/invoices/${id}`, {
        onError: (errors) => Swal.fire(t('common.error') + '!', Object.values(errors)[0], 'error'),
    }))
}

const printInvoice = (id) => {
    // Mark as printed first. The controller redirects back to the current page,
    // so the "Printed" badge refreshes while pagination/filters stay put.
    router.patch(`/admin/invoices/${id}/mark-printed`, {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Then open print popup
            const width = 900
            const height = 700
            const left = window.screenX + (window.outerWidth - width) / 2
            const top = window.screenY + (window.outerHeight - height) / 2

            const popup = window.open(
                `/admin/invoices/${id}?print=1`,
                `invoice-print-${id}`,
                `popup=yes,width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
            )

            popup?.focus()
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to mark invoice as printed.', 'error')
        }
    })
}

const reloadInvoices = () => {
    const [dateFrom, dateTo] = dateRange.value

    if (!dateFrom || !dateTo) {
        return
    }

    router.get('/admin/invoices', {
        search: search.value || undefined,
        date_from: dateFrom,
        date_to: dateTo,
        invoice_type: invoiceType.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['invoices', 'filters'],
    })
}

onMounted(() => {
    const [dateFrom, dateTo] = dateRange.value
    const propDateFrom = componentProps.filters?.date_from ?? ''
    const propDateTo = componentProps.filters?.date_to ?? ''
    const propSearch = componentProps.filters?.search ?? ''
    const propInvoiceType = componentProps.filters?.invoice_type ?? ''

    if (!dateFrom || !dateTo) {
        return
    }

    if (dateFrom !== propDateFrom || dateTo !== propDateTo || search.value !== propSearch || invoiceType.value !== propInvoiceType) {
        reloadInvoices()
    }
})

watch([search, dateRange, invoiceType], () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        reloadInvoices()
    }, 350)
}, { deep: true })

onUnmounted(() => {
    clearTimeout(searchTimeout)
})

const markAsPaid = (id) => {
    Swal.fire({
        title: 'Mark as Paid?',
        text: t('invoices.mark_paid_confirm'),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'Yes, mark as paid',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/admin/invoices/${id}/mark-paid`, {}, {
                onSuccess: () => {
                    Swal.fire({
                        title: t('invoices.paid') + '!',
                        text: t('invoices.marked_paid_message'),
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}

const statusVariant = (status) => {
    const map = {
        draft: 'secondary',
        paid: 'success',
        cancelled: 'destructive',
    }
    return map[status] || 'secondary'
}

const isPrinted = (invoice) => {
    return !!invoice.printed_at
}
</script>

<template>

    <Head :title="t('navigation.invoices')" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <FileText class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">{{ t('navigation.invoices') }}</h2>
                </div>
                <p class="text-muted-foreground">{{ t('invoices.manage_description') }}</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <Button v-if="canAutoGenerate"
                    @click="openAutoGenerateModal"
                    variant="outline"
                    class="rounded-xl px-5 shadow-lg shadow-purple-500/10 transition-all hover:-translate-y-0.5 border-purple-300 hover:bg-purple-50"
                >
                    <Sparkles class="mr-2 h-4 w-4 text-purple-600" />
                    {{ t('invoices.auto_generate') }}
                </Button>
                <Link v-if="canCreate" href="/admin/invoices/create">
                    <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                        <Plus class="mr-2 h-4 w-4" />
                        {{ t('invoices.create_invoice') }}
                    </Button>
                </Link>
            </div>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <div class="border-b bg-card px-6 py-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <input v-model="search" type="text" :placeholder="t('invoices.search_placeholder')"
                                class="w-full h-9 pl-9 pr-4 rounded-lg border bg-secondary/30 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
                        </div>
                        <div class="relative w-full sm:w-72">
                            <CalendarDays class="absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Datepicker v-model="dateRange" mode="range" :placeholder="t('invoices.date_range_placeholder')"
                                class="w-full h-9 pl-9 pr-4 rounded-lg border bg-secondary/30 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <select v-model="invoiceType"
                                class="h-9 w-full sm:w-auto rounded-lg border bg-secondary/30 text-sm px-3 focus:outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer">
                                <option value="">{{ t('invoices.type_all') }}</option>
                                <option value="daily">{{ t('invoices.type_daily') }}</option>
                                <option value="monthly">{{ t('invoices.type_monthly') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ t('common.showing') }} {{ invoices?.data?.length ?? 0 }} {{ t('navigation.invoices') }}
                    </div>
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">{{ t('invoices.invoice') }}</th>
                            <th class="px-6 py-4">{{ t('invoices.shop') }}</th>
                            <th class="px-6 py-4">{{ t('common.date') }}</th>
                            <th class="px-6 py-4 text-right">{{ t('common.amount') }}</th>
                            <th class="px-6 py-4 text-right">{{ t('invoices.return_total') }}</th>
                            <th class="px-6 py-4">{{ t('common.status') }}</th>
                            <th class="px-6 py-4">{{ t('invoices.printed') }}</th>
                            <th class="px-6 py-4">{{ t('invoices.invoice_type') }}</th>
                            <th class="px-6 py-4 text-right">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="inv in invoices?.data ?? []" :key="inv.id"
                            class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-foreground">#{{ inv.id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Store class="h-3.5 w-3.5 text-muted-foreground" />
                                    <span>{{ inv.shop?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    <CalendarDays class="h-3.5 w-3.5" />
                                    <span>{{ inv.invoice_date }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                Rs. {{ parseFloat(inv.total_amount).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-destructive">
                                Rs. {{ parseFloat(inv.return_total_amount || 0).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="statusVariant(inv.status)"
                                    class="rounded-full px-2 py-0 text-[10px] capitalize">
                                    {{ inv.status }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <Badge v-if="isPrinted(inv)" variant="success"
                                    class="rounded-full px-2 py-0 text-[10px]">
                                    Printed
                                </Badge>
                                <Badge v-else variant="outline"
                                    class="rounded-full px-2 py-0 text-[10px] text-muted-foreground">
                                    Not Printed
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="inv.invoice_type === 'monthly' ? 'warning' : 'secondary'"
                                    class="rounded-full px-2 py-0 text-[10px] capitalize">
                                    {{ inv.invoice_type }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Button v-if="canUpdate && inv.status === 'draft'" @click="markAsPaid(inv.id)"
                                        variant="ghost" size="icon"
                                        class="h-8 w-8 rounded-lg text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50"
                                        title="Mark as Paid">
                                        <CheckCircle2 class="h-4 w-4" />
                                    </Button>
                                    <Link v-if="canUpdate && inv.status === 'draft'"
                                        :href="`/admin/invoices/${inv.id}/edit`">
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 rounded-lg text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                                            title="Edit Items">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button v-if="canUpdate" @click="handleDelete(inv.id)" variant="ghost" size="icon"
                                        class="h-8 w-8 rounded-lg text-destructive hover:text-destructive hover:bg-destructive/10"
                                        title="Delete">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                    <Link :href="`/admin/invoices/${inv.id}`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button @click="printInvoice(inv.id)" variant="ghost" size="icon"
                                        class="h-8 w-8 rounded-lg" title="Print">
                                        <Printer class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!invoices?.data || invoices.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-muted-foreground italic">
                                {{ search ? 'No invoices match your search.' : 'No invoices found. Click "Create Invoice" to get started.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="invoices?.links && invoices.links.length > 3"
                class="px-6 py-4 border-t bg-secondary/10 flex items-center justify-center gap-2">
                <Link v-for="link in invoices.links" :key="link.label" :href="link.url || ''" preserve-scroll preserve-state>
                    <Button :variant="link.active ? 'default' : 'ghost'" size="sm" :disabled="!link.url"
                        class="h-8 min-w-[2rem] rounded-lg" v-html="link.label" />
                </Link>
            </div>
        </div>

        <!-- Auto-Generate Modal -->
        <AutoGenerateInvoiceModal
            v-if="showAutoGenerateModal"
            @close="showAutoGenerateModal = false"
            @completed="handleAutoGenerateComplete"
        />
    </AdminLayout>
</template>

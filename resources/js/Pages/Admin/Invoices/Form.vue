<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ChevronLeft, Save, Plus, Trash2, Store, Newspaper, StickyNote, Tags, Calendar, Copy } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Datepicker } from '@/Components/ui/datepicker'
import { Label } from '@/Components/ui/label'
import { Select2 } from '@/Components/ui/select2'
import { useTranslation } from '@/Composables/useTranslation'
import { computed, ref, watch } from 'vue'
import Swal from 'sweetalert2'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm.js'
import { History, AlertCircle, Loader2 } from 'lucide-vue-next'

const { t } = useTranslation()

const props = defineProps({
    auth: Object,
    invoice: Object,
    shops: Array,
    newspapers: Array,
    previousWeekSummary: Object,
})

const isEditing = computed(() => !!props.invoice)

const permissions = computed(() => props.auth?.user?.permissions ?? [])
const canEditPastInvoice = computed(() => permissions.value.includes('edit past invoices'))

const tomorrow = new Date()
tomorrow.setDate(tomorrow.getDate() + 1)
const defaultDate = tomorrow.toISOString().split('T')[0]

const form = useForm({
    invoice_date: isEditing.value ? props.invoice.invoice_date : defaultDate,
    shop_id: isEditing.value ? props.invoice.shop_id : '',
    invoice_type: isEditing.value ? (props.invoice.invoice_type || 'daily') : 'daily',
    notes: isEditing.value ? (props.invoice.notes || '') : '',
    previous_deficit: isEditing.value ? parseFloat(props.invoice.previous_deficit) || 0 : 0,
    special_discount: isEditing.value ? parseFloat(props.invoice.special_discount) || 0 : 0,
    items: isEditing.value
        ? props.invoice.items.map(item => ({
            newspaper_id: item.newspaper_id.toString(),
            price_id: item.price_id ? parseInt(item.price_id) : '',
            quantity: item.quantity,
            unit_price: parseFloat(item.unit_price),
            return_quantity: item.return_quantity || 0,
        }))
        : [{ newspaper_id: '', price_id: '', quantity: 1, unit_price: 0 }],
})

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)
    }, 0)
})

const totalReturnAmount = computed(() => {
    if (!isEditing.value) return 0
    return form.items.reduce((sum, item) => {
        return sum + (parseFloat(item.return_quantity) || 0) * (parseFloat(item.unit_price) || 0)
    }, 0)
})

const netAmount = computed(() => {
    const deficit = parseFloat(form.previous_deficit) || 0
    const discount = parseFloat(form.special_discount) || 0
    return totalAmount.value - totalReturnAmount.value + deficit - discount
})

const newspaperOptions = computed(() => {
    return props.newspapers.map(np => ({
        value: np.id,
        label: `${np.name}`,
    }))
})

const shopOptions = computed(() => {
    return props.shops.map(shop => ({
        value: shop.id,
        label: shop.name,
    }))
})

const getPricesForNewspaper = (newspaperId) => {
    const np = props.newspapers.find(n => n.id === parseInt(newspaperId))
    if (!np || !np.prices) return []
    return np.prices.map(p => ({
        value: p.id,
        label: p.label ? `${p.label} (Rs. ${parseFloat(p.price).toFixed(2)})` : `Rs. ${parseFloat(p.price).toFixed(2)}`,
        price: parseFloat(p.price),
    }))
}

const priceOptions = (index) => {
    const newspaperId = form.items[index].newspaper_id
    if (!newspaperId) return []
    return getPricesForNewspaper(newspaperId)
}

const handleNewspaperChange = (index, value) => {
    const selectedId = parseInt(value)
    form.items[index].newspaper_id = value
    form.items[index].price_id = ''
    form.items[index].unit_price = 0
    if (isEditing.value) {
        form.items[index].return_quantity = 0
    }

    const isDuplicate = form.items.some((item, idx) => {
        return idx !== index && parseInt(item.newspaper_id) === selectedId
    })

    if (isDuplicate) {
        form.items[index].newspaper_id = ''
        form.errors[`items.${index}.newspaper_id`] = 'This newspaper is already added.'
        return
    }

    if (form.errors[`items.${index}.newspaper_id`]) {
        delete form.errors[`items.${index}.newspaper_id`]
    }

    const prices = getPricesForNewspaper(value)
    if (prices.length === 1) {
        form.items[index].price_id = parseInt(prices[0].value)
        form.items[index].unit_price = prices[0].price
    }
}

const handleReturnQuantityChange = (index) => {
    const returnQty = parseInt(form.items[index].return_quantity) || 0
    const currentQty = parseInt(form.items[index].quantity) || 0

    if (returnQty > currentQty) {
        form.errors[`items.${index}.return_quantity`] = `Return quantity cannot exceed current quantity (${currentQty}).`
        form.items[index].return_quantity = currentQty
    } else if (returnQty < 0) {
        form.errors[`items.${index}.return_quantity`] = 'Return quantity cannot be negative.'
        form.items[index].return_quantity = 0
    } else {
        if (form.errors[`items.${index}.return_quantity`]) {
            delete form.errors[`items.${index}.return_quantity`]
        }
    }
}

const handlePriceChange = (index, value) => {
    form.items[index].price_id = parseInt(value)
    const prices = priceOptions(index)
    const selected = prices.find(p => p.value === parseInt(value))
    form.items[index].unit_price = selected ? selected.price : 0
}

const rowTotal = (index) => {
    const qty = parseFloat(form.items[index].quantity) || 0
    const price = parseFloat(form.items[index].unit_price) || 0
    return qty * price
}

const isLoadingSummary = ref(false)
const deletingItemId = ref(null)

// Delete confirmation (SweetAlert2)
// We'll call `useDeleteConfirm` per-invocation to allow dynamic messages

const filledFromLastWeek = ref(false)

const fetchPreviousWeekSummary = () => {
    if (!form.invoice_date || !form.shop_id) {
        return
    }

    filledFromLastWeek.value = false
    isLoadingSummary.value = true
    router.reload({
        data: {
            date: form.invoice_date,
            shop_id: form.shop_id
        },
        only: ['previousWeekSummary'],
        onFinish: () => {
            isLoadingSummary.value = false
        }
    })
}

watch(() => [form.invoice_date, form.shop_id], () => {
    if (!isEditing.value) {
        fetchPreviousWeekSummary()
    }
})

// The form is considered "pristine" while it still only holds the single,
// empty default row — i.e. the user has not started entering items yet.
const itemsArePristine = () => {
    return form.items.length === 1
        && !form.items[0].newspaper_id
        && (parseInt(form.items[0].quantity) || 0) <= 1
}

// Build form rows from last week's same-day invoice items. Quantities are
// copied as-is; pricing is resolved against the newspaper's *current* prices
// so a price change since last week is reflected. Newspapers that are no
// longer active (not in the options list) are skipped.
const mapSummaryItemsToForm = () => {
    const summary = props.previousWeekSummary
    if (!summary?.items?.length) return []

    return summary.items
        .map((item) => {
            const newspaper = props.newspapers.find(n => n.id === parseInt(item.newspaper_id))
            if (!newspaper) return null

            const prices = newspaper.prices || []
            const matched = item.price_id
                ? prices.find(p => p.id === parseInt(item.price_id))
                : null

            let priceId = ''
            let unitPrice = 0
            if (matched) {
                priceId = matched.id
                unitPrice = parseFloat(matched.price)
            } else if (prices.length === 1) {
                priceId = prices[0].id
                unitPrice = parseFloat(prices[0].price)
            }

            return {
                newspaper_id: item.newspaper_id.toString(),
                price_id: priceId,
                quantity: parseInt(item.quantity) || 1,
                unit_price: unitPrice,
            }
        })
        .filter(Boolean)
}

const applyPreviousWeekItems = () => {
    const mapped = mapSummaryItemsToForm()
    if (!mapped.length) return
    form.items = mapped
    filledFromLastWeek.value = true
}

// Manual re-apply from the summary panel. Confirms before overwriting items
// the user may have already entered.
const copyPreviousWeekItems = async () => {
    if (!mapSummaryItemsToForm().length) return

    if (!itemsArePristine()) {
        const result = await Swal.fire({
            title: t('common.confirm'),
            text: t('invoices.overwrite_items_confirm'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: t('common.yes'),
            cancelButtonText: t('common.cancel'),
        })
        if (!result.isConfirmed) return
    }

    applyPreviousWeekItems()
}

// Auto-fill the items table when last week's summary loads, but only while
// the form is still pristine so we never clobber what the user has typed.
watch(() => props.previousWeekSummary, (summary) => {
    if (isEditing.value) return
    if (summary && itemsArePristine()) {
        applyPreviousWeekItems()
    }
})

const addRow = () => {
    const newItem = { newspaper_id: '', price_id: '', quantity: 1, unit_price: 0 }
    if (isEditing.value) {
        newItem.return_quantity = 0
    }
    form.items.push(newItem)
}

const deleteItemFromDatabase = (itemId, index) => {
    return new Promise((resolve, reject) => {
        deletingItemId.value = itemId

        router.delete(`/admin/invoices/${props.invoice.id}/items/${itemId}`, {
            onSuccess: () => {
                // Remove from form items after successful deletion
                form.items.splice(index, 1)
                deletingItemId.value = null
            },
            onError: (errors) => {
                console.error('Failed to delete invoice item:', errors)
                deletingItemId.value = null
                const msg = Object.values(errors || {})[0] || 'Failed to delete item. Please try again.'
                Swal.fire(t('common.error') + '!', msg, 'error')
                reject(errors)
            },
            onFinish: () => {
                deletingItemId.value = null
                resolve()
            }
        })
    })
}

const removeRow = async (index) => {
    if (form.items.length <= 1) return

    // Check if this is an existing item (has id from database)
    const item = form.items[index]
    const originalItem = isEditing.value ? props.invoice.items[index] : null

    const message = (isEditing.value && originalItem && originalItem.id)
        ? (t('invoices.delete_item_confirm') || 'Are you sure you want to delete this item from the invoice? This will remove it from the database.')
        : (t('common.delete_confirm') || 'Remove this item from the form?')

    const { confirmDelete } = useDeleteConfirm(t('common.cannot_undo') + ' ' + message)

    await confirmDelete(async () => {
        if (isEditing.value && originalItem && originalItem.id) {
            await deleteItemFromDatabase(originalItem.id, index)
        } else {
            form.items.splice(index, 1)
        }
    })
}

const isPastDate = (dateStr) => {
    if (!dateStr) return false
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const date = new Date(dateStr)
    date.setHours(0, 0, 0, 0)
    return date < today
}

const submit = async () => {
    // Validate return quantities before submission
    if (isEditing.value) {
        let hasError = false
        form.items.forEach((item, index) => {
            const returnQty = parseInt(item.return_quantity) || 0
            const currentQty = parseInt(item.quantity) || 0

            if (returnQty > currentQty) {
                form.errors[`items.${index}.return_quantity`] = `Return quantity cannot exceed current quantity (${currentQty}).`
                hasError = true
            } else if (returnQty < 0) {
                form.errors[`items.${index}.return_quantity`] = 'Return quantity cannot be negative.'
                hasError = true
            }
        })

        if (hasError) {
            return
        }
    }

    // Editing a past date invoice requires a dedicated permission
    if (isEditing.value && isPastDate(form.invoice_date) && !canEditPastInvoice.value) {
        await Swal.fire({
            title: t('invoices.past_date_permission_denied_title') || 'Permission Required',
            text: t('invoices.past_date_permission_denied') || 'You do not have permission to edit invoices with a past date.',
            icon: 'error',
            confirmButtonColor: '#6b7280',
            confirmButtonText: t('common.ok') || 'OK',
        })

        return
    }

    // Confirm if editing an invoice with a past date
    if (isEditing.value && isPastDate(form.invoice_date)) {
        const result = await Swal.fire({
            title: t('invoices.past_date_confirm_title') || 'Past Date Invoice',
            text: t('invoices.past_date_confirm_message') || 'This invoice date is in the past. Are you sure you want to update this invoice?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6b7280',
            confirmButtonText: t('common.yes_update') || 'Yes, Update',
            cancelButtonText: t('common.cancel') || 'Cancel',
        })

        if (!result.isConfirmed) {
            return
        }
    }

    if (isEditing.value) {
        form.put(`/admin/invoices/${props.invoice.id}`)
    } else {
        form.post('/admin/invoices')
    }
}

const filteredNewspaperOptions = (currentIndex) => {
    const selectedIds = form.items
        .filter((_, idx) => idx !== currentIndex)
        .map(item => parseInt(item.newspaper_id))
        .filter(id => !isNaN(id))

    return newspaperOptions.value.filter(option => !selectedIds.includes(option.value))
}
</script>

<template>

    <Head :title="isEditing ? t('invoices.edit_invoice', { id: invoice.id }) : t('invoices.create_invoice')" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="isEditing ? `/admin/invoices/${invoice.id}` : '/admin/invoices'">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">{{ isEditing ? t('invoices.edit_invoice', { id: invoice.id }) : t('invoices.create_new') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ isEditing ? t('invoices.edit_description') : t('invoices.create_description') }}</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Invoice Details -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="mb-6 flex items-center gap-2 border-b pb-4">
                    <Store class="h-5 w-5 text-primary" />
                    <h3 class="font-bold">{{ t('invoices.invoice_details') }}</h3>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="invoice_date" class="block text-sm font-medium text-gray-700">{{ t('invoices.invoice_date') }}</Label>
                        <Datepicker id="invoice_date" v-model="form.invoice_date"
                            class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 cursor-pointer" />
                        <p v-if="form.errors.invoice_date" class="text-xs text-destructive">{{ form.errors.invoice_date
                        }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="shop_id" class="block text-sm font-medium text-gray-700">{{ t('invoices.shop') }}</Label>
                        <div v-if="isEditing" class="flex h-10 items-center rounded-lg border bg-muted/50 px-3 text-sm text-muted-foreground">
                            {{ invoice.shop?.name }}
                        </div>
                        <Select2
                            v-else
                            v-model="form.shop_id"
                            :options="shopOptions"
                            :placeholder="t('invoices.select_shop')"
                        />
                        <p v-if="form.errors.shop_id" class="text-xs text-destructive">{{ form.errors.shop_id }}</p>
                    </div>
                </div>

                <!-- Invoice Type -->
                <div class="border-t pt-4">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="'daily'" v-model="form.invoice_type" class="w-4 h-4 accent-primary" />
                            <span class="text-sm font-medium text-foreground">{{ t('invoices.type_daily') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="'monthly'" v-model="form.invoice_type" class="w-4 h-4 accent-primary" />
                            <span class="text-sm font-medium text-foreground">{{ t('invoices.type_monthly') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Previous Week Summary -->
                <div v-if="form.invoice_date && form.shop_id" class="mt-6 border-t pt-6">
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <div class="flex items-center gap-2">
                            <History class="h-4 w-4 text-muted-foreground" />
                            <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider">{{ t('invoices.last_week_comparison') }}</h4>
                        </div>
                        <Button v-if="props.previousWeekSummary && !isLoadingSummary" type="button" variant="outline"
                            size="sm" class="rounded-xl" @click="copyPreviousWeekItems">
                            <Copy class="mr-1 h-4 w-4" /> {{ t('invoices.copy_last_week_items') }}
                        </Button>
                    </div>

                    <div v-if="isLoadingSummary" class="flex items-center justify-center py-8">
                        <Loader2 class="h-6 w-6 animate-spin text-primary" />
                        <span class="ml-2 text-sm text-muted-foreground">{{ t('invoices.fetching_data') }}</span>
                    </div>

                    <div v-else-if="props.previousWeekSummary" class="bg-muted/30 rounded-xl p-4 border border-dashed">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="item in props.previousWeekSummary.items" :key="item.id" class="flex items-center justify-between p-2 bg-background rounded-lg border shadow-sm">
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-muted-foreground truncate max-w-[150px]">{{ item.newspaper.name }}</span>
                                    <span class="text-lg font-bold">{{ item.quantity }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-muted-foreground block uppercase">Distributed</span>
                                    <span class="text-xs font-semibold text-primary">Rs. {{ (item.quantity * item.unit_price).toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-between items-center px-2 pt-3 border-t">
                            <span class="text-sm text-muted-foreground">Total Last Week:</span>
                            <span class="font-bold text-lg">Rs. {{ parseFloat(props.previousWeekSummary.total_amount).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-6 bg-muted/20 rounded-xl border border-dashed">
                        <AlertCircle class="h-8 w-8 text-muted-foreground/40 mb-2" />
                        <p class="text-sm text-muted-foreground">No invoice found for the same day last week.</p>
                    </div>
                </div>
            </div>

            <!-- Newspaper Items -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between border-b pb-4">
                    <div class="flex items-center gap-2">
                        <Newspaper class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">{{ t('invoices.newspaper_items') }}</h3>
                        <span v-if="filledFromLastWeek"
                            class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">
                            <History class="h-3 w-3" /> {{ t('invoices.filled_from_last_week') }}
                        </span>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addRow" class="rounded-xl">
                        <Plus class="mr-1 h-4 w-4" /> {{ t('invoices.add_newspaper') }}
                    </Button>
                </div>

                <div v-if="form.errors.items" class="mb-4 text-xs text-destructive">{{ form.errors.items }}</div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-2 py-2 w-80">{{ t('invoices.newspaper') }}</th>
                                <th class="px-2 py-2 w-40">{{ t('invoices.price_variant') }}</th>
                                <th class="px-2 py-2 w-24">{{ t('invoices.quantity') }}</th>
                                <th v-if="isEditing" class="px-2 py-2 w-24">{{ t('invoices.return_quantity') }}</th>
                                <th class="px-2 py-2 w-28">{{ t('invoices.unit_price') }}</th>
                                <th class="px-2 py-2 w-28 text-right">{{ t('invoices.row_total') }}</th>
                                <th class="px-2 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index" class="border-t border-border/50">
                                <td class="px-2 py-2">
                                    <Select2
                                        :model-value="item.newspaper_id"
                                        :options="filteredNewspaperOptions(index)"
                                        :placeholder="t('invoices.select_newspaper')"
                                        @update:modelValue="value => handleNewspaperChange(index, value)"
                                    />
                                    <p v-if="form.errors[`items.${index}.newspaper_id`]"
                                        class="text-xs text-destructive">{{ form.errors[`items.${index}.newspaper_id`]
                                        }}</p>
                                </td>
                                <td class="px-2 py-2">
                                    <Select2
                                        :model-value="item.price_id"
                                        :options="priceOptions(index)"
                                        :placeholder="t('invoices.select_price')"
                                        @update:modelValue="value => handlePriceChange(index, value)"
                                        :disabled="!item.newspaper_id"
                                    />
                                    <p v-if="form.errors[`items.${index}.price_id`]"
                                        class="text-xs text-destructive">{{ form.errors[`items.${index}.price_id`]
                                        }}</p>
                                </td>
                                <td class="px-2 py-2">
                                    <Input v-model.number="item.quantity" type="number" min="1" step="1"
                                        class="h-9 text-center" />
                                    <p v-if="form.errors[`items.${index}.quantity`]" class="text-xs text-destructive">{{
                                        form.errors[`items.${index}.quantity`] }}</p>
                                </td>
                                <td v-if="isEditing" class="px-2 py-2">
                                    <Input
                                        v-model.number="item.return_quantity"
                                        type="number"
                                        min="0"
                                        :max="item.quantity"
                                        step="1"
                                        class="h-9 text-center"
                                        @input="handleReturnQuantityChange(index)"
                                    />
                                    <p v-if="form.errors[`items.${index}.return_quantity`]" class="text-xs text-destructive">{{
                                        form.errors[`items.${index}.return_quantity`] }}</p>
                                </td>
                                <td class="px-2 py-2">
                                    <div class="relative">
                                        <span
                                            class="absolute left-2 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">Rs.</span>
                                        <Input v-model="item.unit_price" type="number" min="0" step="0.01"
                                            class="h-9 pl-8" readonly />
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-right font-semibold">
                                    Rs. {{ rowTotal(index).toFixed(2) }}
                                </td>
                                <td class="px-2 py-2">
                                    <button type="button" @click="removeRow(index)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                        :disabled="form.items.length === 1 || deletingItemId === (isEditing && props.invoice.items[index]?.id)">
                                        <Loader2 v-if="deletingItemId === (isEditing && props.invoice.items[index]?.id)" class="h-4 w-4 animate-spin" />
                                        <Trash2 v-else class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end border-t border-border/50 pt-4">
                    <div class="text-right space-y-2 max-w-xs w-full">
                        <div v-if="isEditing" class="flex items-center justify-between gap-2">
                            <span class="text-xs text-green-600 font-medium">+ {{ t('invoices.previous_deficit') }}</span>
                            <Input
                                v-model.number="form.previous_deficit"
                                type="number"
                                min="0"
                                step="0.01"
                                class="h-8 w-28 text-right text-sm"
                            />
                        </div>
                        <div v-if="isEditing" class="flex items-center justify-between gap-2">
                            <span class="text-xs text-destructive font-medium">- {{ t('invoices.special_discount') }}</span>
                            <Input
                                v-model.number="form.special_discount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="h-8 w-28 text-right text-sm"
                            />
                        </div>
                        <div>
                            <span class="text-xs text-muted-foreground">{{ t('invoices.invoice_total') }}</span>
                            <div class="text-2xl font-bold text-primary">Rs. {{ totalAmount.toFixed(2) }}</div>
                        </div>
                        <div v-if="isEditing">
                            <span class="text-xs text-muted-foreground">{{ t('invoices.return_total') }}</span>
                            <div class="text-xl font-bold text-destructive">Rs. {{ totalReturnAmount.toFixed(2) }}</div>
                        </div>
                        <div v-if="isEditing" class="border-t pt-2">
                            <span class="text-xs text-muted-foreground">{{ t('invoices.net_amount') }}</span>
                            <div class="text-2xl font-bold">Rs. {{ netAmount.toFixed(2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2 border-b pb-4">
                    <StickyNote class="h-5 w-5 text-primary" />
                    <h3 class="font-bold">{{ t('invoices.notes') }}</h3>
                </div>
                <textarea v-model="form.notes" :placeholder="t('invoices.notes_placeholder')"
                    class="flex w-full rounded-lg border border-input bg-background px-4 py-3 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 min-h-[100px] resize-y" />
                <p v-if="form.errors.notes" class="mt-2 text-xs text-destructive">{{ form.errors.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <Link href="/admin/invoices">
                    <Button type="button" variant="outline" class="rounded-xl">{{ t('common.cancel') }}</Button>
                </Link>
                <Button type="submit" class="rounded-xl shadow-lg shadow-primary/20" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    {{ isEditing ? t('invoices.update_invoice') : t('invoices.create_invoice') }}
                </Button>
            </div>
        </form>

    </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ChevronLeft, Save, Plus, Trash2, Store, Newspaper } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Datepicker } from '@/Components/ui/datepicker'
import { Label } from '@/Components/ui/label'
import { computed, ref, watch } from 'vue'
import { History, AlertCircle, Loader2 } from 'lucide-vue-next'

const props = defineProps({
    invoice: Object,
    shops: Array,
    newspapers: Array,
    previousWeekSummary: Object,
})

const tomorrow = new Date()
tomorrow.setDate(tomorrow.getDate() + 1)
const defaultDate = tomorrow.toISOString().split('T')[0]

const minDate = defaultDate

const form = useForm({
    invoice_date: defaultDate,
    shop_id: '',
    items: [
        { newspaper_id: '', quantity: 1, unit_price: 0 },
    ],
})

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)
    }, 0)
})

const newspaperOptions = computed(() => {
    return props.newspapers.map(np => ({
        value: np.id,
        label: `${np.name} (Rs. ${parseFloat(np.price).toFixed(2)})`,
        price: parseFloat(np.price),
    }))
})

const handleNewspaperChange = (index) => {
    const selectedId = parseInt(form.items[index].newspaper_id)
    
    // Check if this newspaper is already selected in another row
    const isDuplicate = form.items.some((item, idx) => {
        return idx !== index && parseInt(item.newspaper_id) === selectedId
    })

    if (isDuplicate) {
        form.items[index].newspaper_id = ''
        form.items[index].unit_price = 0
        form.errors[`items.${index}.newspaper_id`] = 'This newspaper is already added.'
        return
    }

    // Clear error if not duplicate
    if (form.errors[`items.${index}.newspaper_id`]) {
        delete form.errors[`items.${index}.newspaper_id`]
    }

    const selected = props.newspapers.find(n => n.id === selectedId)
    if (selected) {
        form.items[index].unit_price = parseFloat(selected.price)
    } else {
        form.items[index].unit_price = 0
    }
}

const rowTotal = (index) => {
    const qty = parseFloat(form.items[index].quantity) || 0
    const price = parseFloat(form.items[index].unit_price) || 0
    return qty * price
}

const isLoadingSummary = ref(false)

const fetchPreviousWeekSummary = () => {
    if (!form.invoice_date || !form.shop_id) {
        return
    }

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
    fetchPreviousWeekSummary()
})

const addRow = () => {
    form.items.push({ newspaper_id: '', quantity: 1, unit_price: 0 })
}

const removeRow = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1)
    }
}

const submit = () => {
    form.post('/admin/invoices')
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

    <Head title="Create Invoice" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/admin/invoices">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Create New Invoice</h2>
                    <p class="text-sm text-muted-foreground">Select date and shop, then add newspaper items.</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Invoice Details -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="mb-6 flex items-center gap-2 border-b pb-4">
                    <Store class="h-5 w-5 text-primary" />
                    <h3 class="font-bold">Invoice Details</h3>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="invoice_date" class="block text-sm font-medium text-gray-700">Invoice Date</Label>
                        <Datepicker id="invoice_date" v-model="form.invoice_date" :min="minDate"
                            class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 cursor-pointer" />
                        <p v-if="form.errors.invoice_date" class="text-xs text-destructive">{{ form.errors.invoice_date
                        }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="shop_id" class="block text-sm font-medium text-gray-700">Shop</Label>
                        <select id="shop_id" v-model="form.shop_id"
                            class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            <option value="" disabled>Select a shop</option>
                            <option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.name }}</option>
                        </select>
                        <p v-if="form.errors.shop_id" class="text-xs text-destructive">{{ form.errors.shop_id }}</p>
                    </div>
                </div>

                <!-- Previous Week Summary -->
                <div v-if="form.invoice_date && form.shop_id" class="mt-6 border-t pt-6">
                    <div class="flex items-center gap-2 mb-4">
                        <History class="h-4 w-4 text-muted-foreground" />
                        <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Last Week Comparison (Same Day)</h4>
                    </div>

                    <div v-if="isLoadingSummary" class="flex items-center justify-center py-8">
                        <Loader2 class="h-6 w-6 animate-spin text-primary" />
                        <span class="ml-2 text-sm text-muted-foreground">Fetching historical data...</span>
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
                        <h3 class="font-bold">Newspaper Items</h3>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addRow" class="rounded-xl">
                        <Plus class="mr-1 h-4 w-4" /> Add Newspaper
                    </Button>
                </div>

                <div v-if="form.errors.items" class="mb-4 text-xs text-destructive">{{ form.errors.items }}</div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-2 py-2">Newspaper</th>
                                <th class="px-2 py-2 w-24">Quantity</th>
                                <th class="px-2 py-2 w-28">Unit Price</th>
                                <th class="px-2 py-2 w-28 text-right">Row Total</th>
                                <th class="px-2 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index" class="border-t border-border/50">
                                <td class="px-2 py-2">
                                    <select v-model="item.newspaper_id" @change="handleNewspaperChange(index)"
                                        class="flex h-9 w-full rounded-lg border border-input bg-background px-3 py-1 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                        <option value="" disabled>Select newspaper</option>
                                        <option v-for="np in filteredNewspaperOptions(index)" :key="np.value" :value="np.value">{{
                                            np.label }}</option>
                                    </select>
                                    <p v-if="form.errors[`items.${index}.newspaper_id`]"
                                        class="text-xs text-destructive">{{ form.errors[`items.${index}.newspaper_id`]
                                        }}</p>
                                </td>
                                <td class="px-2 py-2">
                                    <Input v-model.number="item.quantity" type="number" min="1" step="1"
                                        class="h-9 text-center" />
                                    <p v-if="form.errors[`items.${index}.quantity`]" class="text-xs text-destructive">{{
                                        form.errors[`items.${index}.quantity`] }}</p>
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
                                        :disabled="form.items.length === 1">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end border-t border-border/50 pt-4">
                    <div class="text-right">
                        <span class="text-xs text-muted-foreground">Invoice Total</span>
                        <div class="text-2xl font-bold text-primary">Rs. {{ totalAmount.toFixed(2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <Link href="/admin/invoices">
                    <Button type="button" variant="outline" class="rounded-xl">Cancel</Button>
                </Link>
                <Button type="submit" class="rounded-xl shadow-lg shadow-primary/20" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    Create Invoice
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>

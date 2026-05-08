<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronLeft, Save, Plus, Trash2, Store, Newspaper, DollarSign } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Datepicker } from '@/Components/ui/datepicker'
import { Label } from '@/Components/ui/label'
import { computed } from 'vue'

const props = defineProps({
    invoice: Object,
    shops: Array,
    newspapers: Array,
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
        label: `${np.name} ($${parseFloat(np.price).toFixed(2)})`,
        price: parseFloat(np.price),
    }))
})

const handleNewspaperChange = (index) => {
    const selected = props.newspapers.find(n => n.id === parseInt(form.items[index].newspaper_id))
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
            </div>

            <!-- Newspaper Items -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between border-b pb-4">
                    <div class="flex items-center gap-2">
                        <Newspaper class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">Newspaper Items</h3>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addRow" class="rounded-xl">
                        <Plus class="mr-1 h-4 w-4" /> Add News paper
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
                                        <option v-for="np in newspaperOptions" :key="np.value" :value="np.value">{{
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
                                        <DollarSign
                                            class="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input v-model="item.unit_price" type="number" min="0" step="0.01"
                                            class="h-9 pl-7" readonly />
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-right font-semibold">
                                    ${{ rowTotal(index).toFixed(2) }}
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
                        <div class="text-2xl font-bold text-primary">${{ totalAmount.toFixed(2) }}</div>
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

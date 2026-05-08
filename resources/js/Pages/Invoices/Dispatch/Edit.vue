<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { Pencil, Save, Send, ChevronLeft, Truck } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { computed } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
    invoice: Object,
})

const form = useForm({
    items: props.invoice.items.map(item => ({
        id: item.id,
        quantity: item.quantity,
        reason: item.manual_adjustment_reason || '',
    })),
})

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        const invoiceItem = props.invoice.items.find(i => i.id === item.id)
        return sum + (item.quantity * (invoiceItem?.unit_price || 0))
    }, 0)
})

const updateQuantity = (index, value) => {
    form.items[index].quantity = parseInt(value) || 0
}

const submit = () => {
    if (!confirmChange()) return
    form.put(`/dispatch/${props.invoice.id}`, {
        preserveState: true,
        onSuccess: () => {
            Swal.fire('Success', 'Invoice updated successfully', 'success')
        },
        onError: (errors) => {
            Swal.fire('Error', Object.values(errors)[0] || 'Failed to update invoice', 'error')
        },
    })
}

const confirmInvoice = () => {
    Swal.fire({
        title: 'Confirm Invoice?',
        text: 'Once confirmed, this invoice cannot be edited.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, confirm it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/dispatch/${props.invoice.id}/confirm`, {
                preserveState: true,
                onSuccess: () => {
                    Swal.fire('Success', 'Invoice confirmed successfully', 'success')
                    router.visit(`/invoices/${props.invoice.id}`)
                },
                onError: (errors) => {
                    Swal.fire('Error', Object.values(errors)[0] || 'Failed to confirm invoice', 'error')
                },
            })
        }
    })
}

const confirmChange = () => {
    const changed = form.items.some((item, i) => {
        return item.quantity !== props.invoice.items[i]?.quantity
    })
    if (!changed) return true
    return new Promise((resolve) => {
        Swal.fire({
            title: 'Unsaved Changes',
            text: 'You have unsaved changes. Do you want to save them?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Save',
            cancelButtonText: 'Discard',
        }).then((result) => {
            resolve(result.isConfirmed)
        })
    })
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'draft': 'bg-yellow-100 text-yellow-800',
        'confirmed': 'bg-blue-100 text-blue-800',
        'sent': 'bg-purple-100 text-purple-800',
        'delivered': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <Head title="Edit Invoice" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/dispatch/create">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Edit Invoice</h2>
                    <p class="text-sm text-muted-foreground">Modify quantities before confirming dispatch</p>
                </div>
            </div>
            <span class="text-xs px-3 py-1 rounded-full font-medium" :class="getStatusBadgeClass(invoice.status)">
                {{ invoice.status }}
            </span>
        </div>

        <!-- Invoice Info -->
        <div class="rounded-2xl border bg-card p-6 shadow-sm mb-6">
            <div class="mb-6 flex items-center gap-2 border-b pb-4">
                <Truck class="h-5 w-5 text-primary" />
                <h3 class="font-bold">Invoice Details</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <div class="text-xs text-muted-foreground uppercase font-bold tracking-wider">Invoice No</div>
                    <div class="font-semibold mt-1">{{ invoice.invoice_no }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground uppercase font-bold tracking-wider">Shop</div>
                    <div class="font-semibold mt-1">{{ invoice.shop?.name }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground uppercase font-bold tracking-wider">Dispatch Date</div>
                    <div class="font-semibold mt-1">{{ invoice.dispatch_date }}</div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex items-center gap-2">
                    <Pencil class="h-5 w-5 text-primary" />
                    <h3 class="font-bold">Invoice Items</h3>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Newspaper</th>
                            <th class="px-6 py-4 text-right">Unit Price</th>
                            <th class="px-6 py-4 text-right">Quantity</th>
                            <th class="px-6 py-4 text-right">Line Total</th>
                            <th class="px-6 py-4 text-left">Adjustment Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="(item, index) in form.items" :key="item.id" class="hover:bg-secondary/20">
                            <td class="px-6 py-4 font-medium">{{ invoice.items[index]?.newspaper_name }}</td>
                            <td class="px-6 py-4 text-right">${{ (invoice.items[index]?.unit_price || 0).toFixed(2) }}</td>
                            <td class="px-6 py-4 text-right">
                                <input type="number"
                                    :value="item.quantity"
                                    @input="updateQuantity(index, $event.target.value)"
                                    min="0"
                                    class="w-20 h-9 rounded-lg border bg-background px-3 text-sm text-right focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
                                <p v-if="form.errors['items.' + index + '.quantity']" class="text-xs text-destructive mt-1">
                                    {{ form.errors['items.' + index + '.quantity'] }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                ${{ (item.quantity * (invoice.items[index]?.unit_price || 0)).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4">
                                <input type="text"
                                    v-model="item.reason"
                                    placeholder="Reason for change"
                                    class="w-full h-9 rounded-lg border bg-background px-3 text-xs focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
                            </td>
                        </tr>
                        <tr v-if="form.items.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-muted-foreground italic">
                                No items found.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-secondary/20">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right font-bold">Total:</td>
                            <td class="px-6 py-4 text-right font-bold">${{ totalAmount.toFixed(2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end gap-3">
            <Link href="/dispatch/create">
                <Button variant="outline" class="rounded-xl">Cancel</Button>
            </Link>
            <Button v-if="invoice.status === 'draft'" @click="submit" class="rounded-xl px-6" :disabled="form.processing">
                <Save class="mr-2 h-4 w-4" />
                Save Changes
            </Button>
            <Button v-if="invoice.status === 'draft'" @click="confirmInvoice" variant="secondary" class="rounded-xl px-6">
                <Send class="mr-2 h-4 w-4" />
                Confirm Invoice
            </Button>
        </div>
    </AdminLayout>
</template>

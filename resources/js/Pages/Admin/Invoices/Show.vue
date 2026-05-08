<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ChevronLeft, Printer, FileText, Store, CalendarDays, User, Hash } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { computed } from 'vue'

const props = defineProps({
    invoice: Object,
})

const totalQty = computed(() => {
    return props.invoice.items.reduce((sum, item) => sum + item.quantity, 0)
})

const printInvoice = () => {
    window.print()
}

const statusVariant = (status) => {
    const map = {
        draft: 'secondary',
        paid: 'success',
        cancelled: 'destructive',
    }
    return map[status] || 'secondary'
}
</script>

<template>
    <Head :title="`Invoice #${invoice.id}`" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between no-print">
            <div class="flex items-center gap-4">
                <Link href="/admin/invoices">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Invoice #{{ invoice.id }}</h2>
                    <p class="text-sm text-muted-foreground">View and print invoice details.</p>
                </div>
            </div>
            <Button @click="printInvoice" class="rounded-xl shadow-lg shadow-primary/20">
                <Printer class="mr-2 h-4 w-4" />
                Print Invoice
            </Button>
        </div>

        <!-- Invoice Content -->
        <div class="max-w-4xl mx-auto bg-card rounded-2xl border shadow-sm p-8 print:p-0 print:border-0 print:shadow-none">
            <!-- Header -->
            <div class="flex items-start justify-between mb-8 print:mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-primary">INVOICE</h1>
                    <p class="text-sm text-muted-foreground mt-1">#{{ invoice.id }}</p>
                </div>
                <div class="text-right">
                    <Badge :variant="statusVariant(invoice.status)" class="rounded-full px-3 py-1 text-xs capitalize print:border-0">
                        {{ invoice.status }}
                    </Badge>
                </div>
            </div>

            <!-- Details -->
            <div class="grid grid-cols-2 gap-8 mb-8 print:mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-2 text-muted-foreground">
                        <Store class="h-4 w-4" />
                        <span class="text-xs font-semibold uppercase tracking-wider">Shop</span>
                    </div>
                    <p class="font-semibold text-lg">{{ invoice.shop?.name }}</p>
                </div>
                <div class="text-right">
                    <div class="flex items-center justify-end gap-2 mb-2 text-muted-foreground">
                        <CalendarDays class="h-4 w-4" />
                        <span class="text-xs font-semibold uppercase tracking-wider">Date</span>
                    </div>
                    <p class="font-semibold">{{ invoice.invoice_date }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto mb-8 print:mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y-2 border-primary/20">
                            <th class="py-3 text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">#</th>
                            <th class="py-3 text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">Newspaper</th>
                            <th class="py-3 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground">Qty</th>
                            <th class="py-3 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">Unit Price</th>
                            <th class="py-3 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in invoice.items" :key="item.id" class="border-b border-border/50">
                            <td class="py-3 text-muted-foreground">{{ index + 1 }}</td>
                            <td class="py-3 font-medium">{{ item.newspaper?.name }}</td>
                            <td class="py-3 text-center">{{ item.quantity }}</td>
                            <td class="py-3 text-right">Rs. {{ parseFloat(item.unit_price).toFixed(2) }}</td>
                            <td class="py-3 text-right font-semibold">Rs. {{ parseFloat(item.total_price).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="flex justify-end border-t-2 border-primary/20 pt-4 print:pt-4">
                <div class="w-64 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Total Items</span>
                        <span>{{ invoice.items.length }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Total Quantity</span>
                        <span>{{ totalQty }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>Total Amount</span>
                        <span class="text-primary">Rs. {{ parseFloat(invoice.total_amount).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-12 pt-6 border-t text-center text-xs text-muted-foreground print:mt-8">
                <p>Generated on {{ new Date().toLocaleDateString() }} by {{ invoice.creator?.name || 'System' }}</p>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white !important;
    }
}
</style>

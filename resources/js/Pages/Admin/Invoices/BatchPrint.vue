<script setup>
import { Head } from '@inertiajs/vue3'
import { Printer, Store, CalendarDays } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { useTranslation } from '@/Composables/useTranslation'
import { onMounted } from 'vue'

const { t } = useTranslation()

defineProps({
    invoices: Array,
})

const invoiceDateLabel = (invoice) => {
    const invoiceDate = invoice?.invoice_date
    if (!invoiceDate) {
        return ''
    }

    const parsedDate = new Date(`${invoiceDate}T00:00:00`)
    if (Number.isNaN(parsedDate.getTime())) {
        return invoiceDate
    }

    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(parsedDate)
}

const statusVariant = (status) => {
    const map = {
        draft: 'secondary',
        paid: 'success',
        cancelled: 'destructive',
    }
    return map[status] || 'secondary'
}

const hasReturns = (invoice) => {
    return invoice.items.some((item) => item.return_quantity && item.return_quantity > 0)
}

const totalQty = (invoice) => {
    return invoice.items.reduce((sum, item) => sum + item.quantity, 0)
}

const totalAmount = (invoice) => {
    return invoice.items.reduce((sum, item) => sum + parseFloat(item.total_price || 0), 0)
}

const totalReturnAmount = (invoice) => {
    return invoice.items.reduce((sum, item) => sum + parseFloat(item.return_total_price || 0), 0)
}

const printPage = () => {
    window.print()
}

onMounted(() => {
    if (!new URLSearchParams(window.location.search).has('print')) {
        return
    }

    setTimeout(() => {
        window.print()
        window.history.replaceState({}, '', window.location.pathname + window.location.search.replace('&print=1', '').replace('print=1&', '').replace('?print=1', ''))
    }, 300)
})
</script>

<template>
    <Head title="Print Invoices" />

    <div class="min-h-screen bg-secondary/20 py-6 print:bg-white print:py-0">
        <div class="no-print mx-auto mb-6 flex max-w-4xl items-center justify-between px-4">
            <div>
                <h1 class="text-xl font-bold">Print Invoices</h1>
                <p class="text-sm text-muted-foreground">{{ invoices.length }} selected</p>
            </div>
            <Button @click="printPage" class="rounded-xl">
                <Printer class="mr-2 h-4 w-4" />
                Print
            </Button>
        </div>

        <div
            v-for="invoice in invoices"
            :key="invoice.id"
            class="print-page mx-auto mb-6 max-w-4xl bg-card rounded-2xl border shadow-sm p-7 print:mb-0 print:p-0 print:border-0 print:shadow-none"
        >
            <div class="relative mb-2 text-center print:mb-4">
                <div>
                    <h1 class="text-base font-bold uppercase tracking-normal text-foreground">{{ t('invoices.invoice') }}</h1>
                    <p class="mt-1 text-xs font-medium text-muted-foreground">#{{ invoice.id }}</p>
                </div>
                <div class="absolute right-0 top-0">
                    <Badge :variant="statusVariant(invoice.status)" class="rounded-full px-3 py-1 text-xs capitalize print:border-0">
                        {{ invoice.status }}
                    </Badge>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5 mb-2 rounded-lg border bg-muted/20 p-4 print:mb-4 print:p-3">
                <div>
                    <div class="flex items-center gap-2 mb-1.5 text-muted-foreground">
                        <Store class="h-4 w-4" />
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ t('invoices.shop') }}</span>
                    </div>
                    <p class="inline-block max-w-full rounded-md uppercase px-2.5 py-1 text-xl font-bold leading-tight text-primary break-words print:text-2xl">
                        {{ invoice.shop?.name }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="flex items-center justify-end gap-2 mb-1.5 text-muted-foreground">
                        <CalendarDays class="h-4 w-4" />
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ t('common.date') }}</span>
                    </div>
                    <p class="font-semibold text-xl">{{ invoiceDateLabel(invoice) }}</p>
                </div>
            </div>

            <div class="overflow-x-auto mb-6 print:mb-4">
                <table class="w-full text-[16px]">
                    <thead>
                        <tr class="border-y-2 border-primary/20 bg-muted/30">
                            <th class="py-2 pl-2 text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">#</th>
                            <th class="py-2 text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">Newspaper</th>
                            <th class="py-2 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ t('invoices.qty') }}</th>
                            <th v-if="hasReturns(invoice)" class="py-2 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ t('invoices.return') }}</th>
                            <th class="py-2 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ t('invoices.unit_price') }}</th>
                            <th class="py-2 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ t('invoices.total') }}</th>
                            <th v-if="hasReturns(invoice)" class="py-2 pr-2 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ t('invoices.return_amt') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in invoice.items" :key="item.id" class="border-b border-border/50">
                            <td class="py-1.5 pl-2 text-muted-foreground">{{ index + 1 }}</td>
                            <td class="py-1.5 font-medium">{{ item.newspaper?.name }}</td>
                            <td class="py-1.5 text-center">{{ item.quantity }}</td>
                            <td v-if="hasReturns(invoice)" class="py-1.5 text-center">{{ item.return_quantity || 0 }}</td>
                            <td class="py-1.5 text-right">Rs. {{ parseFloat(item.unit_price).toFixed(2) }}</td>
                            <td class="py-1.5 text-right font-semibold">Rs. {{ parseFloat(item.total_price).toFixed(2) }}</td>
                            <td v-if="hasReturns(invoice)" class="py-1.5 pr-2 text-right font-semibold text-destructive">Rs. {{ parseFloat(item.return_total_price || 0).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t-2 border-primary/20 pt-3 print:pt-3">
                <div class="w-64 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('invoices.total_items') }}</span>
                        <span>{{ invoice.items.length }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('invoices.total_quantity') }}</span>
                        <span>{{ totalQty(invoice) }}</span>
                    </div>
                    <div v-if="hasReturns(invoice)" class="flex justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('invoices.return_total') }}</span>
                        <span class="text-destructive">Rs. {{ totalReturnAmount(invoice).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">+ {{ t('invoices.previous_deficit') }}</span>
                        <span>Rs. {{ parseFloat(invoice.previous_deficit || 0).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">- {{ t('invoices.special_discount') }}</span>
                        <span class="text-destructive">Rs. {{ parseFloat(invoice.special_discount || 0).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold border-t pt-2">
                        <span>{{ t('invoices.net_amount') }}</span>
                        <span>Rs. {{ parseFloat(invoice.total_net_amount || invoice.total_amount).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold border-t pt-3">
                        <span class="text-primary">{{ t('invoices.total_amount') }}</span>
                        <span class="text-primary">Rs. {{ totalAmount(invoice).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <div v-if="invoice.notes" class="mt-6 border-t pt-4 print:mt-4">
                <div class="flex items-center gap-2 mb-2 text-muted-foreground">
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ t('invoices.notes') }}</span>
                </div>
                <p class="text-sm whitespace-pre-wrap text-muted-foreground bg-muted/30 rounded-lg p-3">{{ invoice.notes }}</p>
            </div>

            <div class="mt-8 border-t pt-4 text-center text-[6px] text-muted-foreground print:mt-5 print:pt-3">
                <p>Generated on {{ new Date().toLocaleDateString() }} by {{ invoice.creator?.name || 'System' }}</p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-[6px] print:mt-2">
                    <span class="font-semibold text-foreground">Developed by Reshan Wijerathna</span>
                    <span class="text-border">|</span>
                    <span>+94711380025</span>
                    <span class="text-border">|</span>
                    <span>reshanmadushanka@gmail.com</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        size: A5;
        margin: 5mm;
    }

    .no-print {
        display: none !important;
    }

    .print-page {
        width: 100%;
        min-height: calc(210mm - 10mm);
        padding: 7mm !important;
        margin: 0 !important;
        break-after: page;
        page-break-after: always;
    }

    .print-page:last-child {
        break-after: auto;
        page-break-after: auto;
    }
}
</style>

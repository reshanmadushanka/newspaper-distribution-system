<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ChevronLeft, Store, CalendarDays, Download, MessageCircle, Mail, Printer, Pencil, Trash2 } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { computed } from 'vue'
import print from 'vue3-print-nb'
import Swal from 'sweetalert2'

const vPrint = print

const props = defineProps({
    invoice: Object,
})

const totalQty = computed(() => {
    return props.invoice.items.reduce((sum, item) => sum + item.quantity, 0)
})

const hasWhatsApp = computed(() => !!props.invoice.shop?.whatsapp_phone)
const hasEmail = computed(() => !!props.invoice.shop?.email)
const isDraft = computed(() => props.invoice.status === 'draft')

const pdfUrl = computed(() => `/admin/invoices/${props.invoice.id}/pdf`)
const invoiceUrl = computed(() => window.location.origin + `/admin/invoices/${props.invoice.id}`)

const downloadPdf = () => {
    window.open(pdfUrl.value, '_blank')
}

const sendWhatsApp = () => {
    const phone = props.invoice.shop.whatsapp_phone.replace(/[^0-9]/g, '')
    const text = encodeURIComponent(
        `Invoice #${props.invoice.id}\nShop: ${props.invoice.shop?.name}\nDate: ${props.invoice.invoice_date}\nTotal: Rs. ${parseFloat(props.invoice.total_amount).toFixed(2)}\n\nView invoice: ${invoiceUrl.value}`
    )
    window.open(`https://wa.me/${phone}?text=${text}`, '_blank')
}

const sendEmail = () => {
    const subject = encodeURIComponent(`Invoice #${props.invoice.id} - ${props.invoice.shop?.name}`)
    const body = encodeURIComponent(
        `Dear ${props.invoice.shop?.name},\n\nPlease find the invoice details below:\n\nInvoice #: ${props.invoice.id}\nDate: ${props.invoice.invoice_date}\nTotal Amount: Rs. ${parseFloat(props.invoice.total_amount).toFixed(2)}\n\nYou can view and download the PDF invoice here:\n${invoiceUrl.value}\n\nThank you.`
    )
    window.location.href = `mailto:${props.invoice.shop.email}?subject=${subject}&body=${body}`
}

const handleDelete = () => {
    Swal.fire({
        title: 'Delete Invoice?',
        text: 'This will permanently delete the invoice and all its items.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--color-destructive)',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/invoices/${props.invoice.id}`, {
                onError: (errors) => Swal.fire('Error!', Object.values(errors)[0], 'error'),
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
            <div class="flex items-center gap-2">
                <Button v-if="hasEmail" @click="sendEmail" variant="outline" class="rounded-xl">
                    <Mail class="mr-2 h-4 w-4" />
                    Email
                </Button>
                <Button v-if="hasWhatsApp" @click="sendWhatsApp" variant="outline" class="rounded-xl">
                    <MessageCircle class="mr-2 h-4 w-4" />
                    WhatsApp
                </Button>
                <Button @click="downloadPdf" variant="outline" class="rounded-xl">
                    <Download class="mr-2 h-4 w-4" />
                    PDF
                </Button>
                <Button v-print="'#printableInvoice'" class="rounded-xl shadow-lg shadow-primary/20">
                    <Printer class="mr-2 h-4 w-4" />
                    Print
                </Button>
                <div class="mx-2 h-6 w-px bg-border"></div>
                <Link v-if="isDraft" :href="`/admin/invoices/${invoice.id}/edit`">
                    <Button variant="outline" class="rounded-xl">
                        <Pencil class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>
                <Button @click="handleDelete" variant="outline" class="rounded-xl text-destructive border-destructive/30 hover:bg-destructive/10">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>

        <!-- Invoice Content -->
        <div id="printableInvoice" class="max-w-4xl mx-auto bg-card rounded-2xl border shadow-sm p-7 print:p-0 print:border-0 print:shadow-none">
            <!-- Header -->
            <div class="relative mb-2 text-center print:mb-4">
                <div>
                    <h1 class="text-xl font-bold uppercase tracking-normal text-foreground print:text-lg">Invoice</h1>
                    <p class="mt-1 text-xs font-medium text-muted-foreground">#{{ invoice.id }}</p>
                </div>
                <div class="absolute right-0 top-0">
                    <Badge :variant="statusVariant(invoice.status)" class="rounded-full px-3 py-1 text-xs capitalize print:border-0">
                        {{ invoice.status }}
                    </Badge>
                </div>
            </div>

            <!-- Details -->
            <div class="grid grid-cols-2 gap-5 rounded-lg border bg-muted/20 p-4 print:mb-4 print:p-3">
                <div>
                    <div class="flex items-center gap-2 mb-1.5 text-muted-foreground">
                        <Store class="h-4 w-4" />
                        <span class="text-xs font-semibold uppercase tracking-wider">Shop</span>
                    </div>
                    <p class="inline-flex rounded-md bg-primary/10 px-2.5 py-1 text-lg font-bold text-primary print:text-base">
                        {{ invoice.shop?.name }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="flex items-center justify-end gap-2 mb-1.5 text-muted-foreground">
                        <CalendarDays class="h-4 w-4" />
                        <span class="text-xs font-semibold uppercase tracking-wider">Date</span>
                    </div>
                    <p class="font-semibold">{{ invoice.invoice_date }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto mb-6 print:mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y-2 border-primary/20 bg-muted/30">
                            <th class="py-2 pl-2 text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">#</th>
                            <th class="py-2 text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">Newspaper</th>
                            <th class="py-2 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground">Qty</th>
                            <th class="py-2 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">Unit Price</th>
                            <th class="py-2 pr-2 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in invoice.items" :key="item.id" class="border-b border-border/50">
                            <td class="py-1.5 pl-2 text-muted-foreground">{{ index + 1 }}</td>
                            <td class="py-1.5 font-medium">{{ item.newspaper?.name }}</td>
                            <td class="py-1.5 text-center">{{ item.quantity }}</td>
                            <td class="py-1.5 text-right">Rs. {{ parseFloat(item.unit_price).toFixed(2) }}</td>
                            <td class="py-1.5 pr-2 text-right font-semibold">Rs. {{ parseFloat(item.total_price).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="flex justify-end border-t-2 border-primary/20 pt-3 print:pt-3">
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

            <!-- Notes -->
            <div v-if="invoice.notes" class="mt-6 border-t pt-4 print:mt-4">
                <div class="flex items-center gap-2 mb-2 text-muted-foreground">
                    <span class="text-xs font-semibold uppercase tracking-wider">Notes</span>
                </div>
                <p class="text-sm whitespace-pre-wrap text-muted-foreground bg-muted/30 rounded-lg p-3">{{ invoice.notes }}</p>
            </div>

            <!-- Footer -->
            <div class="mt-8 border-t pt-4 text-center text-xs text-muted-foreground print:mt-5 print:pt-3">
                <p>Generated on {{ new Date().toLocaleDateString() }} by {{ invoice.creator?.name || 'System' }}</p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-[8px] print:mt-2">
                    <span class="font-semibold text-foreground">Developed by Reshan Wijerathna</span>
                    <span class="text-border">|</span>
                    <span>+94711380025</span>
                    <span class="text-border">|</span>
                    <span>reshanmadushanka@gmail.com</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@media print {
    @page {
        size: A5;
        margin: 5mm;
    }
    body {
        -webkit-print-color-adjust: exact;
        background: white !important;
    }
    #printableInvoice {
        width: 100%;
        min-height: calc(210mm - 10mm);
        padding: 7mm !important;
        margin: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        font-size: 11px;
    }
    #printableInvoice table {
        font-size: 11px;
    }
    .no-print {
        display: none !important;
    }
}
</style>

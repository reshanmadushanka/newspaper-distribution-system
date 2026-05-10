<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { FileText, Store, CalendarDays, Download, TrendingUp, Receipt, Package, Hash, ArrowUpRight, ShoppingCart, DollarSign, BadgePercent, Banknote } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { computed, ref } from 'vue'

const props = defineProps({
    report: Object,
})

const dateInput = ref(props.report.date)

const summary = computed(() => props.report.summary)
const byShop = computed(() => props.report.by_shop)
const invoices = computed(() => props.report.invoices)

const fetchReport = () => {
    router.get('/admin/reports/daily-sales', { date: dateInput.value }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const downloadPdf = () => {
    window.open(`/admin/reports/daily-sales/pdf?date=${dateInput.value}`, '_blank')
}

const statusVariant = (status) => {
    const map = { draft: 'secondary', paid: 'success', cancelled: 'destructive' }
    return map[status] || 'secondary'
}

const formatCurrency = (val) => `Rs. ${parseFloat(val || 0).toFixed(2)}`

const cardClass = 'rounded-2xl border bg-card p-5 shadow-sm'

const profitColorClass = (margin) => {
    if (margin >= 30) return 'text-emerald-600'
    if (margin >= 15) return 'text-amber-600'
    return 'text-destructive'
}
</script>

<template>
    <Head title="Daily Sales Report" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <TrendingUp class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Daily Sales Report</h2>
                </div>
                <p class="text-muted-foreground">Financial summary with revenue, costs, and profit margins.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 rounded-xl border bg-card px-4 py-2 shadow-sm">
                    <CalendarDays class="h-4 w-4 text-muted-foreground" />
                    <input type="date" v-model="dateInput"
                        class="border-0 bg-transparent text-sm font-medium outline-none" />
                </div>
                <Button @click="fetchReport" class="rounded-xl shadow-lg shadow-primary/20">
                    <FileText class="mr-2 h-4 w-4" />
                    Load Report
                </Button>
                <Button @click="downloadPdf" variant="outline" class="rounded-xl">
                    <Download class="mr-2 h-4 w-4" />
                    PDF
                </Button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Invoices</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Receipt class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold">{{ summary.total_invoices }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ summary.paid_count }} paid / {{ summary.draft_count }} draft</p>
            </div>
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Revenue</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                        <TrendingUp class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold">{{ formatCurrency(summary.total_revenue) }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ formatCurrency(summary.paid_revenue) }} paid</p>
            </div>
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Cost</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600">
                        <Banknote class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold">{{ formatCurrency(summary.total_cost) }}</p>
                <p class="text-xs text-muted-foreground mt-1">cost of goods sold</p>
            </div>
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Net Profit</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl" :class="summary.total_profit >= 0 ? 'bg-blue-500/10 text-blue-600' : 'bg-destructive/10 text-destructive'">
                        <DollarSign class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold" :class="summary.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(summary.total_profit) }}</p>
                <p class="text-xs mt-1 flex items-center gap-1" :class="profitColorClass(summary.profit_margin)">
                    <BadgePercent class="h-3 w-3" />
                    <span>{{ summary.profit_margin }}% margin</span>
                </p>
            </div>
        </div>

        <!-- Second Row: Items & Quantity -->
        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Items Distributed</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600">
                        <Package class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold">{{ summary.total_items }}</p>
            </div>
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Quantity</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                        <Hash class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold">{{ summary.total_quantity }}</p>
            </div>
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Avg. Revenue / Invoice</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-600">
                        <ShoppingCart class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold">{{ formatCurrency(summary.total_invoices > 0 ? summary.total_revenue / summary.total_invoices : 0) }}</p>
            </div>
            <div :class="cardClass">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Avg. Profit / Invoice</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl" :class="summary.total_profit >= 0 ? 'bg-indigo-500/10 text-indigo-600' : 'bg-destructive/10 text-destructive'">
                        <BadgePercent class="h-5 w-5" />
                    </div>
                </div>
                <p class="text-3xl font-bold" :class="summary.total_profit >= 0 ? 'text-indigo-600' : 'text-destructive'">{{ formatCurrency(summary.total_invoices > 0 ? summary.total_profit / summary.total_invoices : 0) }}</p>
            </div>
        </div>

        <!-- By Shop Breakdown -->
        <div class="mb-8 rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b">
                <div class="flex items-center gap-2">
                    <Store class="h-5 w-5 text-primary" />
                    <h3 class="font-bold">Breakdown by Shop</h3>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-4 py-4 text-left">Shop</th>
                            <th class="px-4 py-4 text-center">Invoices</th>
                            <th class="px-4 py-4 text-center">Qty</th>
                            <th class="px-4 py-4 text-right">Revenue</th>
                            <th class="px-4 py-4 text-right">Cost</th>
                            <th class="px-4 py-4 text-right">Profit</th>
                            <th class="px-4 py-4 text-right">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="shop in byShop" :key="shop.shop_id" class="hover:bg-secondary/20 transition-colors">
                            <td class="px-4 py-4 font-semibold">{{ shop.shop_name }}</td>
                            <td class="px-4 py-4 text-center">{{ shop.invoice_count }}</td>
                            <td class="px-4 py-4 text-center">{{ shop.quantity }}</td>
                            <td class="px-4 py-4 text-right">{{ formatCurrency(shop.total_revenue) }}</td>
                            <td class="px-4 py-4 text-right text-rose-600">{{ formatCurrency(shop.total_cost) }}</td>
                            <td class="px-4 py-4 text-right font-semibold" :class="shop.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(shop.total_profit) }}</td>
                            <td class="px-4 py-4 text-right">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold" :class="shop.profit_margin >= 30 ? 'bg-emerald-100 text-emerald-800' : shop.profit_margin >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'">
                                    {{ shop.profit_margin }}%
                                </span>
                            </td>
                        </tr>
                        <tr v-if="byShop.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-muted-foreground italic">No invoices found for this date.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="byShop.length > 0" class="bg-secondary/20 font-semibold">
                        <tr>
                            <td class="px-4 py-4 text-sm font-bold">Total</td>
                            <td class="px-4 py-4 text-center">{{ summary.total_invoices }}</td>
                            <td class="px-4 py-4 text-center">{{ summary.total_quantity }}</td>
                            <td class="px-4 py-4 text-right">{{ formatCurrency(summary.total_revenue) }}</td>
                            <td class="px-4 py-4 text-right text-rose-600">{{ formatCurrency(summary.total_cost) }}</td>
                            <td class="px-4 py-4 text-right" :class="summary.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(summary.total_profit) }}</td>
                            <td class="px-4 py-4 text-right">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold" :class="summary.profit_margin >= 30 ? 'bg-emerald-100 text-emerald-800' : summary.profit_margin >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'">
                                    {{ summary.profit_margin }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Detailed Invoices -->
        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b">
                <div class="flex items-center gap-2">
                    <FileText class="h-5 w-5 text-primary" />
                    <h3 class="font-bold">All Invoices — {{ dateInput }}</h3>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-4 py-4">Invoice</th>
                            <th class="px-4 py-4">Shop</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4 text-center">Items</th>
                            <th class="px-4 py-4 text-center">Qty</th>
                            <th class="px-4 py-4 text-right">Revenue</th>
                            <th class="px-4 py-4 text-right">Cost</th>
                            <th class="px-4 py-4 text-right">Profit</th>
                            <th class="px-4 py-4 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-secondary/20 transition-colors">
                            <td class="px-4 py-4 font-semibold">#{{ inv.id }}</td>
                            <td class="px-4 py-4">{{ inv.shop_name }}</td>
                            <td class="px-4 py-4">
                                <Badge :variant="statusVariant(inv.status)" class="rounded-full px-2 py-0 text-[10px] capitalize">
                                    {{ inv.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-4 text-center">{{ inv.items_count }}</td>
                            <td class="px-4 py-4 text-center">{{ inv.quantity }}</td>
                            <td class="px-4 py-4 text-right">{{ formatCurrency(inv.total_revenue) }}</td>
                            <td class="px-4 py-4 text-right text-rose-600">{{ formatCurrency(inv.total_cost) }}</td>
                            <td class="px-4 py-4 text-right font-semibold" :class="inv.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(inv.total_profit) }}</td>
                            <td class="px-4 py-4 text-right">
                                <Link :href="`/admin/invoices/${inv.id}`">
                                    <Button variant="ghost" size="sm" class="h-8 rounded-lg text-xs">
                                        <ArrowUpRight class="h-3.5 w-3.5 mr-1" />
                                        View
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="invoices.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-muted-foreground italic">No invoices found for this date.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

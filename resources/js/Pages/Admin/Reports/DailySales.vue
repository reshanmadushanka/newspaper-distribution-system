<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { FileText, Store, CalendarDays, Download, TrendingUp, Receipt, Hash, ArrowUpRight, DollarSign, BadgePercent, Banknote, Newspaper, ListOrdered, Eye, EyeOff } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Select2 } from '@/Components/ui/select2'
import { Datepicker } from '@/Components/ui/datepicker'
import { ref, computed } from 'vue'

const props = defineProps({
    shopReport: Object,
    newspaperReport: Object,
    invoiceReport: Object,
    shops: Array,
    newspapers: Array,
    filters: Object,
})

const activeTab = ref('by-shop')
const dateRange = ref([
    props.filters.date_from,
    props.filters.date_to,
])
const shopId = ref(props.filters.shop_id ? String(props.filters.shop_id) : 'all')
const newspaperId = ref(props.filters.newspaper_id ? String(props.filters.newspaper_id) : 'all')
const showProfit = ref(props.filters.show_profit ?? true)

const fetchReport = () => {
    const [dateFrom, dateTo] = dateRange.value

    if (!dateFrom || !dateTo) {
        return
    }

    router.get('/admin/reports/daily-sales', {
        date_from: dateFrom,
        date_to: dateTo,
        shop_id: shopId.value === 'all' ? null : shopId.value,
        newspaper_id: newspaperId.value === 'all' ? null : newspaperId.value,
        show_profit: showProfit.value ? 1 : 0,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const downloadPdf = () => {
    const [dateFrom, dateTo] = dateRange.value

    if (!dateFrom || !dateTo) {
        return
    }

    const params = new URLSearchParams({
        report_type: activeTab.value,
        date_from: dateFrom,
        date_to: dateTo,
        show_profit: showProfit.value ? '1' : '0',
    })

    if (shopId.value !== 'all') {
        params.set('shop_id', shopId.value)
    }

    if (newspaperId.value !== 'all') {
        params.set('newspaper_id', newspaperId.value)
    }

    window.open(`/admin/reports/daily-sales/pdf?${params.toString()}`, '_blank')
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

const tabs = [
    { id: 'by-shop', label: 'Breakdown By Shop', icon: Store },
    { id: 'by-newspaper', label: 'Breakdown By Newspapers', icon: Newspaper },
    { id: 'by-invoice', label: 'Invoices by Date Range', icon: ListOrdered },
]

const shopOptions = computed(() => {
    return props.shops.map(shop => ({
        value: String(shop.id),
        label: shop.name,
    }))
})

const newspaperOptions = computed(() => {
    return props.newspapers.map(np => ({
        value: String(np.id),
        label: np.name,
    }))
})
</script>

<template>
    <Head title="Sales Report" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <TrendingUp class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Daily Sales Report</h2>
                </div>
                <p class="text-muted-foreground">Financial summary with revenue, costs, and profit margins.</p>
            </div>
        </div>

        <!-- Global Filters Bar -->
        <div class="mb-6 flex flex-wrap items-end gap-4 rounded-2xl border bg-card p-4 shadow-sm">
            <!-- Date Range Picker -->
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Date Range</label>
                <div class="relative w-full sm:w-72">
                    <CalendarDays class="absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Datepicker
                        v-model="dateRange"
                        mode="range"
                        placeholder="Select invoice date range"
                        class="w-full h-9 pl-9 pr-4 rounded-lg border bg-secondary/30 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    />
                </div>
            </div>
            <!-- Shop Dropdown - Show for By Shop and Invoices tabs -->
            <div v-if="activeTab === 'by-shop' || activeTab === 'by-invoice'">
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Shop</label>
                <Select2 
                    v-model="shopId"
                    :options="[{ value: 'all', label: 'All Shops' }, ...shopOptions]"
                    placeholder="Select Shop"
                    class="w-56"
                />
            </div>

            <!-- Newspaper Dropdown - Show for By Newspaper and Invoices tabs -->
            <div v-if="activeTab === 'by-newspaper'">
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Newspaper</label>
                <Select2 
                    v-model="newspaperId"
                    :options="[{ value: 'all', label: 'All Newspapers' }, ...newspaperOptions]"
                    placeholder="Select Newspaper"
                    class="w-56"
                />
            </div>

            <div class="flex items-end gap-2">
                <Button @click="fetchReport" class="rounded-xl shadow-lg shadow-primary/20">
                    <FileText class="mr-2 h-4 w-4" />
                    Load Report
                </Button>
                <label class="flex h-9 cursor-pointer items-center gap-2 rounded-xl border px-3 text-sm font-medium transition-colors hover:bg-secondary/50">
                    <input v-model="showProfit" type="checkbox" class="sr-only" />
                    <component :is="showProfit ? Eye : EyeOff" class="h-4 w-4" />
                    <span>{{ showProfit ? 'Profit On' : 'Profit Off' }}</span>
                </label>
                <Button @click="downloadPdf" variant="outline" class="rounded-xl">
                    <Download class="mr-2 h-4 w-4" />
                    PDF
                </Button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex flex-wrap gap-1 rounded-2xl border bg-card p-1 shadow-sm">
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
                :class="activeTab === tab.id
                    ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20'
                    : 'text-muted-foreground hover:text-foreground hover:bg-secondary/50'">
                <component :is="tab.icon" class="h-4 w-4" />
                {{ tab.label }}
            </button>
        </div>

        <!-- Tab 1: Breakdown By Shop -->
        <template v-if="activeTab === 'by-shop'">

            <!-- Summary Cards -->
            <div class="mb-8 grid gap-4 sm:grid-cols-2" :class="showProfit ? 'lg:grid-cols-4' : 'lg:grid-cols-3'">
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Invoices</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Receipt class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ shopReport.summary.total_invoices }}</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ shopReport.date_from }} to {{ shopReport.date_to }}</p>
                </div>
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Revenue</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ formatCurrency(shopReport.summary.total_revenue) }}</p>
                    <p class="text-xs text-muted-foreground mt-1">total sales</p>
                </div>
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Cost</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600">
                            <Banknote class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ formatCurrency(shopReport.summary.total_cost) }}</p>
                    <p class="text-xs text-muted-foreground mt-1">cost of goods sold</p>
                </div>
                <div v-if="showProfit" :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Net Profit</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl" :class="shopReport.summary.total_profit >= 0 ? 'bg-blue-500/10 text-blue-600' : 'bg-destructive/10 text-destructive'">
                            <DollarSign class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold" :class="shopReport.summary.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(shopReport.summary.total_profit) }}</p>
                    <p class="text-xs mt-1 flex items-center gap-1" :class="profitColorClass(shopReport.summary.profit_margin)">
                        <BadgePercent class="h-3 w-3" />
                        <span>{{ shopReport.summary.profit_margin }}% margin</span>
                    </p>
                </div>
            </div>

            <!-- By Shop Breakdown Table -->
            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
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
                                <th v-if="showProfit" class="px-4 py-4 text-right">Profit</th>
                                <th v-if="showProfit" class="px-4 py-4 text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr v-for="shop in shopReport.by_shop" :key="shop.shop_id" class="hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-4 font-semibold">{{ shop.shop_name }}</td>
                                <td class="px-4 py-4 text-center">{{ shop.invoice_count }}</td>
                                <td class="px-4 py-4 text-center">{{ shop.quantity }}</td>
                                <td class="px-4 py-4 text-right">{{ formatCurrency(shop.total_revenue) }}</td>
                                <td class="px-4 py-4 text-right text-rose-600">{{ formatCurrency(shop.total_cost) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right font-semibold" :class="shop.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(shop.total_profit) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold" :class="shop.profit_margin >= 30 ? 'bg-emerald-100 text-emerald-800' : shop.profit_margin >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'">
                                        {{ shop.profit_margin }}%
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="shopReport.by_shop.length === 0">
                                <td :colspan="showProfit ? 7 : 5" class="px-6 py-12 text-center text-muted-foreground italic">No invoices found for this date range.</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="shopReport.by_shop.length > 0" class="bg-secondary/20 font-semibold">
                            <tr>
                                <td class="px-4 py-4 text-sm font-bold">Total</td>
                                <td class="px-4 py-4 text-center">{{ shopReport.summary.total_invoices }}</td>
                                <td class="px-4 py-4 text-center">{{ shopReport.summary.total_quantity }}</td>
                                <td class="px-4 py-4 text-right">{{ formatCurrency(shopReport.summary.total_revenue) }}</td>
                                <td class="px-4 py-4 text-right text-rose-600">{{ formatCurrency(shopReport.summary.total_cost) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right" :class="shopReport.summary.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(shopReport.summary.total_profit) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold" :class="shopReport.summary.profit_margin >= 30 ? 'bg-emerald-100 text-emerald-800' : shopReport.summary.profit_margin >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'">
                                        {{ shopReport.summary.profit_margin }}%
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </template>

        <!-- Tab 2: Breakdown By Newspaper -->
        <template v-if="activeTab === 'by-newspaper'">
            <!-- Summary Cards -->
            <div class="mb-8 grid gap-4 sm:grid-cols-2" :class="showProfit ? 'lg:grid-cols-4' : 'lg:grid-cols-3'">
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Newspapers</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Newspaper class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ newspaperReport.summary.total_newspapers }}</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ newspaperReport.date_from }} to {{ newspaperReport.date_to }}</p>
                </div>
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Quantity</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                            <Hash class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ newspaperReport.summary.total_quantity }}</p>
                </div>
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Revenue</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ formatCurrency(newspaperReport.summary.total_revenue) }}</p>
                </div>
                <div v-if="showProfit" :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Net Profit</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl" :class="newspaperReport.summary.total_profit >= 0 ? 'bg-blue-500/10 text-blue-600' : 'bg-destructive/10 text-destructive'">
                            <DollarSign class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold" :class="newspaperReport.summary.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(newspaperReport.summary.total_profit) }}</p>
                    <p class="text-xs mt-1 flex items-center gap-1" :class="profitColorClass(newspaperReport.summary.profit_margin)">
                        <BadgePercent class="h-3 w-3" />
                        <span>{{ newspaperReport.summary.profit_margin }}% margin</span>
                    </p>
                </div>
            </div>

            <!-- By Newspaper Breakdown Table -->
            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b">
                    <div class="flex items-center gap-2">
                        <Newspaper class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">Breakdown by Newspaper</h3>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-4 py-4 text-left">Newspaper</th>
                                <th class="px-4 py-4 text-center">Invoices</th>
                                <th class="px-4 py-4 text-center">Qty</th>
                                <th class="px-4 py-4 text-right">Revenue</th>
                                <th class="px-4 py-4 text-right">Cost</th>
                                <th v-if="showProfit" class="px-4 py-4 text-right">Profit</th>
                                <th v-if="showProfit" class="px-4 py-4 text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr v-for="np in newspaperReport.by_newspaper" :key="np.newspaper_id" class="hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-4 font-semibold">{{ np.newspaper_name }}</td>
                                <td class="px-4 py-4 text-center">{{ np.invoice_count }}</td>
                                <td class="px-4 py-4 text-center">{{ np.quantity }}</td>
                                <td class="px-4 py-4 text-right">{{ formatCurrency(np.total_revenue) }}</td>
                                <td class="px-4 py-4 text-right text-rose-600">{{ formatCurrency(np.total_cost) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right font-semibold" :class="np.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(np.total_profit) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold" :class="np.profit_margin >= 30 ? 'bg-emerald-100 text-emerald-800' : np.profit_margin >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'">
                                        {{ np.profit_margin }}%
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="newspaperReport.by_newspaper.length === 0">
                                <td :colspan="showProfit ? 7 : 5" class="px-6 py-12 text-center text-muted-foreground italic">No invoices found for this date range.</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="newspaperReport.by_newspaper.length > 0" class="bg-secondary/20 font-semibold">
                            <tr>
                                <td class="px-4 py-4 text-sm font-bold">Total</td>
                                <td class="px-4 py-4 text-center">—</td>
                                <td class="px-4 py-4 text-center">{{ newspaperReport.summary.total_quantity }}</td>
                                <td class="px-4 py-4 text-right">{{ formatCurrency(newspaperReport.summary.total_revenue) }}</td>
                                <td class="px-4 py-4 text-right text-rose-600">{{ formatCurrency(newspaperReport.summary.total_cost) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right" :class="newspaperReport.summary.total_profit >= 0 ? 'text-blue-600' : 'text-destructive'">{{ formatCurrency(newspaperReport.summary.total_profit) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold" :class="newspaperReport.summary.profit_margin >= 30 ? 'bg-emerald-100 text-emerald-800' : newspaperReport.summary.profit_margin >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'">
                                        {{ newspaperReport.summary.profit_margin }}%
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </template>

        <!-- Tab 3: Invoices by Date Range -->
        <template v-if="activeTab === 'by-invoice'">
            <!-- Summary Cards -->
            <div class="mb-8 grid gap-4 sm:grid-cols-2" :class="showProfit ? 'lg:grid-cols-3' : 'lg:grid-cols-2'">
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Invoices</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Receipt class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ invoiceReport.summary.total_invoices }}</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ invoiceReport.date_from }} to {{ invoiceReport.date_to }}</p>
                </div>
                <div :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Revenue</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold">{{ formatCurrency(invoiceReport.summary.total_revenue) }}</p>
                    <p class="text-xs text-muted-foreground mt-1">sum of all invoice amounts</p>
                </div>
                <div v-if="showProfit" :class="cardClass">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Profit (12%)</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600">
                            <DollarSign class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-blue-600">{{ formatCurrency(invoiceReport.summary.total_profit) }}</p>
                    <p class="text-xs mt-1 flex items-center gap-1 text-muted-foreground">
                        <BadgePercent class="h-3 w-3" />
                        <span>fixed 12% profit per invoice</span>
                    </p>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b">
                    <div class="flex items-center gap-2">
                        <ListOrdered class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">Invoices — {{ invoiceReport.date_from }} to {{ invoiceReport.date_to }}</h3>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-4 py-4">Invoice</th>
                                <th class="px-4 py-4">Date</th>
                                <th class="px-4 py-4">Shop</th>
                                <th class="px-4 py-4">Status</th>
                                <th class="px-4 py-4 text-center">Items</th>
                                <th class="px-4 py-4 text-right">Amount</th>
                                <th v-if="showProfit" class="px-4 py-4 text-right">Profit (12%)</th>
                                <th class="px-4 py-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr v-for="inv in invoiceReport.invoices" :key="inv.id" class="hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-4 font-semibold">#{{ inv.id }}</td>
                                <td class="px-4 py-4">{{ inv.invoice_date }}</td>
                                <td class="px-4 py-4">{{ inv.shop_name }}</td>
                                <td class="px-4 py-4">
                                    <Badge :variant="statusVariant(inv.status)" class="rounded-full px-2 py-0 text-[10px] capitalize">
                                        {{ inv.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 text-center">{{ inv.items_count }}</td>
                                <td class="px-4 py-4 text-right">{{ formatCurrency(inv.total_amount) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right font-semibold text-blue-600">{{ formatCurrency(inv.profit) }}</td>
                                <td class="px-4 py-4 text-right">
                                    <Link :href="`/admin/invoices/${inv.id}`">
                                        <Button variant="ghost" size="sm" class="h-8 rounded-lg text-xs">
                                            <ArrowUpRight class="h-3.5 w-3.5 mr-1" />
                                            View
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="invoiceReport.invoices.length === 0">
                                <td :colspan="showProfit ? 8 : 7" class="px-6 py-12 text-center text-muted-foreground italic">No invoices found for this date range.</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="invoiceReport.invoices.length > 0" class="bg-secondary/20 font-semibold">
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-sm font-bold">Total</td>
                                <td class="px-4 py-4 text-center">{{ invoiceReport.summary.total_invoices }}</td>
                                <td class="px-4 py-4 text-right">{{ formatCurrency(invoiceReport.summary.total_revenue) }}</td>
                                <td v-if="showProfit" class="px-4 py-4 text-right text-blue-600">{{ formatCurrency(invoiceReport.summary.total_profit) }}</td>
                                <td class="px-4 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>

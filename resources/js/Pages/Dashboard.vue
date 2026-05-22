<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Users,
    Shield,
    KeyRound,
    TrendingUp,
    Clock,
    ArrowUpRight,
    ArrowDownRight,
    Newspaper,
    Store,
    FileText,
    DollarSign,
    AlertCircle,
    Check,
    Receipt,
    CheckCircle2
} from 'lucide-vue-next'

const props = defineProps({
    stats: Array,
    recentInvoices: Array,
    pendingSystemInvoices: Array,
    systemInvoiceStats: Object,
    range: { type: String, default: '30d' },
    chartData: { type: Array, default: () => [] },
})

const iconMap = {
    Users,
    Shield,
    KeyRound,
    Newspaper,
    Store,
    FileText,
    DollarSign
}

const ranges = [
    { value: '7d', label: '7d' },
    { value: '30d', label: '30d' },
    { value: '90d', label: '90d' },
]

function setRange(value) {
    if (value === props.range) return
    router.get('/dashboard', { range: value }, {
        preserveScroll: true,
        preserveState: false,
    })
}

const chartWidth = 800
const chartHeight = 220
const chartPadding = { top: 16, right: 16, bottom: 28, left: 56 }

const chartGeometry = computed(() => {
    const data = props.chartData || []
    const innerW = chartWidth - chartPadding.left - chartPadding.right
    const innerH = chartHeight - chartPadding.top - chartPadding.bottom

    if (data.length === 0) {
        return { hasData: false, points: '', area: '', maxRevenue: 0, ticks: [], xLabels: [] }
    }

    const maxRevenue = Math.max(...data.map(d => d.revenue), 1)
    const stepX = data.length > 1 ? innerW / (data.length - 1) : 0
    const totalRevenue = data.reduce((sum, d) => sum + d.revenue, 0)

    const coords = data.map((d, i) => {
        const x = chartPadding.left + stepX * i
        const y = chartPadding.top + innerH - (d.revenue / maxRevenue) * innerH
        return { x, y, ...d }
    })

    const points = coords.map(p => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ')
    const baseY = chartPadding.top + innerH
    const area = `${chartPadding.left},${baseY} ${points} ${coords[coords.length - 1].x.toFixed(1)},${baseY}`

    const ticks = [0, 0.5, 1].map(frac => ({
        y: chartPadding.top + innerH - frac * innerH,
        value: maxRevenue * frac,
    }))

    const labelIndices = data.length <= 4
        ? data.map((_, i) => i)
        : [0, Math.floor(data.length / 2), data.length - 1]
    const xLabels = labelIndices.map(i => ({
        x: coords[i].x,
        label: formatShortDate(data[i].date),
    }))

    return { hasData: true, points, area, maxRevenue, ticks, xLabels, coords, totalRevenue }
})

function formatShortDate(iso) {
    const d = new Date(iso)
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function formatCurrency(value) {
    return 'Rs. ' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}
</script>

<template>

    <Head title="Dashboard" />
    <AdminLayout>
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Welcome back, Admin!</h2>
                <p class="text-muted-foreground mt-1">Here's what's happening with your distribution system today.</p>
            </div>
            <div class="inline-flex items-center rounded-xl border bg-card p-1 shadow-sm" role="tablist" aria-label="Date range">
                <button v-for="r in ranges" :key="r.value" type="button" role="tab"
                    :aria-selected="range === r.value"
                    @click="setRange(r.value)"
                    :class="[
                        'px-3 py-1.5 text-sm font-medium rounded-lg transition-colors',
                        range === r.value
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground hover:bg-secondary/60'
                    ]">
                    {{ r.label }}
                </button>
            </div>
        </div>

        <!-- Pending Payments Alert -->
        <div v-if="systemInvoiceStats.pending_count > 0" class="mb-8 bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 rounded-lg p-6 shadow">
            <div class="flex items-start gap-4">
                <AlertCircle class="h-6 w-6 text-yellow-600 flex-shrink-0 mt-1" />
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-yellow-900 mb-1">
                        ⚠️ Payment Due: {{ systemInvoiceStats.pending_count }} invoice{{ systemInvoiceStats.pending_count !== 1 ? 's' : '' }}
                    </h3>
                    <p class="text-yellow-800 mb-4">
                        You have pending invoices totaling <span class="font-bold">Rs. {{ Number(systemInvoiceStats.pending_amount).toFixed(2) }}</span> that need your attention.
                    </p>
                    <Link href="/admin/payments/pending">
                        <button class="px-4 py-2 bg-yellow-600 text-white rounded-lg font-medium hover:bg-yellow-700 transition-colors inline-flex items-center gap-2">
                            <Check class="h-4 w-4" />
                            View & Pay Now
                        </button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 mb-8">
            <div v-for="stat in stats" :key="stat.label"
                class="group rounded-2xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div :class="['p-2.5 rounded-xl transition-colors', stat.bg, stat.color]">
                        <component :is="iconMap[stat.icon]" class="h-6 w-6" />
                    </div>
                    <div v-if="stat.change"
                        :class="['flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full', stat.trendingUp ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600']">
                        <component :is="stat.trendingUp ? ArrowUpRight : ArrowDownRight" class="h-3 w-3" />
                        {{ stat.change }}
                    </div>
                </div>
                <div>
                    <p class="text-sm font-medium text-muted-foreground">{{ stat.label }}</p>
                    <h3 class="text-2xl font-bold mt-1">{{ stat.value }}</h3>
                </div>
            </div>
        </div>

        <!-- Revenue Trend Chart -->
        <div class="mb-8 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="flex items-start justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-lg font-bold">Revenue Trend</h3>
                    <p class="text-xs text-muted-foreground">Daily invoice revenue over the last {{ range }}</p>
                </div>
                <div v-if="chartGeometry.hasData" class="text-right">
                    <p class="text-xs text-muted-foreground">Period total</p>
                    <p class="text-lg font-bold">{{ formatCurrency(chartGeometry.totalRevenue) }}</p>
                </div>
            </div>

            <div v-if="!chartGeometry.hasData" class="text-center py-12 text-muted-foreground">
                No revenue data for this range.
            </div>

            <svg v-else :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-auto" preserveAspectRatio="none"
                role="img" aria-label="Revenue trend chart">
                <defs>
                    <linearGradient id="revenueAreaGradient" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="currentColor" stop-opacity="0.25" />
                        <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
                    </linearGradient>
                </defs>

                <g class="text-muted-foreground" stroke="currentColor" stroke-opacity="0.15" stroke-dasharray="3 3">
                    <line v-for="(tick, i) in chartGeometry.ticks" :key="i"
                        :x1="chartPadding.left" :x2="chartWidth - chartPadding.right"
                        :y1="tick.y" :y2="tick.y" />
                </g>

                <g class="text-muted-foreground" font-size="10" fill="currentColor">
                    <text v-for="(tick, i) in chartGeometry.ticks" :key="i"
                        :x="chartPadding.left - 8" :y="tick.y + 3" text-anchor="end">
                        {{ formatCurrency(tick.value) }}
                    </text>
                </g>

                <polygon class="text-primary" :points="chartGeometry.area" fill="url(#revenueAreaGradient)" />
                <polyline class="text-primary" :points="chartGeometry.points"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

                <g class="text-primary">
                    <circle v-for="(p, i) in chartGeometry.coords" :key="i"
                        :cx="p.x" :cy="p.y" r="2.5" fill="currentColor">
                        <title>{{ formatShortDate(p.date) }} — {{ formatCurrency(p.revenue) }} ({{ p.invoices }} invoices)</title>
                    </circle>
                </g>

                <g class="text-muted-foreground" font-size="10" fill="currentColor">
                    <text v-for="(label, i) in chartGeometry.xLabels" :key="i"
                        :x="label.x" :y="chartHeight - 8" text-anchor="middle">
                        {{ label.label }}
                    </text>
                </g>
            </svg>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold">Recent Distributions</h3>
                    <Link href="/admin/invoices" class="text-sm font-medium text-primary hover:underline">View all</Link>
                </div>
                <div class="space-y-4">
                    <div v-for="invoice in recentInvoices" :key="invoice.id"
                        class="flex items-center justify-between p-3 rounded-xl hover:bg-secondary/30 transition-colors border border-transparent hover:border-border">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-full bg-secondary flex items-center justify-center text-primary font-bold uppercase">
                                {{ invoice.shop.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ invoice.shop.name }}</p>
                                <p class="text-xs text-muted-foreground flex items-center gap-1">
                                    <Clock class="h-3 w-3" /> {{ invoice.invoice_date }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold">Rs. {{ parseFloat(invoice.total_amount).toFixed(2) }}</p>
                            <p class="text-[10px] uppercase font-bold text-emerald-600">{{ invoice.status }}</p>
                        </div>
                    </div>
                    <div v-if="recentInvoices.length === 0" class="text-center py-8 text-muted-foreground">
                        No recent distributions found.
                    </div>
                </div>
            </div>

            <!-- Quick Actions / Tips -->
            <div class="space-y-6">
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold flex items-center gap-2">
                            <Receipt class="h-5 w-5 text-muted-foreground" />
                            Pending Payments
                        </h3>
                        <Link v-if="pendingSystemInvoices && pendingSystemInvoices.length > 0"
                            href="/admin/payments/pending"
                            class="text-sm font-medium text-primary hover:underline">
                            View all
                        </Link>
                    </div>
                    <div v-if="pendingSystemInvoices && pendingSystemInvoices.length > 0" class="space-y-3">
                        <Link v-for="invoice in pendingSystemInvoices" :key="invoice.id"
                            href="/admin/payments/pending"
                            class="block p-3 rounded-xl border border-transparent hover:border-border hover:bg-secondary/30 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-sm truncate">{{ invoice.reason || 'System invoice' }}</p>
                                    <p class="text-xs text-muted-foreground truncate">
                                        From {{ invoice.creator?.name || 'System' }}
                                    </p>
                                </div>
                                <p class="text-sm font-bold whitespace-nowrap">
                                    Rs. {{ parseFloat(invoice.amount).toFixed(2) }}
                                </p>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="text-center py-6">
                        <CheckCircle2 class="h-8 w-8 mx-auto mb-2 text-emerald-500" />
                        <p class="text-sm font-medium">All caught up</p>
                        <p class="text-xs text-muted-foreground">No pending system invoices.</p>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <Link :href="'/admin/shops'"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <Store class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">Manage Shops</span>
                        </Link>
                        <Link :href="'/admin/invoices/create'"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <FileText class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">New Invoice</span>
                        </Link>
                        <Link :href="'/admin/newspapers'"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <Newspaper class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">Manage Newspapers</span>
                        </Link>
                        <Link :href="'/admin/payments/pending'"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <Receipt class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">Pending Payments</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

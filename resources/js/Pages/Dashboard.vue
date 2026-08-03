<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useTranslation } from '@/Composables/useTranslation'
import MonthlyIncomeChart from '@/Components/Dashboard/MonthlyIncomeChart.vue'
import ShopRevenueChart from '@/Components/Dashboard/ShopRevenueChart.vue'
import InsightsSummary from '@/Components/Dashboard/InsightsSummary.vue'
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
    PlusCircle,
    BarChart3
} from 'lucide-vue-next'

const { t } = useTranslation()

const props = defineProps({
    stats: {
        type: Array,
        default: () => []
    },
    recentInvoices: {
        type: Array,
        default: () => []
    },
    pendingSystemInvoices: {
        type: Array,
        default: () => []
    },
    systemInvoiceStats: {
        type: Object,
        default: () => ({ pending_count: 0, pending_amount: 0 })
    },
    monthlyTrends: {
        type: Array,
        default: () => []
    },
    monthlyOverview: {
        type: Object,
        default: () => ({})
    },
    topShops: {
        type: Array,
        default: () => []
    },
    topNewspapers: {
        type: Array,
        default: () => []
    },
    smartInsights: {
        type: Array,
        default: () => []
    }
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

// Icon styling dictionary for light theme
const statIconStyles = {
    Store: 'bg-indigo-50 text-indigo-600 border border-indigo-100',
    Newspaper: 'bg-purple-50 text-purple-600 border border-purple-100',
    DollarSign: 'bg-emerald-50 text-emerald-600 border border-emerald-100',
    FileText: 'bg-amber-50 text-amber-600 border border-amber-100',
}
</script>

<template>
    <Head :title="t('navigation.dashboard')" />
    <AdminLayout>
        <!-- Page Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/80 pb-6">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ t('dashboard.welcome_back') }}</h2>
                <p class="text-sm font-medium text-slate-500 mt-1">{{ t('dashboard.description') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <Link href="/admin/invoices">
                    <button class="px-4 py-2.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-xl font-bold text-xs shadow-xs transition-all inline-flex items-center gap-2">
                        <PlusCircle class="h-4 w-4" />
                        {{ t('invoices.create_invoice') }}
                    </button>
                </Link>
            </div>
        </div>

        <!-- Pending System Payments Alert -->
        <div v-if="systemInvoiceStats && systemInvoiceStats.pending_count > 0" class="mb-8 bg-amber-50/90 border-l-4 border-amber-500 rounded-2xl p-6 shadow-xs border border-amber-200">
            <div class="flex items-start gap-4">
                <div class="p-3 rounded-xl bg-amber-100 text-amber-700 flex-shrink-0 border border-amber-200">
                    <AlertCircle class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-extrabold text-amber-950 mb-1">
                        ⚠️ {{ t('dashboard.payment_due', { count: systemInvoiceStats.pending_count }) }}
                    </h3>
                    <p class="text-xs font-medium text-amber-900 mb-4 leading-relaxed">
                        {{ t('dashboard.pending_invoices_message', { amount: Number(systemInvoiceStats.pending_amount).toFixed(2) }) }}
                    </p>
                    <Link href="/admin/payments/pending">
                        <button class="px-4 py-2 bg-amber-600 text-white rounded-xl font-bold text-xs hover:bg-amber-700 transition-colors inline-flex items-center gap-2 shadow-xs">
                            <Check class="h-4 w-4" />
                            {{ t('dashboard.view_pay_now') }}
                        </button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Primary KPI Metrics Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 mb-8">
            <div 
                v-for="stat in stats" 
                :key="stat.label"
                class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-xs transition-all hover:shadow-md hover:border-slate-300">
                <div class="flex items-center justify-between mb-4">
                    <div :class="['p-3 rounded-xl transition-colors', statIconStyles[stat.icon] || 'bg-slate-100 text-slate-600']">
                        <component :is="iconMap[stat.icon] || BarChart3" class="h-6 w-6" />
                    </div>
                    <div
                        :class="[
                            'flex items-center gap-1 text-xs font-extrabold px-2.5 py-1 rounded-full border',
                            stat.trendingUp 
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                : 'bg-rose-50 text-rose-700 border-rose-200'
                        ]">
                        <component :is="stat.trendingUp ? ArrowUpRight : ArrowDownRight" class="h-3.5 w-3.5" />
                        {{ stat.change }}
                    </div>
                </div>
                <div>
                    <p class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">{{ stat.label }}</p>
                    <h3 class="text-2xl font-extrabold mt-1 text-slate-900 tracking-tight">{{ stat.value }}</h3>
                </div>
            </div>
        </div>

        <!-- Monthly Income Interactive Chart -->
        <div class="mb-8">
            <MonthlyIncomeChart 
                :trends="monthlyTrends" 
                :overview="monthlyOverview" 
            />
        </div>

        <!-- Smart Business Insights -->
        <div class="mb-8">
            <InsightsSummary :insights="smartInsights" />
        </div>

        <!-- Top Outlets & Top Newspapers Section -->
        <div class="mb-8">
            <ShopRevenueChart 
                :topShops="topShops" 
                :topNewspapers="topNewspapers" 
            />
        </div>

        <!-- Recent Distributions & Quick Actions Section -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Recent Activity List -->
            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between mb-5 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ t('dashboard.recent_distributions') }}</h3>
                        <p class="text-xs text-slate-500">Latest invoices generated across distribution outlets</p>
                    </div>
                    <Link href="/admin/invoices" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 hover:underline">
                        {{ t('common.view_all') }}
                    </Link>
                </div>
                <div class="space-y-3">
                    <div 
                        v-for="invoice in recentInvoices" 
                        :key="invoice.id"
                        class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100/80 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-extrabold text-sm uppercase shadow-2xs">
                                {{ invoice.shop ? invoice.shop.name.charAt(0) : '#' }}
                            </div>
                            <div>
                                <p class="font-extrabold text-xs text-slate-900">{{ invoice.shop ? invoice.shop.name : 'Unknown Shop' }}</p>
                                <p class="text-[11px] font-medium text-slate-500 flex items-center gap-1 mt-0.5">
                                    <Clock class="h-3 w-3" /> {{ invoice.invoice_date }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-extrabold text-slate-900">Rs. {{ parseFloat(invoice.total_amount).toFixed(2) }}</p>
                            <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] uppercase font-extrabold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ invoice.status }}
                            </span>
                        </div>
                    </div>
                    <div v-if="recentInvoices.length === 0" class="text-center py-10 text-xs text-slate-500">
                        {{ t('dashboard.no_recent_distributions') }}
                    </div>
                </div>
            </div>

            <!-- Side Cards: Upgrade & Quick Actions -->
            <div class="space-y-6">
                <!-- Pro Upgrade Banner -->
                <div class="rounded-2xl bg-indigo-900 p-6 text-white shadow-md border border-indigo-800">
                    <h3 class="text-lg font-bold mb-2">{{ t('dashboard.upgrade_pro') }}</h3>
                    <p class="text-xs opacity-90 mb-4 leading-relaxed text-indigo-100">{{ t('dashboard.upgrade_description') }}</p>
                    <button class="w-full py-2.5 rounded-xl bg-white text-indigo-900 hover:bg-slate-100 font-bold text-xs transition-transform hover:scale-[1.02] active:scale-[0.98] shadow-xs">
                        {{ t('dashboard.learn_more') }}
                    </button>
                </div>

                <!-- Quick Actions Toolbar -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold mb-4 text-slate-900">{{ t('dashboard.quick_actions') }}</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <Link 
                            href="/admin/shops"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-600 transition-all text-center group">
                            <Store class="h-5 w-5 mb-2 text-indigo-600 group-hover:scale-110 transition-transform" />
                            <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600">{{ t('navigation.manage_shops') }}</span>
                        </Link>
                        <Link 
                            href="/admin/invoices"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-600 transition-all text-center group">
                            <FileText class="h-5 w-5 mb-2 text-indigo-600 group-hover:scale-110 transition-transform" />
                            <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600">{{ t('invoices.create_invoice') }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

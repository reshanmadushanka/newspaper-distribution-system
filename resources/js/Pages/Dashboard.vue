<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useTranslation } from '@/Composables/useTranslation'
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
    Check
} from 'lucide-vue-next'

const { t } = useTranslation()

const props = defineProps({
    stats: Array,
    recentInvoices: Array,
    pendingSystemInvoices: Array,
    systemInvoiceStats: Object,
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
</script>

<template>

    <Head :title="t('navigation.dashboard')" />
    <AdminLayout>
        <div class="mb-8">
            <h2 class="text-3xl font-bold tracking-tight">{{ t('dashboard.welcome_back') }}</h2>
            <p class="text-muted-foreground mt-1">{{ t('dashboard.description') }}</p>
        </div>

        <!-- Pending Payments Alert -->
        <div v-if="systemInvoiceStats.pending_count > 0" class="mb-8 bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 rounded-lg p-6 shadow">
            <div class="flex items-start gap-4">
                <AlertCircle class="h-6 w-6 text-yellow-600 flex-shrink-0 mt-1" />
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-yellow-900 mb-1">
                        ⚠️ {{ t('dashboard.payment_due', { count: systemInvoiceStats.pending_count }) }}
                    </h3>
                    <p class="text-yellow-800 mb-4">
                        {{ t('dashboard.pending_invoices_message', { amount: Number(systemInvoiceStats.pending_amount).toFixed(2) }) }}
                    </p>
                    <Link href="/admin/payments/pending">
                        <button class="px-4 py-2 bg-yellow-600 text-white rounded-lg font-medium hover:bg-yellow-700 transition-colors inline-flex items-center gap-2">
                            <Check class="h-4 w-4" />
                            {{ t('dashboard.view_pay_now') }}
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
                    <div
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

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold">{{ t('dashboard.recent_distributions') }}</h3>
                    <button class="text-sm font-medium text-primary hover:underline">{{ t('common.view_all') }}</button>
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
                        {{ t('dashboard.no_recent_distributions') }}
                    </div>
                </div>
            </div>

            <!-- Quick Actions / Tips -->
            <div class="space-y-6">
                <div class="rounded-2xl bg-primary p-6 text-primary-foreground shadow-lg shadow-primary/20">
                    <h3 class="text-lg font-bold mb-2">{{ t('dashboard.upgrade_pro') }}</h3>
                    <p class="text-sm opacity-90 mb-4">{{ t('dashboard.upgrade_description') }}</p>
                    <button
                        class="w-full py-2.5 rounded-xl bg-white text-primary font-bold text-sm transition-transform hover:scale-[1.02] active:scale-[0.98]">
                        {{ t('dashboard.learn_more') }}
                    </button>
                </div>

                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4">{{ t('dashboard.quick_actions') }}</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <Link :href="'/admin/shops'"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <Store class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">{{ t('navigation.manage_shops') }}</span>
                        </Link>
                        <Link :href="'/admin/invoices'"
                            class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <FileText class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">{{ t('invoices.create_invoice') }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

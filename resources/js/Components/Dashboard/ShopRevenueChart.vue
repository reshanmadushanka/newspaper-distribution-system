<script setup>
import { computed } from 'vue'
import { useTranslation } from '@/Composables/useTranslation'
import { Store, Newspaper, ArrowUpRight } from 'lucide-vue-next'

const props = defineProps({
    topShops: {
        type: Array,
        default: () => []
    },
    topNewspapers: {
        type: Array,
        default: () => []
    }
})

const { t } = useTranslation()

const formatCurrency = (val) => {
    return 'Rs. ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const colorPalette = [
    { bg: 'bg-emerald-600', text: 'text-emerald-700', badge: 'bg-emerald-100 text-emerald-800' },
    { bg: 'bg-indigo-600', text: 'text-indigo-700', badge: 'bg-indigo-100 text-indigo-800' },
    { bg: 'bg-sky-600', text: 'text-sky-700', badge: 'bg-sky-100 text-sky-800' },
    { bg: 'bg-amber-600', text: 'text-amber-700', badge: 'bg-amber-100 text-amber-800' },
    { bg: 'bg-purple-600', text: 'text-purple-700', badge: 'bg-purple-100 text-purple-800' },
]
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <!-- Top Outlets Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2.5 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600">
                            <Store class="h-5 w-5" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">{{ t('dashboard.top_outlets') }}</h3>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200/60">
                        This Month
                    </span>
                </div>

                <div v-if="topShops.length === 0" class="py-8 text-center text-xs text-slate-500">
                    {{ t('dashboard.no_top_outlets') }}
                </div>

                <div v-else class="space-y-4">
                    <div v-for="(shop, index) in topShops" :key="shop.id" class="group">
                        <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[11px] font-extrabold text-white shadow-2xs" :class="colorPalette[index % colorPalette.length].bg">
                                    {{ index + 1 }}
                                </span>
                                <span class="truncate max-w-[160px] sm:max-w-[200px] text-slate-900">{{ shop.name }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-900 font-extrabold">{{ formatCurrency(shop.net_income) }}</span>
                                <span class="ml-2 text-[11px] font-semibold text-slate-500">({{ shop.percentage }}%)</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/40">
                            <div 
                                class="h-full rounded-full transition-all duration-500 group-hover:brightness-110"
                                :class="colorPalette[index % colorPalette.length].bg"
                                :style="{ width: `${Math.max(6, shop.percentage)}%` }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Distributed Newspapers Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2.5 rounded-xl bg-purple-50 border border-purple-100 text-purple-600">
                            <Newspaper class="h-5 w-5" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">{{ t('dashboard.top_newspapers') }}</h3>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200/60">
                        Volume & Value
                    </span>
                </div>

                <div v-if="topNewspapers.length === 0" class="py-8 text-center text-xs text-slate-500">
                    {{ t('dashboard.no_top_newspapers') }}
                </div>

                <div v-else class="space-y-3">
                    <div 
                        v-for="(paper, index) in topNewspapers" 
                        :key="paper.id"
                        class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100/80 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center border border-slate-200 font-extrabold text-xs text-purple-700 shadow-2xs">
                                #{{ index + 1 }}
                            </div>
                            <div>
                                <p class="text-xs font-extrabold text-slate-900 truncate max-w-[140px] sm:max-w-[180px]">{{ paper.name }}</p>
                                <p class="text-[11px] font-medium text-slate-500 mt-0.5">
                                    {{ t('dashboard.qty_sold') }}: <span class="font-extrabold text-slate-900">{{ paper.quantity_sold }}</span> 
                                    <span v-if="paper.return_quantity > 0" class="text-rose-600 font-semibold ml-1">({{ paper.return_quantity }} returned)</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-extrabold text-slate-900">{{ formatCurrency(paper.net_amount) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useTranslation } from '@/Composables/useTranslation'
import {
    Sparkles,
    TrendingUp,
    TrendingDown,
    Award,
    AlertTriangle,
    CheckCircle2,
    Store,
    Minus
} from 'lucide-vue-next'

const props = defineProps({
    insights: {
        type: Array,
        default: () => []
    }
})

const { t } = useTranslation()

const iconMap = {
    TrendingUp,
    TrendingDown,
    Award,
    AlertTriangle,
    CheckCircle2,
    Store,
    Minus
}

const getStyle = (type) => {
    switch (type) {
        case 'success':
            return {
                bg: 'bg-emerald-50/90 border-emerald-200',
                iconBg: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                title: 'text-emerald-950',
                text: 'text-emerald-900'
            }
        case 'warning':
            return {
                bg: 'bg-amber-50/90 border-amber-200',
                iconBg: 'bg-amber-100 text-amber-700 border border-amber-200',
                title: 'text-amber-950',
                text: 'text-amber-900'
            }
        default:
            return {
                bg: 'bg-indigo-50/90 border-indigo-200',
                iconBg: 'bg-indigo-100 text-indigo-700 border border-indigo-200',
                title: 'text-indigo-950',
                text: 'text-indigo-900'
            }
    }
}
</script>

<template>
    <div v-if="insights.length > 0" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2.5 mb-5 border-b border-slate-100 pb-3">
            <div class="p-2.5 rounded-xl bg-purple-50 border border-purple-100 text-purple-600">
                <Sparkles class="h-5 w-5" />
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">{{ t('dashboard.business_insights') }}</h3>
                <p class="text-xs text-slate-500">Automated financial and distribution trend intelligence</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div 
                v-for="(item, index) in insights" 
                :key="index"
                :class="['p-4 rounded-xl border transition-all duration-200 hover:shadow-xs flex items-start gap-3', getStyle(item.type).bg]">
                <div :class="['p-2 rounded-lg flex-shrink-0 mt-0.5 shadow-2xs', getStyle(item.type).iconBg]">
                    <component :is="iconMap[item.icon] || Sparkles" class="h-4 w-4" />
                </div>
                <div>
                    <h4 :class="['text-xs font-extrabold tracking-tight', getStyle(item.type).title]">{{ item.title }}</h4>
                    <p :class="['text-[11px] font-semibold leading-relaxed mt-1 opacity-90', getStyle(item.type).text]">
                        {{ item.message }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

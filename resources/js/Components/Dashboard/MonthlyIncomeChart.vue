<script setup>
import { ref, computed } from 'vue'
import { useTranslation } from '@/Composables/useTranslation'
import {
    TrendingUp,
    TrendingDown,
    DollarSign,
    RotateCcw,
    Calendar,
    Layers
} from 'lucide-vue-next'

const props = defineProps({
    trends: {
        type: Array,
        default: () => []
    },
    overview: {
        type: Object,
        default: () => ({})
    }
})

const { t } = useTranslation()

const timeframe = ref(12) // 6 or 12
const activeDataset = ref('net_income') // 'net_income', 'gross_sales', 'returns', 'all'
const hoveredIndex = ref(null)

const filteredTrends = computed(() => {
    if (!props.trends || props.trends.length === 0) return []
    return timeframe.value === 6 ? props.trends.slice(-6) : props.trends
})

const maxVal = computed(() => {
    if (filteredTrends.value.length === 0) return 100
    const max = Math.max(
        ...filteredTrends.value.map(d => Math.max(d.gross_sales, d.net_income, d.returns))
    )
    return max > 0 ? max * 1.15 : 100
})

const formatCurrency = (val) => {
    return 'Rs. ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Chart SVG calculations
const chartWidth = 700
const chartHeight = 240
const padding = { top: 20, right: 20, bottom: 40, left: 55 }

const innerWidth = computed(() => chartWidth - padding.left - padding.right)
const innerHeight = computed(() => chartHeight - padding.top - padding.bottom)

const pointsNet = computed(() => {
    const data = filteredTrends.value
    if (data.length === 0) return []
    const step = innerWidth.value / (data.length - 1 || 1)
    return data.map((d, i) => {
        const x = padding.left + i * step
        const y = padding.top + innerHeight.value - (d.net_income / maxVal.value) * innerHeight.value
        return { x, y, data: d, index: i }
    })
})

const pointsGross = computed(() => {
    const data = filteredTrends.value
    if (data.length === 0) return []
    const step = innerWidth.value / (data.length - 1 || 1)
    return data.map((d, i) => {
        const x = padding.left + i * step
        const y = padding.top + innerHeight.value - (d.gross_sales / maxVal.value) * innerHeight.value
        return { x, y, data: d, index: i }
    })
})

const pointsReturns = computed(() => {
    const data = filteredTrends.value
    if (data.length === 0) return []
    const step = innerWidth.value / (data.length - 1 || 1)
    return data.map((d, i) => {
        const x = padding.left + i * step
        const y = padding.top + innerHeight.value - (d.returns / maxVal.value) * innerHeight.value
        return { x, y, data: d, index: i }
    })
})

// Generate SVG smooth path d string (cubic bezier)
const makeSmoothPath = (pts) => {
    if (pts.length === 0) return ''
    if (pts.length === 1) return `M ${pts[0].x} ${pts[0].y}`

    let d = `M ${pts[0].x} ${pts[0].y}`
    for (let i = 0; i < pts.length - 1; i++) {
        const curr = pts[i]
        const next = pts[i + 1]
        const cpx1 = curr.x + (next.x - curr.x) / 2
        const cpy1 = curr.y
        const cpx2 = curr.x + (next.x - curr.x) / 2
        const cpy2 = next.y
        d += ` C ${cpx1} ${cpy1}, ${cpx2} ${cpy2}, ${next.x} ${next.y}`
    }
    return d
}

const pathNet = computed(() => makeSmoothPath(pointsNet.value))
const pathGross = computed(() => makeSmoothPath(pointsGross.value))
const pathReturns = computed(() => makeSmoothPath(pointsReturns.value))

const areaNet = computed(() => {
    if (pointsNet.value.length === 0) return ''
    const firstX = pointsNet.value[0].x
    const lastX = pointsNet.value[pointsNet.value.length - 1].x
    const bottomY = padding.top + innerHeight.value
    return `${pathNet.value} L ${lastX} ${bottomY} L ${firstX} ${bottomY} Z`
})

const yTicks = computed(() => {
    const ticks = []
    const count = 4
    for (let i = 0; i <= count; i++) {
        const val = (maxVal.value / count) * i
        const y = padding.top + innerHeight.value - (val / maxVal.value) * innerHeight.value
        ticks.push({ val, y })
    }
    return ticks
})
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <!-- Header & Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <h3 class="text-xl font-bold tracking-tight text-slate-900">{{ t('dashboard.monthly_income_overview') }}</h3>
                    <span 
                        :class="[
                            'px-2.5 py-0.5 text-xs font-bold rounded-full border',
                            overview.mom_growth_percent >= 0 
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                : 'bg-rose-50 text-rose-700 border-rose-200'
                        ]">
                        {{ overview.mom_growth_percent >= 0 ? '↑ +' : '↓ ' }}{{ overview.mom_growth_percent }}% MoM
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ t('dashboard.income_trend_subtitle') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Dataset Selector -->
                <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-semibold border border-slate-200">
                    <button 
                        @click="activeDataset = 'net_income'"
                        :class="['px-3 py-1.5 rounded-lg transition-all', activeDataset === 'net_income' ? 'bg-white text-emerald-700 border border-slate-200 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900']">
                        {{ t('dashboard.net_income') }}
                    </button>
                    <button 
                        @click="activeDataset = 'gross_sales'"
                        :class="['px-3 py-1.5 rounded-lg transition-all', activeDataset === 'gross_sales' ? 'bg-white text-blue-700 border border-slate-200 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900']">
                        {{ t('dashboard.gross_sales') }}
                    </button>
                    <button 
                        @click="activeDataset = 'returns'"
                        :class="['px-3 py-1.5 rounded-lg transition-all', activeDataset === 'returns' ? 'bg-white text-rose-700 border border-slate-200 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900']">
                        {{ t('dashboard.returns') }}
                    </button>
                    <button 
                        @click="activeDataset = 'all'"
                        :class="['px-3 py-1.5 rounded-lg transition-all', activeDataset === 'all' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900']">
                        All
                    </button>
                </div>

                <!-- Timeframe Toggle -->
                <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-semibold border border-slate-200">
                    <button 
                        @click="timeframe = 6"
                        :class="['px-3 py-1.5 rounded-lg transition-all', timeframe === 6 ? 'bg-white text-slate-900 font-extrabold border border-slate-200 shadow-xs' : 'text-slate-600 hover:text-slate-900']">
                        6M
                    </button>
                    <button 
                        @click="timeframe = 12"
                        :class="['px-3 py-1.5 rounded-lg transition-all', timeframe === 12 ? 'bg-white text-slate-900 font-extrabold border border-slate-200 shadow-xs' : 'text-slate-600 hover:text-slate-900']">
                        12M
                    </button>
                </div>
            </div>
        </div>

        <!-- Metric Highlights Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
            <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                <p class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ t('dashboard.current_month_income') }}</p>
                <p class="text-xl font-extrabold text-emerald-600 mt-1">{{ formatCurrency(overview.current_month_net) }}</p>
            </div>
            <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                <p class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ t('dashboard.gross_revenue') }}</p>
                <p class="text-xl font-extrabold text-slate-900 mt-1">{{ formatCurrency(overview.current_month_gross) }}</p>
            </div>
            <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                <p class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ t('dashboard.returns_value') }}</p>
                <p class="text-xl font-extrabold text-rose-600 mt-1">{{ formatCurrency(overview.current_month_returns) }}</p>
            </div>
            <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                <p class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ t('dashboard.return_rate') }}</p>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <p class="text-xl font-extrabold text-amber-600">{{ overview.return_percent }}%</p>
                    <span class="text-[10px] font-semibold text-slate-400">loss ratio</span>
                </div>
            </div>
        </div>

        <!-- Interactive SVG Chart -->
        <div class="relative w-full overflow-hidden">
            <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-auto overflow-visible select-none">
                <defs>
                    <!-- Gradient for Net Income -->
                    <linearGradient id="netGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#059669" stop-opacity="0.25" />
                        <stop offset="100%" stop-color="#059669" stop-opacity="0.0" />
                    </linearGradient>
                </defs>

                <!-- Grid Y-Lines & Labels -->
                <g class="grid-lines">
                    <g v-for="(t, idx) in yTicks" :key="idx">
                        <line 
                            :x1="padding.left" 
                            :y1="t.y" 
                            :x2="chartWidth - padding.right" 
                            :y2="t.y" 
                            stroke="#e2e8f0" 
                            stroke-width="1" 
                            stroke-dasharray="4 4" 
                        />
                        <text 
                            :x="padding.left - 8" 
                            :y="t.y + 4" 
                            text-anchor="end" 
                            fill="#64748b" 
                            class="text-[11px] font-bold">
                            {{ t.val >= 1000 ? (t.val / 1000).toFixed(0) + 'k' : t.val.toFixed(0) }}
                        </text>
                    </g>
                </g>

                <!-- Area Gradient Fill -->
                <path 
                    v-if="activeDataset === 'net_income' || activeDataset === 'all'"
                    :d="areaNet" 
                    fill="url(#netGradient)" 
                />

                <!-- Line: Gross Sales -->
                <path 
                    v-if="activeDataset === 'gross_sales' || activeDataset === 'all'"
                    :d="pathGross" 
                    fill="none" 
                    stroke="#2563eb" 
                    stroke-width="2.5" 
                    stroke-linecap="round" 
                />

                <!-- Line: Returns -->
                <path 
                    v-if="activeDataset === 'returns' || activeDataset === 'all'"
                    :d="pathReturns" 
                    fill="none" 
                    stroke="#e11d48" 
                    stroke-width="2.5" 
                    stroke-linecap="round" 
                    stroke-dasharray="5 5"
                />

                <!-- Line: Net Income -->
                <path 
                    v-if="activeDataset === 'net_income' || activeDataset === 'all'"
                    :d="pathNet" 
                    fill="none" 
                    stroke="#059669" 
                    stroke-width="3" 
                    stroke-linecap="round" 
                />

                <!-- Data Points & Hover Targets -->
                <g v-for="(pt, idx) in pointsNet" :key="idx">
                    <!-- Vertical Hover Guide Bar -->
                    <rect 
                        :x="pt.x - 15" 
                        :y="padding.top" 
                        width="30" 
                        :height="innerHeight" 
                        fill="transparent" 
                        class="cursor-pointer"
                        @mouseenter="hoveredIndex = idx"
                        @mouseleave="hoveredIndex = null"
                    />

                    <!-- Guide Line on Hover -->
                    <line 
                        v-if="hoveredIndex === idx"
                        :x1="pt.x" 
                        :y1="padding.top" 
                        :x2="pt.x" 
                        :y2="padding.top + innerHeight" 
                        stroke="#4f46e5" 
                        stroke-width="1.5" 
                        stroke-dasharray="2 2"
                    />

                    <!-- Net Point Circle -->
                    <circle 
                        v-if="activeDataset === 'net_income' || activeDataset === 'all'"
                        :cx="pt.x" 
                        :cy="pt.y" 
                        :r="hoveredIndex === idx ? 6 : 4" 
                        fill="#059669" 
                        stroke="#ffffff" 
                        stroke-width="2" 
                        class="transition-all duration-150"
                    />

                    <!-- Gross Point Circle -->
                    <circle 
                        v-if="activeDataset === 'gross_sales' && pointsGross[idx]"
                        :cx="pointsGross[idx].x" 
                        :cy="pointsGross[idx].y" 
                        :r="hoveredIndex === idx ? 6 : 4" 
                        fill="#2563eb" 
                        stroke="#ffffff" 
                        stroke-width="2" 
                    />

                    <!-- Returns Point Circle -->
                    <circle 
                        v-if="activeDataset === 'returns' && pointsReturns[idx]"
                        :cx="pointsReturns[idx].x" 
                        :cy="pointsReturns[idx].y" 
                        :r="hoveredIndex === idx ? 6 : 4" 
                        fill="#e11d48" 
                        stroke="#ffffff" 
                        stroke-width="2" 
                    />

                    <!-- X-Axis Month Label -->
                    <text 
                        :x="pt.x" 
                        :y="padding.top + innerHeight + 20" 
                        text-anchor="middle" 
                        :fill="hoveredIndex === idx ? '#059669' : '#64748b'"
                        :class="['text-[11px] transition-colors', hoveredIndex === idx ? 'font-extrabold' : 'font-semibold']">
                        {{ pt.data.short_label }}
                    </text>
                </g>
            </svg>

            <!-- Floating Active Point Tooltip Card -->
            <div 
                v-if="hoveredIndex !== null && filteredTrends[hoveredIndex]"
                class="absolute z-20 top-2 right-4 bg-white text-slate-900 rounded-xl p-3.5 shadow-xl border border-slate-200 pointer-events-none text-xs space-y-1.5 min-w-[200px]">
                <p class="font-extrabold text-slate-900 border-b border-slate-100 pb-1.5 mb-1.5">
                    {{ filteredTrends[hoveredIndex].label }}
                </p>
                <div class="flex justify-between items-center text-emerald-700 font-extrabold">
                    <span>{{ t('dashboard.net_income') }}:</span>
                    <span>{{ formatCurrency(filteredTrends[hoveredIndex].net_income) }}</span>
                </div>
                <div class="flex justify-between items-center text-blue-700 font-bold">
                    <span>{{ t('dashboard.gross_sales') }}:</span>
                    <span>{{ formatCurrency(filteredTrends[hoveredIndex].gross_sales) }}</span>
                </div>
                <div class="flex justify-between items-center text-rose-700 font-bold">
                    <span>{{ t('dashboard.returns') }}:</span>
                    <span>{{ formatCurrency(filteredTrends[hoveredIndex].returns) }}</span>
                </div>
                <div class="flex justify-between items-center text-slate-500 text-[10px] pt-1.5 border-t border-slate-100">
                    <span>Invoices Issued:</span>
                    <span class="font-extrabold text-slate-900">{{ filteredTrends[hoveredIndex].invoice_count }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

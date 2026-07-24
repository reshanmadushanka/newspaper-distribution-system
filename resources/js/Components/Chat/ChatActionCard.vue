<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { useTranslation } from '@/Composables/useTranslation'
import { CalendarDays, Store, Loader2, CheckCircle2, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
    action: { type: Object, required: true },
    canGenerate: { type: Boolean, default: false },
    isSending: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
const { t } = useTranslation()

const type = computed(() => props.action?.type)
const payload = computed(() => props.action?.payload || {})

const progressPercent = computed(() => {
    const total = Number(payload.value.total || 0)
    const processed = Number(payload.value.processed || 0)
    if (total <= 0) return 0
    return Math.min(100, Math.round((processed / total) * 100))
})
</script>

<template>
    <div class="mt-2 rounded-xl border bg-card p-3 text-sm shadow-sm">
        <!-- Preview -->
        <div v-if="type === 'auto_generate_preview'" class="space-y-3">
            <div class="flex items-center gap-2 font-semibold text-foreground">
                <CalendarDays class="h-4 w-4 text-primary" />
                {{ t('chat.preview_title') }}
            </div>
            <div class="grid grid-cols-1 gap-2 text-muted-foreground sm:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wide">{{ t('chat.target_date') }}</p>
                    <p class="font-medium text-foreground">{{ payload.target_date || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide">{{ t('chat.last_week_date') }}</p>
                    <p class="font-medium text-foreground">{{ payload.last_week_date || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide">{{ t('chat.eligible_shops') }}</p>
                    <p class="font-medium text-foreground flex items-center gap-1">
                        <Store class="h-3.5 w-3.5" />
                        {{ payload.eligible_shops_count ?? 0 }}
                    </p>
                </div>
            </div>
            <div v-if="canGenerate && (payload.eligible_shops_count ?? 0) > 0" class="flex flex-wrap gap-2">
                <Button
                    size="sm"
                    :disabled="isSending"
                    @click="emit('confirm', payload.target_date)"
                >
                    {{ t('chat.confirm_generate') }}
                </Button>
                <Button size="sm" variant="outline" :disabled="isSending" @click="emit('cancel')">
                    {{ t('chat.cancel') }}
                </Button>
            </div>
            <p v-else-if="!canGenerate" class="text-xs text-destructive">
                {{ t('chat.no_generate_permission') }}
            </p>
            <p v-else class="text-xs text-muted-foreground">
                {{ t('chat.empty') }}
            </p>
        </div>

        <!-- Started -->
        <div v-else-if="type === 'auto_generate_started'" class="space-y-2">
            <div class="flex items-center gap-2 font-semibold">
                <Loader2 class="h-4 w-4 animate-spin text-primary" />
                {{ t('chat.started') }}
            </div>
            <p class="text-muted-foreground">
                {{ payload.message || t('chat.progress_title') }}
                <span v-if="payload.target_date"> ({{ payload.target_date }})</span>
            </p>
        </div>

        <!-- Progress -->
        <div v-else-if="type === 'auto_generate_progress'" class="space-y-3">
            <div class="flex items-center gap-2 font-semibold">
                <Loader2 class="h-4 w-4 animate-spin text-primary" />
                {{ t('chat.progress_title') }}
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                <div
                    class="h-full rounded-full bg-primary transition-all"
                    :style="{ width: progressPercent + '%' }"
                />
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground sm:grid-cols-4">
                <span>{{ t('chat.processed') }}: {{ payload.processed ?? 0 }}/{{ payload.total ?? 0 }}</span>
                <span>{{ t('chat.created') }}: {{ payload.created ?? 0 }}</span>
                <span>{{ t('chat.skipped') }}: {{ payload.skipped ?? 0 }}</span>
                <span>{{ t('chat.failed') }}: {{ payload.failed ?? 0 }}</span>
            </div>
        </div>

        <!-- Result -->
        <div v-else-if="type === 'auto_generate_result'" class="space-y-3">
            <div class="flex items-center gap-2 font-semibold text-foreground">
                <CheckCircle2 class="h-4 w-4 text-primary" />
                {{ t('chat.result_title') }}
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground sm:grid-cols-4">
                <span>{{ t('chat.processed') }}: {{ payload.processed ?? 0 }}/{{ payload.total ?? 0 }}</span>
                <span>{{ t('chat.created') }}: {{ payload.created ?? 0 }}</span>
                <span>{{ t('chat.skipped') }}: {{ payload.skipped ?? 0 }}</span>
                <span>{{ t('chat.failed') }}: {{ payload.failed ?? 0 }}</span>
            </div>
            <Link href="/admin/invoices" class="inline-flex">
                <Button size="sm" variant="outline">{{ t('chat.view_invoices') }}</Button>
            </Link>
        </div>

        <!-- Info / fallback -->
        <div v-else class="flex items-start gap-2 text-muted-foreground">
            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
            <p>{{ payload.message || JSON.stringify(payload) }}</p>
        </div>
    </div>
</template>

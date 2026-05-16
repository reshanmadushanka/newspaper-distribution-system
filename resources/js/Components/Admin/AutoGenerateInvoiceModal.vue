<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { CalendarDays, Loader2, CheckCircle2, AlertCircle, X, Sparkles } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Datepicker } from '@/Components/ui/datepicker'
import Swal from 'sweetalert2'

const emit = defineEmits(['close', 'completed'])

const isOpen = ref(true)
const selectedDate = ref('')
const isLoading = ref(false)
const isGenerating = ref(false)
const previewData = ref(null)
const progress = ref({
    status: 'not_started',
    total: 0,
    processed: 0,
    created: 0,
    skipped: 0,
    failed: 0,
    invoices: [],
})

let pollingInterval = null

const progressPercentage = computed(() => {
    if (progress.value.total === 0) return 0
    return Math.round((progress.value.processed / progress.value.total) * 100)
})

const isComplete = computed(() => {
    return progress.value.status === 'completed' ||
        (progress.value.processed > 0 && progress.value.processed >= progress.value.total)
})

const closeModal = () => {
    isOpen.value = false
    stopPolling()
    emit('close')
}

const previewInvoices = () => {
    if (!selectedDate.value) {
        Swal.fire('Warning', 'Please select a date', 'warning')
        return
    }

    isLoading.value = true

    router.post('/admin/invoices/auto-generate/preview', {
        date: selectedDate.value,
    }, {
        onSuccess: (page) => {
            isLoading.value = false
            previewData.value = page.props.previewData || page.props

            if (previewData.value.eligible_shops_count === 0) {
                Swal.fire(
                    'No Invoices to Generate',
                    `All shops already have invoices for ${selectedDate.value} or there are no invoices from last week (${previewData.value.last_week_date}) to copy from.`,
                    'info'
                )
            }
        },
        onError: (errors) => {
            isLoading.value = false
            Swal.fire('Error', Object.values(errors).join(', ') || 'Failed to preview invoice generation', 'error')
        },
    })
}

const startGeneration = async () => {
    if (!previewData.value || previewData.value.eligible_shops_count === 0) {
        return
    }

    const result = await Swal.fire({
        title: 'Generate Invoices?',
        html: `This will create invoices for <strong>${previewData.value.eligible_shops_count} shops</strong> based on last week's data (${previewData.value.last_week_date}).`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Generate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        confirmButtonColor: 'var(--color-primary)',
    })

    if (!result.isConfirmed) {
        return
    }

    isGenerating.value = true

    router.post('/admin/invoices/auto-generate', {
        date: selectedDate.value,
    }, {
        onSuccess: () => {
            startPolling()
        },
        onError: (errors) => {
            isGenerating.value = false
            Swal.fire('Error', Object.values(errors).join(', ') || 'Failed to start invoice generation', 'error')
        },
    })
}

const startPolling = () => {
    pollingInterval = setInterval(async () => {
        try {
            const response = await fetch('/admin/invoices/auto-generate/progress', {
                headers: {
                    'Accept': 'application/json',
                },
            })

            const data = await response.json()
            progress.value = data

            if (data.status === 'completed') {
                stopPolling()
                showCompletionSummary()
            }
        } catch (error) {
            console.error('Failed to fetch progress:', error)
        }
    }, 1000)
}

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval)
        pollingInterval = null
    }
}

const showCompletionSummary = () => {
    const p = progress.value

    let html = `
        <div class="text-left">
            <p class="mb-2"><strong>Total Shops:</strong> ${p.total}</p>
            <p class="mb-2 text-green-600"><strong>Created:</strong> ${p.created}</p>
            <p class="mb-2 text-yellow-600"><strong>Skipped:</strong> ${p.skipped}</p>
            ${p.failed > 0 ? `<p class="mb-2 text-red-600"><strong>Failed:</strong> ${p.failed}</p>` : ''}
        </div>
    `

    Swal.fire({
        title: 'Generation Complete!',
        html: html,
        icon: p.failed > 0 ? 'warning' : 'success',
        confirmButtonText: 'View Invoices',
        confirmButtonColor: 'var(--color-primary)',
    }).then(() => {
        emit('completed')
        closeModal()
        router.get('/admin/invoices', {
            date_from: selectedDate.value,
            date_to: selectedDate.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['invoices', 'filters'],
        })
    })
}

onUnmounted(() => {
    stopPolling()
})
</script>

<template>
    <Transition name="modal">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-foreground/40 backdrop-blur-sm" @click="closeModal" />

            <!-- Modal -->
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-2xl">
                <!-- Header -->
                <div class="border-b bg-card px-6 py-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Sparkles class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold tracking-tight text-foreground">Auto-Generate Invoices</h2>
                                <p class="text-sm text-muted-foreground">Create invoices based on last week's data</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="max-h-[60vh] overflow-y-auto px-6 py-6">
                    <!-- Date Selection -->
                    <div v-if="!isGenerating" class="space-y-4">
                        <div class="rounded-2xl border bg-secondary/20 p-4">
                            <label class="mb-2 block text-sm font-semibold text-foreground">
                                Select Invoice Date
                            </label>
                            <div class="relative">
                                <CalendarDays
                                    class="absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Datepicker v-model="selectedDate" :disabled="isLoading"
                                    placeholder="Choose date for invoice generation"
                                    class="h-10 w-full cursor-pointer rounded-lg border border-input bg-card pl-10 pr-4 text-sm outline-none transition-colors placeholder:text-muted-foreground focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50" />
                            </div>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Invoices will be generated based on the same day of last week
                            </p>
                        </div>

                        <Button @click="previewInvoices" :disabled="!selectedDate || isLoading"
                            class="h-11 w-full rounded-xl shadow-lg shadow-primary/20">
                            <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin mr-2" />
                            {{ isLoading ? 'Loading...' : 'Preview Eligible Shops' }}
                        </Button>

                        <!-- Preview Results -->
                        <div v-if="previewData" class="mt-4 rounded-2xl border border-primary/20 bg-primary/5 p-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <AlertCircle class="h-5 w-5" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="mb-1 font-semibold text-foreground">Preview Results</h4>
                                    <p class="mb-2 text-sm text-muted-foreground">
                                        <strong>{{ previewData.eligible_shops_count }}</strong> shops are eligible for
                                        invoice generation
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Target Date: <strong>{{ previewData.target_date }}</strong> |
                                        Based on: <strong>{{ previewData.last_week_date }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <Button v-if="previewData && previewData.eligible_shops_count > 0" @click="startGeneration"
                            class="h-12 w-full rounded-xl font-semibold shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                            <Sparkles class="h-5 w-5 mr-2" />
                            Generate {{ previewData.eligible_shops_count }} Invoices
                        </Button>
                    </div>

                    <!-- Progress Display -->
                    <div v-else class="space-y-6">
                        <!-- Status Badge -->
                        <div class="text-center">
                            <div v-if="!isComplete"
                                class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-primary">
                                <Loader2 class="h-4 w-4 animate-spin" />
                                <span class="font-semibold">Processing...</span>
                            </div>
                            <div v-else
                                class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-4 py-2 text-emerald-700">
                                <CheckCircle2 class="h-5 w-5" />
                                <span class="font-semibold">Complete!</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium text-foreground">Progress</span>
                                <span class="font-bold text-primary">{{ progressPercentage }}%</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-secondary">
                                <div class="h-full rounded-full bg-primary transition-all duration-500 ease-out"
                                    :style="{ width: progressPercentage + '%' }" />
                            </div>
                            <p class="mt-2 text-center text-xs text-muted-foreground">
                                {{ progress.processed }} of {{ progress.total }} shops processed
                            </p>
                        </div>

                        <!-- Statistics -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4 text-center">
                                <CheckCircle2 class="mx-auto mb-1 h-6 w-6 text-emerald-600" />
                                <div class="text-2xl font-bold text-emerald-700">{{ progress.created }}</div>
                                <div class="text-xs font-medium text-muted-foreground">Created</div>
                            </div>
                            <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-center">
                                <AlertCircle class="mx-auto mb-1 h-6 w-6 text-amber-600" />
                                <div class="text-2xl font-bold text-amber-700">{{ progress.skipped }}</div>
                                <div class="text-xs font-medium text-muted-foreground">Skipped</div>
                            </div>
                            <div class="rounded-2xl border border-destructive/20 bg-destructive/5 p-4 text-center">
                                <X class="mx-auto mb-1 h-6 w-6 text-destructive" />
                                <div class="text-2xl font-bold text-destructive">{{ progress.failed }}</div>
                                <div class="text-xs font-medium text-muted-foreground">Failed</div>
                            </div>
                        </div>

                        <!-- Info Message -->
                        <div class="rounded-2xl border bg-secondary/20 p-4">
                            <p class="text-center text-sm text-foreground">
                                <strong>Please wait...</strong> Invoices are being generated in the background.
                                <br />
                                <span class="text-xs text-muted-foreground">You can safely close this dialog and the process
                                    will continue.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
    opacity: 0;
}
</style>

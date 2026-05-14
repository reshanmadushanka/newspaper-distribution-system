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
        confirmButtonColor: '#3b82f6',
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
        confirmButtonColor: '#3b82f6',
    }).then(() => {
        emit('completed')
        closeModal()
        router.reload({ only: ['invoices'] })
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
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />

            <!-- Modal -->
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-5 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <Sparkles class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold">Auto-Generate Invoices</h2>
                                <p class="text-sm text-blue-100">Create invoices based on last week's data</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                    <!-- Date Selection -->
                    <div v-if="!isGenerating" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Select Invoice Date
                            </label>
                            <div class="relative">
                                <CalendarDays
                                    class="absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Datepicker v-model="selectedDate" :disabled="isLoading"
                                    placeholder="Choose date for invoice generation"
                                    class="w-full h-11 pl-10 pr-4 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                Invoices will be generated based on the same day of last week
                            </p>
                        </div>

                        <Button @click="previewInvoices" :disabled="!selectedDate || isLoading"
                            class="w-full h-11 rounded-xl">
                            <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin mr-2" />
                            {{ isLoading ? 'Loading...' : 'Preview Eligible Shops' }}
                        </Button>

                        <!-- Preview Results -->
                        <div v-if="previewData" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                            <div class="flex items-start gap-3">
                                <AlertCircle class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" />
                                <div class="flex-1">
                                    <h4 class="font-semibold text-blue-900 mb-1">Preview Results</h4>
                                    <p class="text-sm text-blue-800 mb-2">
                                        <strong>{{ previewData.eligible_shops_count }}</strong> shops are eligible for
                                        invoice generation
                                    </p>
                                    <p class="text-xs text-blue-700">
                                        Target Date: <strong>{{ previewData.target_date }}</strong> |
                                        Based on: <strong>{{ previewData.last_week_date }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <Button v-if="previewData && previewData.eligible_shops_count > 0" @click="startGeneration"
                            class="w-full h-12 rounded-xl bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold shadow-lg shadow-green-500/30">
                            <Sparkles class="h-5 w-5 mr-2" />
                            Generate {{ previewData.eligible_shops_count }} Invoices
                        </Button>
                    </div>

                    <!-- Progress Display -->
                    <div v-else class="space-y-6">
                        <!-- Status Badge -->
                        <div class="text-center">
                            <div v-if="!isComplete"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-full">
                                <Loader2 class="h-4 w-4 animate-spin" />
                                <span class="font-semibold">Processing...</span>
                            </div>
                            <div v-else
                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-full">
                                <CheckCircle2 class="h-5 w-5" />
                                <span class="font-semibold">Complete!</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium text-gray-700">Progress</span>
                                <span class="font-bold text-blue-600">{{ progressPercentage }}%</span>
                            </div>
                            <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500 ease-out rounded-full"
                                    :style="{ width: progressPercentage + '%' }" />
                            </div>
                            <p class="mt-2 text-xs text-gray-600 text-center">
                                {{ progress.processed }} of {{ progress.total }} shops processed
                            </p>
                        </div>

                        <!-- Statistics -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-center">
                                <CheckCircle2 class="h-6 w-6 text-green-600 mx-auto mb-1" />
                                <div class="text-2xl font-bold text-green-700">{{ progress.created }}</div>
                                <div class="text-xs text-green-600 font-medium">Created</div>
                            </div>
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-center">
                                <AlertCircle class="h-6 w-6 text-yellow-600 mx-auto mb-1" />
                                <div class="text-2xl font-bold text-yellow-700">{{ progress.skipped }}</div>
                                <div class="text-xs text-yellow-600 font-medium">Skipped</div>
                            </div>
                            <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-center">
                                <X class="h-6 w-6 text-red-600 mx-auto mb-1" />
                                <div class="text-2xl font-bold text-red-700">{{ progress.failed }}</div>
                                <div class="text-xs text-red-600 font-medium">Failed</div>
                            </div>
                        </div>

                        <!-- Info Message -->
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                            <p class="text-sm text-gray-700 text-center">
                                <strong>Please wait...</strong> Invoices are being generated in the background.
                                <br />
                                <span class="text-xs text-gray-500">You can safely close this dialog and the process
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

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Check, CreditCard } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ref } from 'vue'
import Swal from 'sweetalert2'

const componentProps = defineProps({
    invoice: Object,
})

const showPaymentForm = ref(false)
const paymentForm = ref({
    payment_method: 'bank_transfer',
    payment_notes: '',
})
const loading = ref(false)

const handleMarkPaid = async () => {
    if (!paymentForm.value.payment_method) {
        Swal.fire('Error', 'Please select a payment method', 'error')
        return
    }

    loading.value = true

    router.patch(`/admin/payments/invoice/${componentProps.invoice.id}`, paymentForm.value, {
        onSuccess: () => {
            loading.value = false
            Swal.fire({
                title: 'Payment Recorded!',
                html: `<p class="text-lg mb-2">Thank you for your payment!</p>
                       <p class="text-xl font-bold text-green-600 mb-4">Rs. ${Number(componentProps.invoice.amount).toFixed(2)} for ${componentProps.invoice.reason}</p>
                       <p class="text-gray-600">Your payment has been successfully recorded.</p>`,
                icon: 'success',
                confirmButtonText: 'Continue',
            }).then(() => {
                router.visit('/admin/payments/history')
            })
        },
        onError: () => {
            loading.value = false
            Swal.fire('Error', 'Failed to record payment', 'error')
        },
    })
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Invoice #${invoice.id}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link href="/admin/payments/pending">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft :size="20" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Invoice #{{ invoice.id }}</h1>
                    <p class="text-sm text-gray-600 mt-1">Review and pay this invoice</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Invoice Card -->
                    <div class="bg-white rounded-lg shadow-lg p-8">
                        <div class="flex items-start justify-between mb-8 pb-6 border-b">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Invoice Date</p>
                                <p class="text-lg font-semibold">{{ new Date(invoice.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                            </div>
                            <Badge :variant="invoice.status === 'pending' ? 'secondary' : 'success'" class="text-base px-4 py-1 capitalize">
                                {{ invoice.status }}
                            </Badge>
                        </div>

                        <!-- Invoice Details -->
                        <div class="space-y-8">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Description</p>
                                <p class="text-2xl font-bold text-gray-900">{{ invoice.reason }}</p>
                            </div>

                            <div v-if="invoice.description" class="bg-gray-50 rounded p-4">
                                <p class="text-sm text-gray-600 mb-2">Details</p>
                                <p class="text-gray-900">{{ invoice.description }}</p>
                            </div>

                            <!-- Amount Box -->
                            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg p-6 border-2 border-yellow-200">
                                <p class="text-sm text-yellow-700 mb-2 font-medium">AMOUNT DUE</p>
                                <p class="text-4xl font-bold text-yellow-900">Rs. {{ Number(invoice.amount).toFixed(2) }}</p>
                            </div>

                            <!-- Created By -->
                            <!-- <div class="bg-gray-50 rounded p-4">
                                <p class="text-sm text-gray-600 mb-1">Created By</p>
                                <p class="font-medium text-gray-900">{{ invoice.creator.name }}</p>
                                <p class="text-sm text-gray-600">{{ invoice.creator.email }}</p>
                            </div> -->
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <div v-if="invoice.bank_account_details" class="bg-blue-50 rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <h3 class="text-lg font-semibold text-blue-900 mb-4 flex items-center gap-2">
                            <CreditCard :size="20" />
                            Bank Account Details
                        </h3>
                        <div class="bg-white rounded p-4 font-mono text-sm text-gray-700 whitespace-pre-wrap break-words">
                            {{ invoice.bank_account_details }}
                        </div>
                    </div>

                    <!-- Payment Success Message -->
                    <div v-if="invoice.status === 'paid'" class="bg-green-50 rounded-lg shadow p-6 border-l-4 border-green-500">
                        <h3 class="text-lg font-semibold text-green-900 mb-4 flex items-center gap-2">
                            <Check :size="20" />
                            Payment Received
                        </h3>
                        <p class="text-green-800 mb-4">
                            Thank you for your payment of <span class="font-bold">Rs. {{ Number(invoice.amount).toFixed(2) }}</span> for <span class="font-bold">{{ invoice.reason }}</span>
                        </p>
                        <div class="text-sm text-green-700 space-y-2">
                            <p><span class="font-medium">Payment Method:</span> {{ invoice.payment_method || 'N/A' }}</p>
                            <p v-if="invoice.paid_at"><span class="font-medium">Paid on:</span> {{ new Date(invoice.paid_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                            <p v-if="invoice.payment_notes"><span class="font-medium">Notes:</span> {{ invoice.payment_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-lg p-6 sticky top-6">
                        <h3 class="text-xl font-bold mb-6">Invoice Summary</h3>
                        
                        <div class="space-y-4 mb-6 pb-6 border-b">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Amount:</span>
                                <span class="text-xl font-bold">Rs. {{ Number(invoice.amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-600">
                                <span>Status:</span>
                                <Badge :variant="invoice.status === 'pending' ? 'secondary' : 'success'" class="capitalize">
                                    {{ invoice.status }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div v-if="invoice.status === 'pending'" class="space-y-4">
                            <Button @click="showPaymentForm = !showPaymentForm" class="w-full gap-2 bg-green-600 hover:bg-green-700" size="lg">
                                <Check :size="20" />
                                {{ showPaymentForm ? 'Hide Form' : 'Record Payment' }}
                            </Button>

                            <div v-if="showPaymentForm" class="space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                    <select
                                        v-model="paymentForm.payment_method"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                                    >
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="check">Check</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                                    <textarea
                                        v-model="paymentForm.payment_notes"
                                        placeholder="Add any payment reference or notes..."
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                    />
                                </div>

                                <Button @click="handleMarkPaid" :disabled="loading" class="w-full bg-green-600 hover:bg-green-700">
                                    {{ loading ? 'Recording...' : 'Confirm Payment' }}
                                </Button>
                            </div>
                        </div>

                        <div v-else class="text-center">
                            <p class="text-sm text-green-700 font-medium mb-2">✓ Payment Completed</p>
                            <Link href="/admin/payments/history">
                                <Button variant="outline" class="w-full">View Payment History</Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

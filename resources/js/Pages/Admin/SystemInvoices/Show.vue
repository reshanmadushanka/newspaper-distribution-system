<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Pencil, Trash2, Check, X } from 'lucide-vue-next'
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
    payment_method: '',
    payment_notes: '',
})

const handleMarkPaid = async () => {
    Swal.fire({
        title: 'Mark as Paid?',
        text: 'Are you sure you want to mark this invoice as paid?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'Yes, mark as paid',
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/admin/system-invoices/${componentProps.invoice.id}/mark-as-paid`, paymentForm.value, {
                onSuccess: () => {
                    Swal.fire('Success!', 'Invoice marked as paid.', 'success')
                    showPaymentForm.value = false
                },
            })
        }
    })
}

const handleDelete = async () => {
    Swal.fire({
        title: 'Delete Invoice?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/system-invoices/${componentProps.invoice.id}`, {
                onSuccess: () => {
                    router.visit('/admin/system-invoices')
                },
            })
        }
    })
}

const statusVariant = (status) => {
    return status === 'paid' ? 'success' : 'secondary'
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Invoice #${invoice.id}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/system-invoices">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft :size="20" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Invoice #{{ invoice.id }}</h1>
                        <p class="text-sm text-gray-600 mt-1">System Invoice Details</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/admin/system-invoices/${invoice.id}/edit`">
                        <Button variant="outline" class="gap-2">
                            <Pencil :size="18" />
                            Edit
                        </Button>
                    </Link>
                    <Button @click="handleDelete" variant="destructive" class="gap-2">
                        <Trash2 :size="18" />
                        Delete
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main Invoice Details -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold">Status</h2>
                            <Badge :variant="statusVariant(invoice.status)" class="capitalize text-base px-3 py-1">
                                {{ invoice.status }}
                            </Badge>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-600">
                                Created: <span class="font-medium text-gray-900">{{ new Date(invoice.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
                            </p>
                            <p v-if="invoice.paid_at" class="text-gray-600">
                                Paid: <span class="font-medium text-gray-900">{{ new Date(invoice.paid_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Invoice Details</h2>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-600">Admin</p>
                                <p class="font-medium text-gray-900">{{ invoice.admin.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Reason</p>
                                <p class="font-medium text-gray-900">{{ invoice.reason }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Amount</p>
                                <p class="text-2xl font-bold text-gray-900">Rs. {{ Number(invoice.amount).toFixed(2) }}</p>
                            </div>
                            <div v-if="invoice.description">
                                <p class="text-sm text-gray-600">Description</p>
                                <p class="text-gray-900">{{ invoice.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Account Details -->
                    <div v-if="invoice.bank_account_details" class="bg-blue-50 rounded-lg shadow p-6 border border-blue-200">
                        <h2 class="text-lg font-semibold mb-4 text-blue-900">Bank Account Details</h2>
                        <div class="bg-white rounded p-3 text-sm text-gray-700 whitespace-pre-wrap font-mono">
                            {{ invoice.bank_account_details }}
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div v-if="invoice.status === 'paid'" class="bg-green-50 rounded-lg shadow p-6 border border-green-200">
                        <h2 class="text-lg font-semibold mb-4 text-green-900">Payment Information</h2>
                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="text-green-700">Payment Method</p>
                                <p class="font-medium text-gray-900">{{ invoice.payment_method || 'Not specified' }}</p>
                            </div>
                            <div v-if="invoice.payment_notes">
                                <p class="text-green-700">Payment Notes</p>
                                <p class="text-gray-900">{{ invoice.payment_notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Created By -->
                    <!-- <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Created By</h3>
                        <p class="text-sm text-gray-600">{{ invoice.creator.name }}</p>
                        <p class="text-sm text-gray-600">{{ invoice.creator.email }}</p>
                    </div> -->

                    <!-- Action Buttons -->
                    <div v-if="invoice.status === 'pending'" class="space-y-3">
                        <Button @click="showPaymentForm = !showPaymentForm" class="w-full gap-2 bg-green-600 hover:bg-green-700">
                            <Check :size="18" />
                            Mark as Paid
                        </Button>

                        <div v-if="showPaymentForm" class="bg-white rounded-lg shadow p-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                <input
                                    v-model="paymentForm.payment_method"
                                    type="text"
                                    placeholder="e.g., Bank Transfer"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                                <textarea
                                    v-model="paymentForm.payment_notes"
                                    placeholder="Add any payment notes..."
                                    rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                />
                            </div>
                            <Button @click="handleMarkPaid" class="w-full bg-green-600 hover:bg-green-700">
                                Confirm Payment
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

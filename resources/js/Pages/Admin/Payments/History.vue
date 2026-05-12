<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Check, AlertCircle, FileText } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'

const componentProps = defineProps({
    invoices: Object,
    stats: Object,
})
</script>

<template>
    <AdminLayout>
        <Head title="Payment History" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">My Payments</h1>
                    <p class="text-sm text-gray-600 mt-1">Payment history and receipts</p>
                </div>
                <Link href="/admin/payments/pending">
                    <Button variant="outline" class="gap-2">
                        <AlertCircle :size="18" />
                        View Pending Payments
                    </Button>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-6 border border-green-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-green-700 mb-2">Total Paid</p>
                            <p class="text-3xl font-bold text-green-900">{{ stats.paid_count }}</p>
                        </div>
                        <div class="bg-green-200 rounded-full p-3">
                            <Check :size="24" class="text-green-700" />
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-6 border border-green-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-green-700 mb-2">Total Amount Paid</p>
                            <p class="text-3xl font-bold text-green-900">Rs. {{ Number(stats.paid_amount).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-6 border border-yellow-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-yellow-700 mb-2">Pending</p>
                            <p class="text-3xl font-bold text-yellow-900">{{ stats.pending_count }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paid Invoices List -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Paid Invoices</h2>

                <div v-if="invoices.data.length > 0" class="space-y-3">
                    <div v-for="invoice in invoices.data" :key="invoice.id" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-5 border-l-4 border-green-500">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ invoice.reason }}</h3>
                                    <Badge variant="success">Paid</Badge>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                    <p>Created: {{ new Date(invoice.created_at).toLocaleDateString() }}</p>
                                    <p>Paid: {{ new Date(invoice.paid_at).toLocaleDateString() }}</p>
                                    <p>Method: <span class="font-medium capitalize">{{ invoice.payment_method || 'Not specified' }}</span></p>
                                    <p>Created by: <span class="font-medium">{{ invoice.creator.name }}</span></p>
                                </div>
                                <p v-if="invoice.payment_notes" class="text-sm text-gray-600 mt-2">
                                    Notes: <span class="text-gray-900">{{ invoice.payment_notes }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-green-600">Rs. {{ Number(invoice.amount).toFixed(2) }}</p>
                                <p class="text-xs text-green-600 mt-1">✓ Completed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="flex justify-center mb-4">
                        <FileText :size="48" class="text-gray-300" />
                    </div>
                    <p class="text-gray-500 text-lg font-medium mb-2">No Payment History</p>
                    <p class="text-gray-400 mb-4">You haven't completed any payments yet.</p>
                    <Link href="/admin/payments/pending">
                        <Button>View Pending Invoices</Button>
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="invoices.links.length > 1" class="flex items-center justify-between mt-6">
                <p class="text-sm text-gray-600">
                    Showing {{ invoices.from }} to {{ invoices.to }} of {{ invoices.total }}
                </p>
                <div class="flex gap-2">
                    <Link v-for="link in invoices.links" :key="link.label" :href="link.url || '#'" :only="['invoices']">
                        <Button
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                            size="sm"
                            v-html="link.label"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

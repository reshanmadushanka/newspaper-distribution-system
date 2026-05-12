<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { AlertCircle, ChevronRight, Home } from 'lucide-vue-next'
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
        <Head title="Pending Payments" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Pending Payments</h1>
                    <p class="text-sm text-gray-600 mt-1">You have {{ stats.pending_count }} pending invoices</p>
                </div>
                <Link href="/admin/payments/history">
                    <Button variant="outline" class="gap-2">
                        View Payment History
                    </Button>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-6 border border-yellow-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-yellow-700 mb-2">Pending Invoices</p>
                            <p class="text-3xl font-bold text-yellow-900">{{ stats.pending_count }}</p>
                        </div>
                        <div class="bg-yellow-200 rounded-full p-3">
                            <AlertCircle :size="24" class="text-yellow-700" />
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-6 border border-yellow-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-yellow-700 mb-2">Total Amount Due</p>
                            <p class="text-3xl font-bold text-yellow-900">Rs. {{ Number(stats.pending_amount).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Box -->
            <div v-if="stats.pending_count > 0" class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded">
                <p class="text-blue-900 font-medium mb-2">
                    ⚠️ You have {{ stats.pending_count }} payment{{ stats.pending_count !== 1 ? 's' : '' }} pending!
                </p>
                <p class="text-blue-700 text-sm mb-4">
                    Please review and complete your pending payments as soon as possible.
                </p>
            </div>

            <!-- Invoices List -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Your Pending Invoices</h2>
                
                <div v-if="invoices.data.length > 0" class="space-y-3">
                    <Link v-for="invoice in invoices.data" :key="invoice.id" :href="`/admin/payments/invoice/${invoice.id}`">
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-all hover:scale-[1.01] cursor-pointer p-5 border-l-4 border-yellow-500">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ invoice.reason }}</h3>
                                        <Badge variant="secondary">Pending</Badge>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-3">
                                        Created by <span class="font-medium">{{ invoice.creator_name }}</span> on {{ new Date(invoice.created_at).toLocaleDateString() }}
                                    </p>
                                    <p class="text-xs text-gray-500">Invoice date: {{ new Date(invoice.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                                </div>
                                <div class="text-right flex items-center gap-4">
                                    <div>
                                        <p class="text-2xl font-bold text-yellow-600">Rs. {{ Number(invoice.amount).toFixed(2) }}</p>
                                    </div>
                                    <ChevronRight :size="24" class="text-gray-400" />
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="flex justify-center mb-4">
                        <Home :size="48" class="text-gray-300" />
                    </div>
                    <p class="text-gray-500 text-lg font-medium mb-2">No Pending Payments</p>
                    <p class="text-gray-400">You don't have any pending invoices. Great job!</p>
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

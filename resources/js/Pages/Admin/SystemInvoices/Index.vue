<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { Plus, FileText, Eye, Pencil, Trash2, AlertCircle, Check } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { computed } from 'vue'
import Swal from 'sweetalert2'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm.js'

const componentProps = defineProps({
    invoices: Object,
})

const { props } = usePage()
const permissions = computed(() => props.auth.user?.permissions ?? [])

const canCreate = computed(() => permissions.value.includes('manage system invoices'))
const canUpdate = computed(() => permissions.value.includes('manage system invoices'))

const { confirmDelete } = useDeleteConfirm('This will permanently delete the invoice.')

const handleDelete = (id) => {
    confirmDelete(() => router.delete(`/admin/system-invoices/${id}`, {
        onError: (errors) => Swal.fire('Error!', Object.values(errors)[0], 'error'),
    }))
}

const statusVariant = (status) => {
    const map = {
        pending: 'secondary',
        paid: 'success',
    }
    return map[status] || 'secondary'
}

const statusIcon = (status) => {
    return status === 'paid' ? Check : AlertCircle
}

const markAsPaid = (id) => {
    Swal.fire({
        title: 'Mark as Paid?',
        text: 'Mark this invoice as paid?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'Yes, mark as paid',
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/admin/system-invoices/${id}/mark-as-paid`, {}, {
                onSuccess: () => Swal.fire('Success!', 'Invoice marked as paid.', 'success'),
                onError: () => Swal.fire('Error!', 'Failed to update invoice.', 'error'),
            })
        }
    })
}
</script>

<template>
    <AdminLayout>
        <Head title="System Invoices" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">System Invoices</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage invoices created for system admins</p>
                </div>
                <Link v-if="canCreate" :href="`/admin/system-invoices/create`">
                    <Button class="gap-2">
                        <Plus :size="18" />
                        Create Invoice
                    </Button>
                </Link>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div v-if="invoices.data.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ invoice.admin.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ invoice.reason }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    Rs. {{ Number(invoice.amount).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge :variant="statusVariant(invoice.status)" class="capitalize">
                                        {{ invoice.status }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ new Date(invoice.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/admin/system-invoices/${invoice.id}`">
                                            <Button variant="ghost" size="sm" class="gap-1">
                                                <Eye :size="16" />
                                            </Button>
                                        </Link>
                                        <Link v-if="canUpdate" :href="`/admin/system-invoices/${invoice.id}/edit`">
                                            <Button variant="ghost" size="sm" class="gap-1">
                                                <Pencil :size="16" />
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="canUpdate && invoice.status === 'pending'"
                                            @click="markAsPaid(invoice.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="gap-1 text-green-600 hover:text-green-700"
                                        >
                                            <Check :size="16" />
                                        </Button>
                                        <Button
                                            v-if="canUpdate"
                                            @click="handleDelete(invoice.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="gap-1 text-red-600 hover:text-red-700"
                                        >
                                            <Trash2 :size="16" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="px-6 py-12 text-center">
                    <FileText :size="48" class="mx-auto text-gray-300 mb-4" />
                    <p class="text-gray-500">No invoices created yet</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="invoices.links.length > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Showing {{ invoices.from }} to {{ invoices.to }} of {{ invoices.total }} invoices
                </p>
                <div class="flex gap-2">
                    <Link v-for="link in invoices.links" :key="link.label" :href="link.url || '#'" :only="['invoices']">
                        <Button
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                            v-html="link.label"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Save } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { ref } from 'vue'
import Swal from 'sweetalert2'

const componentProps = defineProps({
    invoice: Object,
    admins: Array,
})

const form = ref({
    admin_id: componentProps.invoice.admin_id,
    reason: componentProps.invoice.reason,
    amount: componentProps.invoice.amount,
    description: componentProps.invoice.description || '',
    bank_account_details: componentProps.invoice.bank_account_details || '',
})

const errors = ref({})
const loading = ref(false)

const handleSubmit = async () => {
    errors.value = {}
    loading.value = true

    router.put(`/admin/system-invoices/${componentProps.invoice.id}`, form.value, {
        onSuccess: () => {
            Swal.fire({
                title: 'Success!',
                text: 'Invoice updated successfully.',
                icon: 'success',
                confirmButtonText: 'OK',
            }).then(() => {
                router.visit(`/admin/system-invoices/${componentProps.invoice.id}`)
            })
        },
        onError: (errorsResponse) => {
            errors.value = errorsResponse
            Swal.fire('Validation Error', 'Please check all required fields.', 'error')
            loading.value = false
        },
    })
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit Invoice #${invoice.id}`" />

        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <Link :href="`/admin/system-invoices/${invoice.id}`">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft :size="20" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Edit Invoice</h1>
                    <p class="text-sm text-gray-600 mt-1">Update invoice details</p>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Admin Selection -->
                    <div class="space-y-2">
                        <label for="admin_id" class="block text-sm font-medium text-gray-700">
                            Admin <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="admin_id"
                            v-model="form.admin_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Choose an admin...</option>
                            <option v-for="admin in admins" :key="admin.id" :value="admin.id">
                                {{ admin.name }} ({{ admin.email }})
                            </option>
                        </select>
                        <p v-if="errors.admin_id" class="text-sm text-red-600">{{ errors.admin_id[0] }}</p>
                    </div>

                    <!-- Reason -->
                    <div class="space-y-2">
                        <label for="reason" class="block text-sm font-medium text-gray-700">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <Input
                            id="reason"
                            v-model="form.reason"
                            type="text"
                            placeholder="e.g., Monthly Subscription, System Maintenance"
                            class="w-full"
                        />
                        <p v-if="errors.reason" class="text-sm text-red-600">{{ errors.reason[0] }}</p>
                    </div>

                    <!-- Amount -->
                    <div class="space-y-2">
                        <label for="amount" class="block text-sm font-medium text-gray-700">
                            Amount (Rs.) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-600">Rs.</span>
                            <Input
                                id="amount"
                                v-model="form.amount"
                                type="text"
                                inputmode="decimal"
                                placeholder="0.00"
                                class="w-full pl-8"
                            />
                        </div>
                        <p v-if="errors.amount" class="text-sm text-red-600">{{ errors.amount[0] }}</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            Description
                        </label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Add any additional details..."
                            rows="3"
                            class="w-full"
                        />
                        <p v-if="errors.description" class="text-sm text-red-600">{{ errors.description[0] }}</p>
                    </div>

                    <!-- Bank Account Details -->
                    <div class="space-y-2">
                        <label for="bank_account_details" class="block text-sm font-medium text-gray-700">
                            Bank Account Details
                        </label>
                        <Textarea
                            id="bank_account_details"
                            v-model="form.bank_account_details"
                            placeholder="Bank name, Account number, IBAN, etc."
                            rows="3"
                            class="w-full"
                        />
                        <p v-if="errors.bank_account_details" class="text-sm text-red-600">{{ errors.bank_account_details[0] }}</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex gap-3 pt-4 border-t">
                        <Button type="submit" :disabled="loading" class="gap-2">
                            <Save :size="18" />
                            {{ loading ? 'Saving...' : 'Save Changes' }}
                        </Button>
                        <Link :href="`/admin/system-invoices/${invoice.id}`">
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

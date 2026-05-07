<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ChevronLeft, Plus, Pencil, Trash2, Search, DollarSign, Calendar, Save } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm'
import Swal from 'sweetalert2'
import { ref } from 'vue'

const props = defineProps({
    newspaper: Object,
    prices: Object,
})

const showAddForm = ref(false)
const editingPriceId = ref(null)

const addForm = useForm({
    effective_from: '',
    effective_to: '',
    price: '',
    cost_price: '',
})

const editForm = useForm({
    effective_from: '',
    effective_to: '',
    price: '',
    cost_price: '',
})

const { deleting, confirmDelete } = useDeleteConfirm('This action cannot be undone. This will permanently delete this price record.')

const handleDelete = (priceId) => {
    confirmDelete(() => 
        router.delete(`/admin/newspaper-prices/${priceId}`, {
            onError: (errors) => {
                Swal.fire('Error!', Object.values(errors)[0] || 'Failed to delete price.', 'error')
            }
        })
    )
}

const submitAdd = () => {
    addForm.post(`/admin/newspapers/${props.newspaper.id}/prices`, {
        onSuccess: () => {
            showAddForm.value = false
            addForm.reset()
        }
    })
}

const startEdit = (price) => {
    editingPriceId.value = price.id
    editForm.effective_from = price.effective_from
    editForm.effective_to = price.effective_to ?? ''
    editForm.price = price.price
    editForm.cost_price = price.cost_price ?? ''
}

const cancelEdit = () => {
    editingPriceId.value = null
    editForm.reset()
}

const submitEdit = (priceId) => {
    editForm.put(`/admin/newspaper-prices/${priceId}`, {
        onSuccess: () => {
            editingPriceId.value = null
        }
    })
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
    <Head :title="`Prices - ${newspaper.name}`" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/admin/newspapers">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-2 text-primary">
                        <DollarSign class="h-6 w-6" />
                        <h2 class="text-2xl font-bold tracking-tight">Price History</h2>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ newspaper.name }} - Track all price changes over time.</p>
                </div>
            </div>
            <Button @click="showAddForm = !showAddForm" class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                <Plus class="mr-2 h-4 w-4" />
                {{ showAddForm ? 'Cancel' : 'Add Price' }}
            </Button>
        </div>

        <div v-if="showAddForm" class="rounded-2xl border bg-card shadow-sm overflow-hidden mb-6">
            <div class="p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-primary" />
                    Add New Price
                </h3>
                <form @submit.prevent="submitAdd" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-2">
                        <Label for="add-effective_from">Effective From</Label>
                        <Input id="add-effective_from" v-model="addForm.effective_from" type="date" :error="addForm.errors.effective_from" />
                        <p v-if="addForm.errors.effective_from" class="text-xs text-destructive">{{ addForm.errors.effective_from }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="add-price">Selling Price</Label>
                        <Input id="add-price" v-model="addForm.price" type="number" step="0.01" placeholder="0.00" :error="addForm.errors.price" />
                        <p v-if="addForm.errors.price" class="text-xs text-destructive">{{ addForm.errors.price }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="add-cost_price">Cost Price</Label>
                        <Input id="add-cost_price" v-model="addForm.cost_price" type="number" step="0.01" placeholder="0.00" />
                    </div>
                    <div class="flex items-end">
                        <Button type="submit" class="w-full" :disabled="addForm.processing">
                            <Save class="mr-2 h-4 w-4" />
                            Save Price
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Effective From</th>
                            <th class="px-6 py-4">Effective To</th>
                            <th class="px-6 py-4">Selling Price</th>
                            <th class="px-6 py-4">Cost Price</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="price in prices" :key="price.id" class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="text-xs font-medium">{{ formatDate(price.effective_from) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs" :class="price.effective_to ? 'text-muted-foreground' : 'text-green-600 font-semibold'">
                                    {{ price.effective_to ? formatDate(price.effective_to) : 'Current' }}
                                </div>
                            </td>

                            <td v-if="editingPriceId === price.id" class="px-6 py-4">
                                <Input v-model="editForm.price" type="number" step="0.01" class="w-24" :error="editForm.errors.price" />
                            </td>
                            <td v-else class="px-6 py-4">
                                <div class="text-xs font-semibold text-green-600">${{ price.price }}</div>
                            </td>

                            <td v-if="editingPriceId === price.id" class="px-6 py-4">
                                <Input v-model="editForm.cost_price" type="number" step="0.01" class="w-24" />
                            </td>
                            <td v-else class="px-6 py-4">
                                <div class="text-xs text-muted-foreground">${{ price.cost_price ?? '—' }}</div>
                            </td>

                            <td v-if="editingPriceId === price.id" class="px-6 py-4">
                                <div class="flex gap-2">
                                    <Button @click="submitEdit(price.id)" size="sm" :disabled="editForm.processing">
                                        <Save class="h-3 w-3 mr-1" /> Save
                                    </Button>
                                    <Button @click="cancelEdit" variant="ghost" size="sm">Cancel</Button>
                                </div>
                            </td>
                            <td v-else class="px-6 py-4">
                                <Badge :variant="price.effective_to ? 'secondary' : 'success'" class="rounded-full px-2 py-0 text-[10px]">
                                    {{ price.effective_to ? 'Historical' : 'Active' }}
                                </Badge>
                            </td>

                            <td v-if="editingPriceId !== price.id" class="px-6 py-4">
                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Button @click="startEdit(price)" variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <button v-if="!price.effective_to" @click="handleDelete(price.id)"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                        :disabled="deleting" title="Cannot delete active price">
                                        <Trash2 class="h-4 w-4 opacity-50 cursor-not-allowed" />
                                    </button>
                                    <button v-else @click="handleDelete(price.id)"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                        :disabled="deleting">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="prices.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic">
                                No price history found. Click "Add Price" to set the initial price.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

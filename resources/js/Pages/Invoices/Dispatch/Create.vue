<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { Truck, Calendar, Store, FileText, Send, Printer, Plus, Minus } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { ref, computed, watch } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
    dispatchDate: { type: String, default: () => new Date().toISOString().split('T')[0] },
    shops: Array,
    newspapers: Array,
})

const dispatchDate = ref(props.dispatchDate)
const selectedShop = ref(null)
const newspaperQuantities = ref({})
const generating = ref(false)
const basePrice = ref(0.10) // Default price per newspaper

// Initialize all newspapers with quantity 0
const initializeQuantities = () => {
    if (props.newspapers && props.newspapers.length > 0) {
        const qty = {}
        props.newspapers.forEach(n => {
            qty[n.id] = 0
        })
        newspaperQuantities.value = qty
    }
}

// Initialize on load
initializeQuantities()

const totalQty = computed(() => {
    return Object.values(newspaperQuantities.value).reduce((sum, q) => sum + (parseInt(q) || 0), 0)
})

const totalAmount = computed(() => {
    return totalQty.value * basePrice.value
})

const hasQuantities = computed(() => {
    return totalQty.value > 0
})

const updateQty = (id, value) => {
    newspaperQuantities.value[id] = parseInt(value) || 0
}

const increment = (id) => {
    newspaperQuantities.value[id] = (newspaperQuantities.value[id] || 0) + 1
}

const decrement = (id) => {
    const current = newspaperQuantities.value[id] || 0
    if (current > 0) {
        newspaperQuantities.value[id] = current - 1
    }
}

const generateInvoices = () => {
    if (!hasQuantities.value) {
        Swal.fire('Warning', 'Please add at least one newspaper quantity.', 'warning')
        return
    }

    generating.value = true

    // Build quantities object
    const quantities = {}
    Object.entries(newspaperQuantities.value).forEach(([newspaperId, qty]) => {
        if (qty > 0) {
            quantities[newspaperId] = qty
        }
    })

    router.post('/dispatch/generate', {
        dispatch_date: dispatchDate.value,
        shop_id: selectedShop.value,
        quantities: quantities,
    }, {
        preserveState: true,
        onSuccess: (page) => {
            generating.value = false
            if (page.props.invoices && page.props.invoices.length > 0) {
                const invoice = page.props.invoices[0]
                Swal.fire({
                    title: 'Success',
                    text: 'Invoice created successfully!',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'View & Print',
                    cancelButtonText: 'Create Another',
                }).then((result) => {
                    if (result.isConfirmed || result.dismiss === 'confirm') {
                        window.location.href = `/invoices/${invoice.id}`
                    } else if (result.dismiss === 'cancel') {
                        // Reset and create another
                        initializeQuantities()
                        step = 1
                    }
                })
            } else {
                Swal.fire('Warning', 'No invoices were generated.', 'warning')
            }
        },
        onError: (errors) => {
            generating.value = false
            Swal.fire('Error', Object.values(errors)[0] || 'Failed to generate invoice', 'error')
        },
        onFinish: () => {
            generating.value = false
        },
    })
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'draft': 'bg-yellow-100 text-yellow-800',
        'confirmed': 'bg-blue-100 text-blue-800',
        'sent': 'bg-purple-100 text-purple-800',
        'delivered': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <Head title="Create Invoice" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <FileText class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Create Invoice</h2>
                </div>
                <p class="text-muted-foreground">Select date and add newspapers to create invoice</p>
            </div>
        </div>

        <!-- Selection Form -->
        <div class="rounded-2xl border bg-card p-6 shadow-sm mb-6">
            <div class="mb-6 flex items-center gap-2 border-b pb-4">
                <Calendar class="h-5 w-5 text-primary" />
                <h3 class="font-bold">Invoice Details</h3>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Dispatch Date</label>
                    <input type="date" v-model="dispatchDate"
                        class="w-full h-10 rounded-lg border bg-background px-3 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Shop</label>
                    <select v-model="selectedShop"
                        class="w-full h-10 rounded-lg border bg-background px-3 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                        <option :value="null">All Shops</option>
                        <option v-for="shop in shops" :key="shop.id" :value="shop.id">
                            {{ shop.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Base Price (Rs.)</label>
                    <input type="number" v-model="basePrice" step="0.01" min="0"
                        class="w-full h-10 rounded-lg border bg-background px-3 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-secondary/20 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Total Newspapers:</span>
                    <span class="font-bold">{{ Object.keys(newspaperQuantities).length }}</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-2">
                    <span class="text-muted-foreground">Total Quantity:</span>
                    <span class="font-bold text-lg">{{ totalQty }}</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-2 border-t pt-2">
                    <span class="text-muted-foreground">Estimated Amount:</span>
                    <span class="font-bold text-xl text-green-600">Rs. {{ totalAmount.toFixed(2) }}</span>
                </div>
            </div>

            <Button @click="generateInvoices" :disabled="generating || !hasQuantities"
                class="rounded-xl px-6 py-6 w-full text-lg">
                <FileText v-if="generating" class="mr-2 h-5 w-5 animate-spin" />
                <FileText v-else class="mr-2 h-5 w-5" />
                {{ generating ? 'Creating Invoice...' : 'Create Invoice' }}
            </Button>
        </div>

        <!-- Newspapers List -->
        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex items-center gap-2">
                    <Store class="h-5 w-5 text-primary" />
                    <h3 class="font-bold">Newspapers - Set Quantities</h3>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Newspaper</th>
                            <th class="px-6 py-4">Price</th>
                            <th class="px-6 py-4 text-center">Quantity</th>
                            <th class="px-6 py-4 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="newspaper in newspapers" :key="newspaper.id"
                            class="hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ newspaper.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ newspaper.code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-green-600 font-semibold">Rs. {{ basePrice.toFixed(2) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <Button variant="outline" size="sm" @click="decrement(newspaper.id)"
                                        class="h-8 w-8 rounded-lg" type="button">
                                        <Minus class="h-3 w-3" />
                                    </Button>
                                    <input type="number" :value="newspaperQuantities[newspaper.id] || 0"
                                        @input="updateQty(newspaper.id, $event.target.value)"
                                        class="w-16 h-9 text-center rounded-lg border bg-background text-sm focus:border-primary outline-none"
                                        min="0" />
                                    <Button variant="outline" size="sm" @click="increment(newspaper.id)"
                                        class="h-8 w-8 rounded-lg" type="button">
                                        <Plus class="h-3 w-3" />
                                    </Button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                Rs. {{ ((newspaperQuantities[newspaper.id] || 0) * basePrice).toFixed(2) }}
                            </td>
                        </tr>
                        <tr v-if="newspapers.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-muted-foreground italic">
                                No newspapers found. Please add newspapers first.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
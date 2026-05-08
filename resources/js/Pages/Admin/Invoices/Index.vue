<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Plus, FileText, Eye, Store, CalendarDays } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { computed } from 'vue'

defineProps({
    invoices: Object,
})

const { props } = usePage()
const permissions = computed(() => props.auth.user?.permissions ?? [])

const canCreate = computed(() => permissions.value.includes('create invoices') || permissions.value.includes('manage invoices'))

const statusVariant = (status) => {
    const map = {
        draft: 'secondary',
        paid: 'success',
        cancelled: 'destructive',
    }
    return map[status] || 'secondary'
}
</script>

<template>
    <Head title="Invoices" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <FileText class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Invoices</h2>
                </div>
                <p class="text-muted-foreground">Manage distribution invoices.</p>
            </div>
            <Link v-if="canCreate" href="/admin/invoices/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Invoice
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Invoice</th>
                            <th class="px-6 py-4">Shop</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-right">Amount</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="inv in invoices.data" :key="inv.id" class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-foreground">#{{ inv.id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Store class="h-3.5 w-3.5 text-muted-foreground" />
                                    <span>{{ inv.shop?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    <CalendarDays class="h-3.5 w-3.5" />
                                    <span>{{ inv.invoice_date }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                    Rs. {{ parseFloat(inv.total_amount).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="statusVariant(inv.status)" class="rounded-full px-2 py-0 text-[10px] capitalize">
                                    {{ inv.status }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Link :href="`/admin/invoices/${inv.id}`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic">
                                No invoices found. Click "Create Invoice" to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="invoices.links && invoices.links.length > 3" class="px-6 py-4 border-t bg-secondary/10 flex items-center justify-center gap-2">
                <Link v-for="link in invoices.links" :key="link.label" :href="link.url || ''" preserve-scroll>
                    <Button :variant="link.active ? 'default' : 'ghost'" size="sm" :disabled="!link.url"
                        class="h-8 min-w-[2rem] rounded-lg" v-html="link.label" />
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

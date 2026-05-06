<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, Store, Search, MapPin, Phone, Mail } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { computed } from 'vue'
import Swal from 'sweetalert2'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm'

defineProps({
    shops: Object,
})

const { props } = usePage()
const permissions = computed(() => props.auth.user?.permissions ?? [])

const canEdit = computed(() => permissions.value.includes('edit shops') || permissions.value.includes('manage shops'))
const canDelete = computed(() => permissions.value.includes('delete shops') || permissions.value.includes('manage shops'))
const canCreate = computed(() => permissions.value.includes('create shops') || permissions.value.includes('manage shops'))

const { deleting, confirmDelete } = useDeleteConfirm('This action cannot be undone. This will permanently delete the Shop')

const handleDelete = (shopId) => {
    confirmDelete(() => 
        router.delete(`/admin/shops/${shopId}`, {
            onError: (errors) => {
                Swal.fire('Error!', Object.values(errors)[0] || 'Failed to delete shop.', 'error')
            }
        })
    )
}
</script>

<template>
    <Head title="Shops" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <Store class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Shops</h2>
                </div>
                <p class="text-muted-foreground">Manage distribution points, contacts and credit limits.</p>
            </div>
            <Link v-if="canCreate" href="/admin/shops/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    Add shop
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Shop Info</th>
                            <th class="px-6 py-4">Contact</th>
                            <th class="px-6 py-4">Route</th>
                            <th class="px-6 py-4">Balance</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="shop in shops.data" :key="shop.id" class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-semibold text-foreground">{{ shop.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ shop.code }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div v-if="shop.owner_name" class="text-xs font-medium">{{ shop.owner_name }}</div>
                                    <div v-if="shop.phone" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Phone class="h-3 w-3" /> {{ shop.phone }}
                                    </div>
                                    <div v-if="shop.email" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Mail class="h-3 w-3" /> {{ shop.email }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="shop.route" class="flex items-center gap-1.5">
                                    <MapPin class="h-3.5 w-3.5 text-muted-foreground" />
                                    <span class="text-xs">{{ shop.route.name }}</span>
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic">No route</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold" :class="shop.opening_balance > 0 ? 'text-destructive' : 'text-emerald-600'">
                                    {{ shop.opening_balance }}
                                </div>
                                <div class="text-[10px] text-muted-foreground leading-none">Limit: {{ shop.credit_limit }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="shop.status === 'active' ? 'success' : 'secondary'" class="rounded-full px-2 py-0 text-[10px]">
                                    {{ shop.status }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Link v-if="canEdit" :href="`/admin/shops/${shop.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <button v-if="canDelete" @click="handleDelete(shop.id)"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                        :disabled="deleting">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="shops.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic">
                                No shops found. Click "Add shop" to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="shops.links && shops.links.length > 3" class="px-6 py-4 border-t bg-secondary/10 flex items-center justify-center gap-2">
                <Link v-for="link in shops.links" :key="link.label" :href="link.url || ''" preserve-scroll>
                    <Button :variant="link.active ? 'default' : 'ghost'" size="sm" :disabled="!link.url"
                        class="h-8 min-w-[2rem] rounded-lg" v-html="link.label" />
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

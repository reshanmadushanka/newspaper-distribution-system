<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, Store, Search, MapPin, Phone, Mail } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { useTranslation } from '@/Composables/useTranslation'
import { computed } from 'vue'
import Swal from 'sweetalert2'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm'

const { t } = useTranslation()

defineProps({
    shops: Object,
})

const { props } = usePage()
const permissions = computed(() => props.auth.user?.permissions ?? [])

const canEdit = computed(() => permissions.value.includes('edit shops') || permissions.value.includes('manage shops'))
const canDelete = computed(() => permissions.value.includes('delete shops') || permissions.value.includes('manage shops'))
const canCreate = computed(() => permissions.value.includes('create shops') || permissions.value.includes('manage shops'))

const { deleting, confirmDelete } = useDeleteConfirm(t('common.cannot_undo') + ' ' + t('common.delete_confirm'))

const handleDelete = (shopId) => {
    confirmDelete(() =>
        router.delete(`/admin/shops/${shopId}`, {
            onError: (errors) => {
                Swal.fire(t('common.error') + '!', Object.values(errors)[0] || t('shops.delete_failed'), 'error')
            }
        })
    )
}
</script>

<template>
    <Head :title="t('navigation.shops')" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <Store class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">{{ t('navigation.shops') }}</h2>
                </div>
                <p class="text-muted-foreground">{{ t('shops.manage_description') }}</p>
            </div>
            <Link v-if="canCreate" href="/admin/shops/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ t('common.create') }} {{ t('navigation.shops') }}
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">{{ t('shops.shop_info') }}</th>
                            <th class="px-6 py-4">{{ t('shops.contact') }}</th>
                            <th class="px-6 py-4">{{ t('common.status') }}</th>
                            <th class="px-6 py-4 text-right">{{ t('common.actions') }}</th>
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
                                {{ t('shops.no_shops_message') }}
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

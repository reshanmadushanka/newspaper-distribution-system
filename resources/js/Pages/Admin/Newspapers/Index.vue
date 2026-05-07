<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, Newspaper, DollarSign } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm'

defineProps({
    newspapers: Object,
    canCreate: { type: Boolean, default: true },
    canEdit: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: true },
})

const { deleting, confirmDelete } = useDeleteConfirm('This action cannot be undone. This will permanently delete the newspaper.')

const handleDelete = (newspaperId) => {
    confirmDelete(() => 
        router.delete(`/admin/newspapers/${newspaperId}`, {
            onError: (errors) => {
                Swal.fire('Error!', Object.values(errors)[0] || 'Failed to delete newspaper.', 'error')
            }
        })
    )
}

const languageVariant = (lang) => {
    switch (lang) {
        case 'English': return 'default'
        case 'Tamil': return 'secondary'
        case 'Sinhala': return 'outline'
        default: return 'secondary'
    }
}
</script>

<template>
    <Head title="Newspapers" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <Newspaper class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Newspapers</h2>
                </div>
                <p class="text-muted-foreground">Manage newspaper catalog, languages and pricing.</p>
            </div>
            <Link v-if="canCreate" href="/admin/newspapers/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    Add newspaper
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Newspaper</th>
                            <th class="px-6 py-4">Language</th>
                            <th class="px-6 py-4">Publication</th>
                            <th class="px-6 py-4">Price</th>
                            <th class="px-6 py-4">Cost</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="newspaper in newspapers.data" :key="newspaper.id" class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-foreground">{{ newspaper.name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="languageVariant(newspaper.language)" class="rounded-full px-2 py-0 text-[10px]">
                                    {{ newspaper.language }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-medium">{{ newspaper.frequency }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold text-green-600">Rs. {{ newspaper.price || '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-muted-foreground">Rs. {{ newspaper.cost_price || '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Link v-if="canEdit" :href="`/admin/newspapers/${newspaper.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <button v-if="canDelete" @click="handleDelete(newspaper.id)"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                        :disabled="deleting">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="newspapers.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic">
                                No newspapers found. Click "Add newspaper" to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="newspapers.links && newspapers.links.length > 3" class="px-6 py-4 border-t bg-secondary/10 flex items-center justify-center gap-2">
                <Link v-for="link in newspapers.links" :key="link.label" :href="link.url || ''" preserve-scroll>
                    <Button :variant="link.active ? 'default' : 'ghost'" size="sm" :disabled="!link.url"
                        class="h-8 min-w-[2rem] rounded-lg" v-html="link.label" />
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

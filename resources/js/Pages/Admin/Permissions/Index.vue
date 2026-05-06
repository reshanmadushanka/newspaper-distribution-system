<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, KeyRound, Search } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { computed } from 'vue'
import Swal from 'sweetalert2'
import { useDeleteConfirm } from '@/Composables/useDeleteConfirm'

defineProps({
    permissions: Array,
})

const { props } = usePage()
const perms = computed(() => props.auth.user?.permissions ?? [])

const canEdit = computed(() => perms.value.includes('manage permissions'))
const canDelete = computed(() => perms.value.includes('manage permissions'))
const canCreate = computed(() => perms.value.includes('manage permissions'))

const { deleting, confirmDelete } = useDeleteConfirm('This action cannot be undone. This will permanently delete the Permission.')

const handleDelete = (permId) => {
    confirmDelete(() => 
        router.delete(`/admin/permissions/${permId}`, {
            onError: (errors) => {
                Swal.fire('Error!', Object.values(errors)[0] || 'Failed to delete permission.', 'error')
            }
        })
    )
}
</script>

<template>

    <Head title="Permissions" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <KeyRound class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Permissions</h2>
                </div>
                <p class="text-muted-foreground">Manage granular access rules that can be assigned to different roles.
                </p>
            </div>
            <Link v-if="canCreate" href="/admin/permissions/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    Add permission
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <!-- <div class="border-b bg-card px-6 py-4">
                <div class="relative w-64">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input type="text" placeholder="Filter permissions..."
                        class="w-full h-9 pl-9 pr-4 rounded-lg border bg-secondary/30 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
                </div>
            </div> -->

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Permission Name</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="permission in permissions" :key="permission.id"
                            class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                        <KeyRound class="h-4 w-4" />
                                    </div>
                                    <span class="font-medium text-foreground">{{ permission.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Link v-if="canEdit" :href="`/admin/permissions/${permission.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <button v-if="canDelete && permission.name !== 'manage users'" @click="handleDelete(permission.id)"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors"
                                        :disabled="deleting">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

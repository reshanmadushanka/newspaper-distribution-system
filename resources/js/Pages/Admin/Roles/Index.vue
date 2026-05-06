<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, Shield, Search } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { computed, ref } from 'vue'
import Swal from 'sweetalert2'

defineProps({
    roles: Array,
})

const { props } = usePage()
const permissions = computed(() => props.auth.user?.permissions ?? [])

const canEdit = computed(() => permissions.value.includes('manage roles'))
const canDelete = computed(() => permissions.value.includes('manage roles'))
const canCreate = computed(() => permissions.value.includes('manage roles'))

const deleting = ref(false)

const confirmDelete = async (roleId) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone. This will permanently delete the role.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--color-destructive)',
        cancelButtonColor: 'var(--color-muted-foreground)',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl',
            cancelButton: 'rounded-xl'
        }
    })

    if (result.isConfirmed) {
        deleting.value = true
        router.delete(`/admin/roles/${roleId}`, {
            onFinish: () => {
                deleting.value = false
            },
            onError: (errors) => {
                deleting.value = false
                Swal.fire('Error!', Object.values(errors)[0] || 'Failed to delete role.', 'error')
            }
        })
    }
}
</script>

<template>

    <Head title="Roles" />
    <AdminLayout>
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <Shield class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Roles</h2>
                </div>
                <p class="text-muted-foreground">Define and manage user access roles and their associated permissions.
                </p>
            </div>
            <Link v-if="canCreate" href="/admin/roles/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    Add role
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <!-- <div class="border-b bg-card px-6 py-4 flex items-center justify-between">
                <div class="relative w-64">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input type="text" placeholder="Filter roles..." class="w-full h-9 pl-9 pr-4 rounded-lg border bg-secondary/30 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
                </div>
            </div> -->

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 w-1/4">Role Name</th>
                            <th class="px-6 py-4">Permissions</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="role in roles" :key="role.id" class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4 font-semibold text-foreground">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-primary"></div>
                                    {{ role.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <Badge v-for="permission in role.permissions" :key="permission"
                                        class="rounded-lg bg-secondary text-foreground border-border px-2 py-0 text-[11px]">
                                        {{ permission }}
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Link v-if="canEdit" :href="`/admin/roles/${role.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <button v-if="canDelete && role.name !== 'super-admin'" @click="confirmDelete(role.id)"
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

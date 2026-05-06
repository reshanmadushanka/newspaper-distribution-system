<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'

defineProps({
    permissions: Array,
})
</script>

<template>
    <Head title="Permissions" />
    <AdminLayout>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">Permissions</h2>
                <p class="text-sm text-muted-foreground">Define access rules used by roles.</p>
            </div>
            <Link href="/admin/permissions/create">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Add permission
                </Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Permission</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="permission in permissions" :key="permission.id">
                        <td class="px-4 py-3 font-medium">{{ permission.name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/admin/permissions/${permission.id}/edit`">
                                    <Button variant="outline" size="sm">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Link :href="`/admin/permissions/${permission.id}`" method="delete" as="button" class="inline-flex h-9 items-center justify-center rounded-md bg-destructive px-3 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90">
                                        <Trash2 class="h-4 w-4" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

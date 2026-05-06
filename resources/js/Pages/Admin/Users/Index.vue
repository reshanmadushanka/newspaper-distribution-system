<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'

defineProps({
    users: Object,
    roles: Array,
})
</script>

<template>
    <Head title="Users" />
    <AdminLayout>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">Users</h2>
                <p class="text-sm text-muted-foreground">Manage login accounts and assigned roles.</p>
            </div>
            <Link href="/admin/users/create">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Add user
                </Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Roles</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <Badge v-for="role in user.roles" :key="role">{{ role }}</Badge>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/admin/users/${user.id}/edit`">
                                    <Button variant="outline" size="sm">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Link :href="`/admin/users/${user.id}`" method="delete" as="button" class="inline-flex h-9 items-center justify-center rounded-md bg-destructive px-3 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90">
                                        <Trash2 class="h-4 w-4" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <Link v-for="link in users.links" :key="link.label" :href="link.url || ''" preserve-scroll>
                <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" v-html="link.label" />
            </Link>
        </div>
    </AdminLayout>
</template>

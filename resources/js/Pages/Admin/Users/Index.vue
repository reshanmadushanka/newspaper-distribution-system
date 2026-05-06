<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, Users, Search, MoreHorizontal } from 'lucide-vue-next'
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
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-primary">
                    <Users class="h-6 w-6" />
                    <h2 class="text-2xl font-bold tracking-tight">Users</h2>
                </div>
                <p class="text-muted-foreground">Manage your team members and their account access levels.</p>
            </div>
            <Link href="/admin/users/create">
                <Button class="rounded-xl px-5 shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                    <Plus class="mr-2 h-4 w-4" />
                    Add user
                </Button>
            </Link>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
            <div class="border-b bg-card px-6 py-4 flex items-center justify-between">
                <div class="relative w-64">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input type="text" placeholder="Filter users..." class="w-full h-9 pl-9 pr-4 rounded-lg border bg-secondary/30 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
                </div>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Showing {{ users.data.length }} users</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary/30 text-muted-foreground uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Name & Email</th>
                            <th class="px-6 py-4">Roles</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr v-for="user in users.data" :key="user.id" class="group transition-colors hover:bg-secondary/20">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                                        {{ user.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-foreground">{{ user.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <Badge 
                                        v-for="role in user.roles" 
                                        :key="role"
                                        class="rounded-lg bg-primary/5 text-primary border-primary/10 px-2 py-0"
                                    >
                                        {{ role }}
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <Badge class="rounded-full bg-emerald-500/10 text-emerald-600 border-emerald-500/20 px-2">Active</Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Link :href="`/admin/users/${user.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="`/admin/users/${user.id}`" method="delete" as="button" class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors">
                                        <Trash2 class="h-4 w-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t bg-secondary/10 flex items-center justify-center gap-2">
                <Link v-for="link in users.links" :key="link.label" :href="link.url || ''" preserve-scroll>
                    <Button 
                        :variant="link.active ? 'default' : 'ghost'" 
                        size="sm" 
                        :disabled="!link.url" 
                        class="h-8 min-w-[2rem] rounded-lg"
                        v-html="link.label" 
                    />
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>


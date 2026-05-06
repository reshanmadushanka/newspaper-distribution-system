<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { LogOut, Shield, Users, KeyRound } from 'lucide-vue-next'

const page = usePage()
const permissions = page.props.auth.user?.permissions ?? []

const nav = [
    { label: 'Users', href: '/admin/users', icon: Users, permission: 'manage users' },
    { label: 'Roles', href: '/admin/roles', icon: Shield, permission: 'manage roles' },
    { label: 'Permissions', href: '/admin/permissions', icon: KeyRound, permission: 'manage permissions' },
].filter((item) => permissions.includes(item.permission))
</script>

<template>
    <div class="min-h-screen bg-background">
        <header class="border-b bg-card">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <h1 class="text-lg font-semibold">Newspaper Distribution</h1>
                    <p class="text-sm text-muted-foreground">{{ page.props.auth.user?.email }}</p>
                </div>
                <Link href="/logout" method="post" as="button" class="inline-flex h-9 items-center justify-center rounded-md border bg-card px-3 text-sm font-medium transition-colors hover:bg-muted">
                        <LogOut class="mr-2 h-4 w-4" />
                        Logout
                </Link>
            </div>
        </header>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[220px_1fr] lg:px-8">
            <aside class="rounded-lg border bg-card p-2">
                <nav class="space-y-1">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <component :is="item.icon" class="mr-2 h-4 w-4" />
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>

            <main>
                <div v-if="page.props.flash.success" class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ page.props.flash.success }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

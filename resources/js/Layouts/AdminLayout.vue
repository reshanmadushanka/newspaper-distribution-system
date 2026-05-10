<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import {
    LogOut, Shield, Users, KeyRound, LayoutDashboard, Newspaper,
    Settings, ChevronRight, Bell, Search, User, Menu, X, Lock,
    ChevronDown, Store, FileText, BarChart3
} from 'lucide-vue-next'

import { computed, ref, onMounted } from 'vue'
import Toast from '@/Components/Toast.vue'

const page = usePage()
const permissions = page.props.auth.user?.permissions ?? []
const user = computed(() => page.props.auth.user)

const isMobileMenuOpen = ref(false)
const expandedGroups = ref(['Access Management']) // Default expanded

const toggleGroup = (title) => {
    if (expandedGroups.value.includes(title)) {
        expandedGroups.value = expandedGroups.value.filter(t => t !== title)
    } else {
        expandedGroups.value.push(title)
    }
}

const menuGroups = computed(() => [
    {
        title: 'Overview',
        type: 'item',
        label: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
        permission: 'view dashboard'
    },
    {
        title: 'Shops',
        type: 'item',
        label: 'Shops',
        href: '/admin/shops',
        icon: Store,
        permission: 'manage shops'
    },
    {
        title: 'News Papers',
        type: 'item',
        label: 'News Papers',
        href: '/admin/newspapers',
        icon: Newspaper,
        permission: 'manage newspapers'
    },
    {
        title: 'Invoices',
        type: 'item',
        label: 'Invoices',
        href: '/admin/invoices',
        icon: FileText,
        permission: 'manage invoices'
    },
    {
        title: 'Reports',
        type: 'item',
        label: 'Sales Report',
        href: '/admin/reports/daily-sales',
        icon: BarChart3,
        permission: 'manage invoices'
    },
    {
        title: 'Access Management',
        type: 'group',
        icon: Lock,
        items: [
            { label: 'Users', href: '/admin/users', icon: Users, permission: 'manage users' },
            { label: 'Roles', href: '/admin/roles', icon: Shield, permission: 'manage roles' },
            { label: 'Permissions', href: '/admin/permissions', icon: KeyRound, permission: 'manage permissions' },
        ]

    }
].map(group => {
    if (group.type === 'group') {
        return {
            ...group,
            items: group.items.filter(item => !item.permission || permissions.includes(item.permission))
        }
    }
    return group
}).filter(group => {
    if (group.type === 'group') return group.items.length > 0
    return !group.permission || permissions.includes(group.permission)
}))

const isActive = (href) => page.url.startsWith(href)
const isGroupActive = (items) => items.some(item => isActive(item.href))

// Auto-expand active group on mount
onMounted(() => {
    menuGroups.value.forEach(group => {
        if (group.type === 'group' && isGroupActive(group.items)) {
            if (!expandedGroups.value.includes(group.title)) {
                expandedGroups.value.push(group.title)
            }
        }
    })
})
</script>

<template>
    <div class="flex min-h-screen bg-background text-foreground font-sans">
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-40 bg-background/80 backdrop-blur-sm lg:hidden"
            @click="isMobileMenuOpen = false"></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 border-r bg-card transition-transform duration-300 lg:static lg:block lg:translate-x-0"
            :class="[isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full']">
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-16 items-center justify-between border-b px-6 bg-card/50 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-lg shadow-primary/30">
                            <Newspaper class="h-5 w-5" />
                        </div>
                        <span
                            class="text-xl font-bold tracking-tight bg-gradient-to-br from-foreground to-foreground/70 bg-clip-text text-transparent">NewsFlow</span>
                    </div>
                    <button @click="isMobileMenuOpen = false"
                        class="lg:hidden p-1 text-muted-foreground hover:text-foreground transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 space-y-2 overflow-y-auto p-4 custom-scrollbar">
                    <div v-for="group in menuGroups" :key="group.title">
                        <!-- Single Item -->
                        <Link v-if="group.type === 'item'" :href="group.href"
                            class="group flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200"
                            :class="[
                                isActive(group.href)
                                    ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20'
                                    : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                            ]" @click="isMobileMenuOpen = false">
                            <div class="flex items-center gap-3">
                                <component :is="group.icon" class="h-5 w-5" />
                                {{ group.label }}
                            </div>
                        </Link>

                        <!-- Expandable Group -->
                        <div v-else class="space-y-1">
                            <button @click="toggleGroup(group.title)"
                                class="flex w-full items-center justify-between rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200"
                                :class="[
                                    isGroupActive(group.items)
                                        ? 'text-primary'
                                        : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                                ]">
                                <div class="flex items-center gap-3">
                                    <component :is="group.icon" class="h-5 w-5" />
                                    {{ group.title }}
                                </div>
                                <ChevronDown class="h-4 w-4 transition-transform duration-200"
                                    :class="[expandedGroups.includes(group.title) ? 'rotate-180' : '']" />
                            </button>

                            <!-- Submenu Items -->
                            <div v-show="expandedGroups.includes(group.title)"
                                class="mt-1 space-y-1 overflow-hidden transition-all duration-300">
                                <Link v-for="item in group.items" :key="item.href" :href="item.href"
                                    class="flex items-center gap-3 rounded-xl py-2.5 pl-12 pr-4 text-sm font-medium transition-all duration-200"
                                    :class="[
                                        isActive(item.href)
                                            ? 'text-primary'
                                            : 'text-muted-foreground hover:bg-secondary/50 hover:text-foreground'
                                    ]" @click="isMobileMenuOpen = false">
                                    <div class="h-1.5 w-1.5 rounded-full transition-all"
                                        :class="[isActive(item.href) ? 'bg-primary scale-125' : 'bg-muted-foreground/30']">
                                    </div>
                                    {{ item.label }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- User Profile Sidebar -->
                <div class="border-t p-4 bg-secondary/10">
                    <div class="flex items-center gap-3 rounded-2xl bg-card border p-3 shadow-sm">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <User class="h-6 w-6" />
                        </div>
                        <div class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap">
                            <p class="text-sm font-bold leading-none text-foreground truncate">{{ user?.name ||
                                'AdminUser' }}</p>
                            <p class="mt-1 text-xs text-muted-foreground truncate">{{ user?.email }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <header class="sticky top-0 z-40 h-16 border-b bg-background/80 backdrop-blur-md">
                <div class="flex h-full items-center justify-between px-6">
                    <div class="flex items-center gap-4">
                        <!-- Mobile Menu Trigger -->
                        <button
                            class="lg:hidden p-2 -ml-2 text-muted-foreground hover:text-foreground transition-colors"
                            @click="isMobileMenuOpen = true">
                            <Menu class="h-6 w-6" />
                        </button>

                        <!-- Search Bar -->
                        <div class="relative hidden sm:block">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <input type="text" placeholder="Quick Search..."
                                class="h-9 w-64 rounded-full border bg-secondary/50 pl-10 pr-4 text-sm outline-none transition-all focus:border-primary/50 focus:bg-card focus:ring-4 focus:ring-primary/5" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            class="relative rounded-full p-2 text-muted-foreground transition-all hover:bg-secondary hover:text-foreground focus:outline-none">
                            <Bell class="h-5 w-5" />
                            <span
                                class="absolute right-2 top-2 h-2 w-2 rounded-full bg-destructive border-2 border-background animate-pulse"></span>
                        </button>
                        <div class="h-8 w-px bg-border mx-1"></div>
                        <Link href="/logout" method="post" as="button"
                            class="group flex items-center gap-2 rounded-full border border-destructive/20 bg-destructive/5 p-1 pr-4 transition-all hover:bg-destructive hover:text-white shadow-sm">
                            <div
                                class="h-7 w-7 rounded-full bg-destructive/10 text-destructive flex items-center justify-center group-hover:bg-white/20 group-hover:text-white transition-colors">
                                <LogOut class="h-4 w-4" />
                            </div>
                            <span class="text-xs font-bold">Logout</span>
                        </Link>
                    </div>
                </div>
            </header>





            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6 lg:p-8">
                <div class="mx-auto max-w-7xl">
                    <Toast />
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Optional: Custom scrollbar for the navigation */
nav::-webkit-scrollbar {
    width: 4px;
}

nav::-webkit-scrollbar-thumb {
    background: transparent;
    border-radius: 10px;
}

nav:hover::-webkit-scrollbar-thumb {
    background: var(--color-border);
}
</style>

<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { 
    Users, 
    Shield, 
    KeyRound, 
    TrendingUp, 
    Clock, 
    ArrowUpRight,
    ArrowDownRight,
    Newspaper
} from 'lucide-vue-next'

const stats = [
    { label: 'Total Users', value: '1,284', icon: Users, change: '+12.5%', trendingUp: true, color: 'text-blue-600', bg: 'bg-blue-100/50' },
    { label: 'Active Roles', value: '12', icon: Shield, change: '0%', trendingUp: true, color: 'text-purple-600', bg: 'bg-purple-100/50' },
    { label: 'Permissions', value: '48', icon: KeyRound, change: '+2', trendingUp: true, color: 'text-amber-600', bg: 'bg-amber-100/50' },
    { label: 'Distributions', value: '8,432', icon: Newspaper, change: '+18.2%', trendingUp: true, color: 'text-emerald-600', bg: 'bg-emerald-100/50' },
]
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="mb-8">
            <h2 class="text-3xl font-bold tracking-tight">Welcome back, Admin!</h2>
            <p class="text-muted-foreground mt-1">Here's what's happening with your distribution system today.</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 mb-8">
            <div 
                v-for="stat in stats" 
                :key="stat.label"
                class="group rounded-2xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:-translate-y-1"
            >
                <div class="flex items-center justify-between mb-4">
                    <div :class="['p-2.5 rounded-xl transition-colors', stat.bg, stat.color]">
                        <component :is="stat.icon" class="h-6 w-6" />
                    </div>
                    <div :class="['flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full', stat.trendingUp ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600']">
                        <component :is="stat.trendingUp ? ArrowUpRight : ArrowDownRight" class="h-3 w-3" />
                        {{ stat.change }}
                    </div>
                </div>
                <div>
                    <p class="text-sm font-medium text-muted-foreground">{{ stat.label }}</p>
                    <h3 class="text-2xl font-bold mt-1">{{ stat.value }}</h3>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold">Recent Distributions</h3>
                    <button class="text-sm font-medium text-primary hover:underline">View all</button>
                </div>
                <div class="space-y-4">
                    <div v-for="i in 5" :key="i" class="flex items-center justify-between p-3 rounded-xl hover:bg-secondary/30 transition-colors border border-transparent hover:border-border">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-secondary flex items-center justify-center text-primary font-bold">
                                {{ ['M', 'T', 'D', 'N', 'E'][i-1] }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm">Morning Herald #{{ 1200 + i }}</p>
                                <p class="text-xs text-muted-foreground flex items-center gap-1">
                                    <Clock class="h-3 w-3" /> {{ i * 2 }} hours ago
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold">LKR {{ (500 * i).toLocaleString() }}</p>
                            <p class="text-[10px] uppercase font-bold text-emerald-600">Delivered</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions / Tips -->
            <div class="space-y-6">
                <div class="rounded-2xl bg-primary p-6 text-primary-foreground shadow-lg shadow-primary/20">
                    <h3 class="text-lg font-bold mb-2">Upgrade to Pro</h3>
                    <p class="text-sm opacity-90 mb-4">Get access to advanced analytics and automated distribution routing.</p>
                    <button class="w-full py-2.5 rounded-xl bg-white text-primary font-bold text-sm transition-transform hover:scale-[1.02] active:scale-[0.98]">
                        Learn More
                    </button>
                </div>

                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <button class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <Users class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">New User</span>
                        </button>
                        <button class="flex flex-col items-center justify-center p-4 rounded-xl bg-secondary/50 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20">
                            <Newspaper class="h-5 w-5 mb-2" />
                            <span class="text-xs font-medium">Reports</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>


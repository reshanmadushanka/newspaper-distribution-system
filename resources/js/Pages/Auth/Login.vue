<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Newspaper, Lock, Mail, ArrowRight, NewspaperIcon } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Card } from '@/Components/ui/card'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>

    <Head title="NewsFlow Login" />

    <div class="relative min-h-screen w-full flex items-center justify-center overflow-hidden bg-[#0a0a0a] px-4 py-12">
        <!-- Background Image with Parallax-like Overlay -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat scale-105"
            style="background-image: url('/images/auth-bg.png'); filter: brightness(0.3) saturate(0.8);"></div>

        <!-- Gradient Overlays for Depth -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-transparent to-background/80"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.4)_100%)]"></div>

        <!-- Animated Background Shapes -->
        <div
            class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] rounded-full bg-primary/10 blur-[120px] animate-pulse">
        </div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] rounded-full bg-primary/15 blur-[120px] animate-pulse"
            style="animation-delay: 2s"></div>

        <div class="w-full max-w-[460px] z-10 transition-all duration-700 ease-out transform">
            <div class="mb-12 text-center">
                <div class="relative inline-flex mb-6 group">
                    <div
                        class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-primary to-primary/50 opacity-75 blur transition duration-1000 group-hover:duration-200 group-hover:opacity-100">
                    </div>
                    <div
                        class="relative flex h-20 w-20 items-center justify-center rounded-2xl bg-card shadow-2xl text-primary transition-transform hover:scale-105 duration-500">
                        <NewspaperIcon class="h-10 w-10" />
                    </div>
                </div>
                <h1 class="text-5xl font-black tracking-tight text-white mb-3">
                    News<span class="text-primary">Flow</span>
                </h1>
                <p class="text-gray-400 text-lg font-medium">Sri Lanka's Premier Distribution Portal</p>
            </div>

            <Card
                class="border border-white/10 shadow-2xl bg-black/40 backdrop-blur-2xl p-10 rounded-[2.5rem] relative overflow-hidden group">
                <!-- Subtle internal glow -->
                <div
                    class="absolute -inset-px bg-gradient-to-tr from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none">
                </div>

                <form class="space-y-8 relative z-10" @submit.prevent="submit">
                    <div class="space-y-2.5">
                        <Label for="email" class="text-sm font-bold text-gray-300 ml-1">Work Email</Label>
                        <div class="relative group/input">
                            <Mail
                                class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-500 transition-colors group-focus-within/input:text-primary" />
                            <Input id="email" v-model="form.email" type="email" placeholder="name@newsflow.lk"
                                class="h-14 pl-12 rounded-2xl bg-white/5 border-white/10 text-white placeholder:text-gray-600 transition-all focus:bg-white/10 focus:ring-4 focus:ring-primary/10 focus:border-primary/50"
                                autocomplete="email" autofocus />
                        </div>
                        <p v-if="form.errors.email" class="text-xs font-semibold text-red-400 mt-1 ml-1">{{
                            form.errors.email }}</p>
                    </div>

                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between ml-1">
                            <Label for="password" class="text-sm font-bold text-gray-300">Secure Password</Label>
                            <a href="#"
                                class="text-xs font-bold text-primary/80 hover:text-primary transition-colors">Recovery
                                Assistance</a>
                        </div>
                        <div class="relative group/input">
                            <Lock
                                class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-500 transition-colors group-focus-within/input:text-primary" />
                            <Input id="password" v-model="form.password" type="password" placeholder="••••••••"
                                class="h-14 pl-12 rounded-2xl bg-white/5 border-white/10 text-white placeholder:text-gray-600 transition-all focus:bg-white/10 focus:ring-4 focus:ring-primary/10 focus:border-primary/50"
                                autocomplete="current-password" />
                        </div>
                        <p v-if="form.errors.password" class="text-xs font-semibold text-red-400 mt-1 ml-1">{{
                            form.errors.password }}</p>
                    </div>

                    <div class="flex items-center gap-3 px-1">
                        <div class="relative flex items-center">
                            <input id="remember" v-model="form.remember" type="checkbox"
                                class="peer h-5 w-5 rounded-lg border-white/10 bg-white/5 text-primary focus:ring-offset-0 focus:ring-primary/20 transition-all cursor-pointer appearance-none checked:bg-primary checked:border-primary">
                                <svg class="absolute h-5 w-5 pointer-events-none hidden peer-checked:block text-white p-1"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                        </div>
                        <Label for="remember"
                            class="text-sm font-bold text-gray-400 cursor-pointer select-none">Remember this
                            device</Label>
                    </div>

                    <Button
                        class="group/btn w-full h-14 rounded-2xl bg-primary text-primary-foreground font-black text-lg shadow-2xl shadow-primary/30 transition-all hover:shadow-primary/40 hover:-translate-y-1 active:translate-y-0 disabled:opacity-70 overflow-hidden relative"
                        type="submit" :disabled="form.processing">
                        <span v-if="!form.processing" class="flex items-center justify-center gap-3 relative z-10">
                            LOGIN
                            <ArrowRight class="h-5 w-5 transition-transform group-hover/btn:translate-x-1" />
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            VERIFYING...
                        </span>
                    </Button>
                </form>
            </Card>

            <div class="mt-2 flex flex-col items-center gap-6 animate-fade-in">
                <div class="pt-6 border-t border-white/5 w-full flex flex-col items-center gap-3">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">
                        Developed by <span class="text-white">Reshan Wijerathna</span>
                    </p>
                    <div class="flex items-center gap-4 text-[10px] font-black tracking-tighter">
                        <a href="https://reshan.dev" target="_blank"
                            class="text-primary hover:text-white transition-colors">RESHAN.DEV</a>
                        <span class="h-1 w-1 rounded-full bg-gray-700"></span>
                        <a href="tel:0711380025" class="text-gray-400 hover:text-white transition-colors">0711380025</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shimmer {
    0% {
        transform: skewX(-30deg) translateX(-100%);
    }

    100% {
        transform: skewX(-30deg) translateX(300%);
    }
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 1s ease-out forwards;
}
</style>

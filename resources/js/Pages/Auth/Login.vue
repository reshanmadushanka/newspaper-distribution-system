<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Newspaper, Lock, Mail, ArrowRight } from 'lucide-vue-next'
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
    <Head title="Login" />

    <div class="relative min-h-screen w-full flex items-center justify-center overflow-hidden bg-background px-4 py-12">
        <!-- Background Decorative Elements -->
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-primary/5 blur-[120px]"></div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] rounded-full bg-primary/10 blur-[120px]"></div>

        <div class="w-full max-w-[440px] z-10">
            <div class="mb-10 text-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-primary shadow-xl shadow-primary/20 text-primary-foreground mb-6 transition-transform hover:scale-110 duration-500">
                    <Newspaper class="h-8 w-8" />
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight text-foreground mb-2">Welcome Back</h1>
                <p class="text-muted-foreground text-lg">Enter your credentials to access the portal</p>
            </div>

            <Card class="border-none shadow-2xl bg-card/80 backdrop-blur-xl p-8 rounded-[2rem]">
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="email" class="text-sm font-semibold ml-1">Email Address</Label>
                        <div class="relative group">
                            <Mail class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground transition-colors group-focus-within:text-primary" />
                            <Input 
                                id="email" 
                                v-model="form.email" 
                                type="email" 
                                placeholder="admin@newsflow.com"
                                class="h-12 pl-12 rounded-xl bg-secondary/30 border-transparent transition-all focus:bg-card focus:ring-4 focus:ring-primary/5 focus:border-primary/50"
                                autocomplete="email" 
                                autofocus 
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-xs font-medium text-destructive mt-1 ml-1">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between ml-1">
                            <Label for="password" class="text-sm font-semibold">Password</Label>
                            <a href="#" class="text-xs font-medium text-primary hover:underline">Forgot password?</a>
                        </div>
                        <div class="relative group">
                            <Lock class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground transition-colors group-focus-within:text-primary" />
                            <Input 
                                id="password" 
                                v-model="form.password" 
                                type="password" 
                                placeholder="••••••••"
                                class="h-12 pl-12 rounded-xl bg-secondary/30 border-transparent transition-all focus:bg-card focus:ring-4 focus:ring-primary/5 focus:border-primary/50"
                                autocomplete="current-password" 
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-xs font-medium text-destructive mt-1 ml-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center gap-2 px-1">
                        <input 
                            id="remember"
                            v-model="form.remember" 
                            type="checkbox" 
                            class="h-4 w-4 rounded border-input bg-secondary/50 text-primary transition-all focus:ring-primary/20"
                        >
                        <Label for="remember" class="text-sm font-medium text-muted-foreground cursor-pointer">Remember my session</Label>
                    </div>

                    <Button 
                        class="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold shadow-lg shadow-primary/20 transition-all hover:shadow-primary/30 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-70" 
                        type="submit" 
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing" class="flex items-center gap-2">
                            Sign In
                            <ArrowRight class="h-4 w-4" />
                        </span>
                        <span v-else>Signing in...</span>
                    </Button>
                </form>
            </Card>

            <p class="text-center mt-8 text-sm text-muted-foreground">
                Don't have an account? <span class="font-semibold text-primary cursor-pointer hover:underline">Contact System Admin</span>
            </p>
        </div>
    </div>
</template>


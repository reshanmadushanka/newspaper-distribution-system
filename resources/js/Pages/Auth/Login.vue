<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Newspaper } from 'lucide-vue-next'
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

    <div class="grid min-h-screen place-items-center px-4 py-10">
        <Card class="w-full max-w-md p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-md bg-primary text-primary-foreground">
                    <Newspaper class="h-5 w-5" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold">Login</h1>
                    <p class="text-sm text-muted-foreground">Access your distribution dashboard</p>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" autocomplete="email" autofocus />
                    <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="password">Password</Label>
                    <Input id="password" v-model="form.password" type="password" autocomplete="current-password" />
                    <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                    <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-input accent-primary">
                    Remember me
                </label>

                <Button class="w-full" type="submit" :disabled="form.processing">
                    Login
                </Button>
            </form>
        </Card>
    </div>
</template>

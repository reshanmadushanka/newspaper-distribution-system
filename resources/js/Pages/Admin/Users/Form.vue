<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Card } from '@/Components/ui/card'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { useTranslation } from '@/Composables/useTranslation'

const { t } = useTranslation()

const props = defineProps({
    user: Object,
    roles: Array,
})

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: props.user?.roles ?? [],
})

function toggleRole(role) {
    form.roles = form.roles.includes(role)
        ? form.roles.filter((item) => item !== role)
        : [...form.roles, role]
}

function submit() {
    if (props.user) {
        form.put(`/admin/users/${props.user.id}`)
    } else {
        form.post('/admin/users')
    }
}
</script>

<template>
    <Head :title="user ? t('users.edit_user') : t('users.create_user')" />
    <AdminLayout>
        <div class="mb-4">
            <h2 class="text-xl font-semibold">{{ user ? t('users.edit_user') : t('users.create_user') }}</h2>
        </div>

        <Card class="p-6">
            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">{{ t('users.name') }}</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('users.email') }}</Label>
                    <Input id="email" v-model="form.email" type="email" />
                    <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('users.password') }}</Label>
                    <Input id="password" v-model="form.password" type="password" />
                    <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ t('users.confirm_password') }}</Label>
                    <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
                </div>

                <div class="grid gap-2">
                    <Label>{{ t('users.roles') }}</Label>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 rounded-md border p-3 text-sm">
                            <input :checked="form.roles.includes(role.name)" type="checkbox" class="h-4 w-4 accent-primary" @change="toggleRole(role.name)">
                            {{ role.name }}
                        </label>
                    </div>
                    <p v-if="form.errors.roles" class="text-sm text-destructive">{{ form.errors.roles }}</p>
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                    <Link href="/admin/users">
                        <Button type="button" variant="outline">{{ t('common.cancel') }}</Button>
                    </Link>
                </div>
            </form>
        </Card>
    </AdminLayout>
</template>

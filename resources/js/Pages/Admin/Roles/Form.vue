<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Card } from '@/Components/ui/card'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'

const props = defineProps({
    role: Object,
    permissions: Array,
})

const form = useForm({
    name: props.role?.name ?? '',
    permissions: props.role?.permissions ?? [],
})

function togglePermission(permission) {
    form.permissions = form.permissions.includes(permission)
        ? form.permissions.filter((item) => item !== permission)
        : [...form.permissions, permission]
}

function submit() {
    if (props.role) {
        form.put(`/admin/roles/${props.role.id}`)
    } else {
        form.post('/admin/roles')
    }
}
</script>

<template>
    <Head :title="role ? 'Edit role' : 'Create role'" />
    <AdminLayout>
        <div class="mb-4">
            <h2 class="text-xl font-semibold">{{ role ? 'Edit role' : 'Create role' }}</h2>
        </div>

        <Card class="p-6">
            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-2">
                    <Label>Permissions</Label>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2 rounded-md border p-3 text-sm">
                            <input :checked="form.permissions.includes(permission.name)" type="checkbox" class="h-4 w-4 accent-primary" @change="togglePermission(permission.name)">
                            {{ permission.name }}
                        </label>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">Save</Button>
                    <Link href="/admin/roles">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </Card>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Card } from '@/Components/ui/card'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'

const props = defineProps({
    permission: Object,
})

const form = useForm({
    name: props.permission?.name ?? '',
})

function submit() {
    if (props.permission) {
        form.put(`/admin/permissions/${props.permission.id}`)
    } else {
        form.post('/admin/permissions')
    }
}
</script>

<template>
    <Head :title="permission ? 'Edit permission' : 'Create permission'" />
    <AdminLayout>
        <div class="mb-4">
            <h2 class="text-xl font-semibold">{{ permission ? 'Edit permission' : 'Create permission' }}</h2>
        </div>

        <Card class="p-6">
            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" placeholder="manage users" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">Save</Button>
                    <Link href="/admin/permissions">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </Card>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronLeft, Save, Newspaper, Languages, Calendar, DollarSign, ShieldAlert, Type } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Select2 } from '@/Components/ui/select2'
import { useSinhalaInput } from '@/Composables/useSinhalaInput'
import { ref } from 'vue'

const props = defineProps({
    newspaper: Object,
    languageOptions: Object,
    statusOptions: Object,
    frequencyOptions: Object,
})

const form = useForm({
    name: props.newspaper?.name ?? '',
    publisher_name: props.newspaper?.publisher_name ?? '',
    language: props.newspaper?.language ?? '',
    frequency: props.newspaper?.frequency ?? '',
    status: props.newspaper?.status ?? 'active',
    price: props.newspaper?.price ?? '',
    cost_price: props.newspaper?.cost_price ?? '',
})

const { enabled: sinhalaEnabled, toggle: toggleSinhala } = useSinhalaInput()

const submit = () => {
    if (props.newspaper) {
        form.put(`/admin/newspapers/${props.newspaper.id}`)
    } else {
        form.post('/admin/newspapers')
    }
}
</script>

<template>
    <Head :title="newspaper ? 'Edit Newspaper' : 'Create Newspaper'" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/admin/newspapers">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">{{ newspaper ? 'Edit Newspaper' : 'Create New Newspaper' }}</h2>
                    <p class="text-sm text-muted-foreground">Fill in the details to {{ newspaper ? 'update the' : 'register a new' }} newspaper.</p>
                </div>
            </div>

            <!-- Sinhala Input Toggle -->
            <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-2 shadow-sm">
                <Type class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm font-medium">{{ sinhalaEnabled ? 'සිංහල' : 'English' }}</span>
                <button
                    type="button"
                    role="switch"
                    :aria-checked="sinhalaEnabled"
                    @click="toggleSinhala"
                    :class="sinhalaEnabled ? 'bg-primary' : 'bg-muted'"
                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <span
                        :class="sinhalaEnabled ? 'translate-x-4' : 'translate-x-0'"
                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    />
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <Newspaper class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">Basic Information</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Newspaper Name</Label>
                            <Input id="name" v-model="form.name" placeholder="Type newspaper name..." :error="form.errors.name"/>
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="publisher_name">Publisher Name</Label>
                            <Input id="publisher_name" v-model="form.publisher_name" placeholder="Type publisher name..."/>
                            <p v-if="form.errors.publisher_name" class="text-xs text-destructive">{{ form.errors.publisher_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="language">Language</Label>
                            <div class="relative">
                                <Languages class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Select2
                                    id="language"
                                    v-model="form.language"
                                    :options="languageOptions"
                                    placeholder="Select language"
                                    class="pl-9"
                                />
                            </div>
                            <p v-if="form.errors.language" class="text-xs text-destructive">{{ form.errors.language }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="frequency">Publication Frequency</Label>
                            <div class="relative">
                                <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Select2
                                    id="frequency"
                                    v-model="form.frequency"
                                    :options="frequencyOptions"
                                    placeholder="Select frequency"
                                    class="pl-9"
                                />
                            </div>
                            <p v-if="form.errors.frequency" class="text-xs text-destructive">{{ form.errors.frequency }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Pricing -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <span class="flex h-5 w-5 items-center justify-center text-primary text-sm font-bold">Rs.</span>
                        <h3 class="font-bold">Pricing</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="price">Selling Price</Label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 flex items-center justify-center text-muted-foreground text-[10px] font-bold">Rs.</span>
                                <Input id="price" v-model="form.price" class="pl-9" placeholder="0.00" :error="form.errors.price" />
                            </div>
                            <p v-if="form.errors.price" class="text-xs text-destructive">{{ form.errors.price }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="cost_price">Cost Price</Label>
                            <div class="relative">
                                <ShieldAlert class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input id="cost_price" v-model="form.cost_price" class="pl-9" placeholder="0.00" :error="form.errors.cost_price" />
                            </div>
                            <p v-if="form.errors.cost_price" class="text-xs text-destructive">{{ form.errors.cost_price }}</p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <ShieldAlert class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">Status</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="status">Account Status</Label>
                            <Select2 
                                v-model="form.status" 
                                :options="statusOptions"
                                placeholder="Select status"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <Button type="submit" class="w-full rounded-xl py-6 shadow-lg shadow-primary/20" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ newspaper ? 'Update Newspaper' : 'Create Newspaper' }}
                    </Button>
                    <Link href="/admin/newspapers" class="mt-3 block text-center text-sm text-muted-foreground hover:text-foreground">
                        Cancel and return
                    </Link>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>

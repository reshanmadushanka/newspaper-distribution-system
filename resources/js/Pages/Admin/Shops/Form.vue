<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronLeft, Save, Store, MapPin, Phone, Mail, User, ShieldAlert, CreditCard } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'
import { Select2 } from '@/Components/ui/select2'
import { useTranslation } from '@/Composables/useTranslation'

const { t } = useTranslation()


const props = defineProps({
    shop: Object,
    routes: Array,
    statusOptions: Array,
    deliveryOptions: Array,
})


const form = useForm({
    name: props.shop?.name ?? '',
    owner_name: props.shop?.owner_name ?? '',
    phone: props.shop?.phone ?? '',
    email: props.shop?.email ?? '',
    whatsapp_phone: props.shop?.whatsapp_phone ?? '',
    address: props.shop?.address ?? '',
    status: props.shop?.status ?? 'active',
})

const submit = () => {
    if (props.shop) {
        form.put(`/admin/shops/${props.shop.id}`)
    } else {
        form.post('/admin/shops')
    }
}
</script>

<template>
    <Head :title="shop ? t('shops.edit_shop') : t('shops.create_shop')" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/admin/shops">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">{{ shop ? t('shops.edit_shop') : t('shops.create_new') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ shop ? t('shops.fill_update') : t('shops.fill_create') }}</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <Store class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">{{ t('common.basic_info') }}</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">{{ t('shops.shop_name') }}</Label>
                            <Input id="name" v-model="form.name" :placeholder="t('shops.type_shop_name')" :error="form.errors.name"/>
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="sm:col-span-2 space-y-2">
                            <Label for="owner_name">{{ t('shops.owner_name') }}</Label>
                            <div class="relative">
                                <User class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input id="owner_name" v-model="form.owner_name" class="pl-9" :placeholder="t('shops.type_owner_name')"/>
                                <p v-if="form.errors.owner_name" class="text-xs text-destructive">{{ form.errors.owner_name }}</p>
                            </div>
                        </div>
                        <div class="sm:col-span-2 space-y-2">
                            <Label for="address">{{ t('shops.address') }}</Label>
                            <div class="relative">
                                <MapPin class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Textarea id="address" v-model="form.address" class="pl-9 min-h-[100px]" :placeholder="t('shops.type_address')" />
                                <p v-if="form.errors.address" class="text-xs text-destructive">{{ form.errors.address }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact & Delivery -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <Phone class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">{{ t('shops.phone') }} & {{ t('common.optional') }}</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="phone">{{ t('shops.phone') }}</Label>
                            <div class="relative">
                                <Phone class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input id="phone" v-model="form.phone" class="pl-9" :placeholder="t('shops.type_phone')" :error="form.errors.phone" />
                                <p v-if="form.errors.phone" class="text-xs text-destructive">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="whatsapp_phone">WhatsApp {{ t('shops.phone') }}</Label>
                            <div class="relative">
                                <ShieldAlert class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input id="whatsapp_phone" v-model="form.whatsapp_phone" class="pl-9" :placeholder="t('shops.type_phone')" :error="form.errors.whatsapp_phone" />
                                <p v-if="form.errors.whatsapp_phone" class="text-xs text-destructive">{{ form.errors.whatsapp_phone }}</p>
                            </div>
                        </div>
                        <div class="sm:col-span-2 space-y-2">
                            <Label for="email">{{ t('common.email') }}</Label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input id="email" v-model="form.email" class="pl-9" :placeholder="t('auth.email_placeholder')" :error="form.errors.email" />
                                <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Financial & Route -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <CreditCard class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">{{ t('common.financial') }}</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="status">{{ t('newspapers.account_status') }}</Label>
                            <Select2
                                v-model="form.status"
                                :options="statusOptions"
                                :placeholder="t('newspapers.select_status')"
                            />
                        </div>
                    </div>
                </div>


                <!-- Actions -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <Button type="submit" class="w-full rounded-xl py-6 shadow-lg shadow-primary/20" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ shop ? t('shops.update_button') : t('shops.create_button') }}
                    </Button>
                    <Link href="/admin/shops" class="mt-3 block text-center text-sm text-muted-foreground hover:text-foreground">
                        {{ t('shops.cancel_return') }}
                    </Link>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>

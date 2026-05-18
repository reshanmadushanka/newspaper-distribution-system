<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronLeft, Save, Newspaper, Languages, Calendar, DollarSign, ShieldAlert, Type, Plus, Trash2, Tags } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Select2 } from '@/Components/ui/select2'
import { useTranslation } from '@/Composables/useTranslation'
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
    prices: props.newspaper?.prices?.length
        ? props.newspaper.prices.map(p => ({
            id: p.id,
            label: p.label ?? '',
            price: p.price ?? '',
            cost_price: p.cost_price ?? '',
        }))
        : props.newspaper?.price
            ? [{ label: '', price: props.newspaper.price, cost_price: props.newspaper.cost_price ?? '' }]
            : [{ label: '', price: '', cost_price: '' }],
})

const { t } = useTranslation()

const addPriceRow = () => {
    form.prices.push({ label: '', price: '', cost_price: '' })
}

const removePriceRow = (index) => {
    if (form.prices.length > 1) {
        form.prices.splice(index, 1)
    }
}

const submit = () => {
    if (props.newspaper) {
        form.put(`/admin/newspapers/${props.newspaper.id}`)
    } else {
        form.post('/admin/newspapers')
    }
}
</script>

<template>

    <Head :title="newspaper ? t('newspapers.edit') : t('newspapers.create')" />
    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/admin/newspapers">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">{{ newspaper ? t('newspapers.edit') :
                        t('newspapers.create_new') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ newspaper ? t('newspapers.fill_update') :
                        t('newspapers.fill_create') }}</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <Newspaper class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">{{ t('common.basic_info') }}</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">{{ t('newspapers.newspaper_name') }}</Label>
                            <Input id="name" v-model="form.name" :placeholder="t('newspapers.type_newspaper_name')"
                                :error="form.errors.name" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="publisher_name">{{ t('newspapers.publisher_name') }}</Label>
                            <Input id="publisher_name" v-model="form.publisher_name"
                                :placeholder="t('newspapers.type_publisher_name')" />
                            <p v-if="form.errors.publisher_name" class="text-xs text-destructive">{{
                                form.errors.publisher_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="language">{{ t('newspapers.language') }}</Label>
                            <div class="relative">
                                <Languages
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Select2 id="language" v-model="form.language" :options="languageOptions"
                                    :placeholder="t('newspapers.select_language')" class="pl-9" />
                            </div>
                            <p v-if="form.errors.language" class="text-xs text-destructive">{{ form.errors.language }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="frequency">{{ t('newspapers.frequency') }}</Label>
                            <div class="relative">
                                <Calendar
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Select2 id="frequency" v-model="form.frequency" :options="frequencyOptions"
                                    :placeholder="t('newspapers.select_frequency')" class="pl-9" />
                            </div>
                            <p v-if="form.errors.frequency" class="text-xs text-destructive">{{ form.errors.frequency }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Pricing -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between border-b pb-4">
                        <div class="flex items-center gap-2">
                            <DollarSign class="h-5 w-5 text-primary" />
                            <h3 class="font-bold">{{ t('common.pricing') }}</h3>
                        </div>
                        <Button type="button" variant="outline" size="sm" @click="addPriceRow" class="rounded-xl">
                            <Plus class="mr-1 h-4 w-4" /> {{ t('common.add_price') }}
                        </Button>
                    </div>

                    <p v-if="form.errors.prices" class="mb-4 text-xs text-destructive">{{ form.errors.prices }}</p>

                    <div class="space-y-4">
                        <div v-for="(priceRow, index) in form.prices" :key="index"
                            class="rounded-xl border bg-muted/20 p-4 relative">
                            <button type="button" @click="removePriceRow(index)"
                                class="absolute -top-2 -right-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-white hover:bg-destructive/90 transition-colors"
                                :disabled="form.prices.length === 1" v-if="form.prices.length > 1">
                                <Trash2 class="h-3 w-3" />
                            </button>
                            <div class="grid grid-cols-1 gap-3">
                                <div class="space-y-2">
                                    <Label :for="'price_label_' + index">{{ t('common.label') }} <span
                                            class="text-xs text-muted-foreground">({{ t('common.optional')
                                            }})</span></Label>
                                    <div class="relative">
                                        <Tags
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input :id="'price_label_' + index" v-model="priceRow.label" class="pl-9"
                                            :placeholder="t('common.optional')" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label :for="'price_' + index">{{ t('common.selling_price') }}</Label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 flex items-center justify-center text-muted-foreground text-[10px] font-bold">Rs.</span>
                                        <Input :id="'price_' + index" v-model="priceRow.price" class="pl-9"
                                            placeholder="0.00" />
                                    </div>
                                    <p v-if="form.errors[`prices.${index}.price`]" class="text-xs text-destructive">{{
                                        form.errors[`prices.${index}.price`] }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label :for="'cost_price_' + index">{{ t('common.cost_price') }} <span
                                            class="text-xs text-muted-foreground">({{ t('common.optional')
                                            }})</span></Label>
                                    <div class="relative">
                                        <ShieldAlert
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input :id="'cost_price_' + index" v-model="priceRow.cost_price" class="pl-9"
                                            placeholder="0.00" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-2 border-b pb-4">
                        <ShieldAlert class="h-5 w-5 text-primary" />
                        <h3 class="font-bold">{{ t('common.status') }}</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="status">{{ t('newspapers.account_status') }}</Label>
                            <Select2 v-model="form.status" :options="statusOptions"
                                :placeholder="t('newspapers.select_status')" />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <Button type="submit" class="w-full rounded-xl py-6 shadow-lg shadow-primary/20"
                        :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ newspaper ? t('newspapers.update_button') : t('newspapers.create_button') }}
                    </Button>
                    <Link href="/admin/newspapers"
                        class="mt-3 block text-center text-sm text-muted-foreground hover:text-foreground">
                        {{ t('newspapers.cancel_return') }}
                    </Link>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>

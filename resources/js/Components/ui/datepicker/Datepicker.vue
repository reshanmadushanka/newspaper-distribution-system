<script setup>
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    min: { type: String, default: '' },
    class: { type: null, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
let fp = null

const parseDate = (str) => {
    if (!str) return null
    const [y, m, d] = str.split('-').map(Number)
    return new Date(y, m - 1, d)
}

const getMinDate = () => {
    if (props.min) return parseDate(props.min)
    return null
}

onMounted(() => {
    fp = flatpickr(inputRef.value, {
        dateFormat: 'd-m-Y',
        minDate: getMinDate(),
        allowInput: true,
        disableMobile: true,
        defaultDate: parseDate(props.modelValue),
        onChange: (selectedDates) => {
            if (selectedDates.length > 0) {
                const d = selectedDates[0]
                const y = d.getFullYear()
                const m = String(d.getMonth() + 1).padStart(2, '0')
                const day = String(d.getDate()).padStart(2, '0')
                emit('update:modelValue', `${y}-${m}-${day}`)
            }
        },
    })
})

onUnmounted(() => {
    fp?.destroy()
})
</script>

<template>
    <input
        ref="inputRef"
        type="text"
        placeholder="DD-MM-YYYY"
        :class="props.class"
        readonly
    />
</template>

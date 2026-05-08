<script setup>
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { ref, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    min: { type: String, default: '' },
    class: { type: null, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
let fp = null

const getMinDate = () => {
    if (props.min) {
        const [y, m, d] = props.min.split('-').map(Number)
        return new Date(y, m - 1, d)
    }
    const date = new Date()
    date.setDate(date.getDate() + 1)
    return date
}

onMounted(() => {
    fp = flatpickr(inputRef.value, {
        dateFormat: 'd-m-Y',
        minDate: getMinDate(),
        allowInput: true,
        disableMobile: true,
        defaultDate: props.modelValue ? flatpickr.parseDate(props.modelValue, 'Y-m-d') : null,
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

watch(() => props.modelValue, (val) => {
    if (fp && val) {
        fp.setDate(val, false)
    } else if (fp) {
        fp.clear()
    }
})

watch(() => props.min, () => {
    if (fp) {
        fp.set('minDate', getMinDate())
    }
})
</script>

<template>
    <input ref="inputRef" type="text" :class="props.class" placeholder="DD-MM-YYYY" readonly />
</template>

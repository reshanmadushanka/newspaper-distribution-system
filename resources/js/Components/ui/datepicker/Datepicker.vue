<script setup>
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    modelValue: { type: [String, Array], default: '' },
    min: { type: String, default: '' },
    mode: { type: String, default: 'single' },
    placeholder: { type: String, default: 'DD-MM-YYYY' },
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

const formatDate = (date) => {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

const getDefaultDate = () => {
    if (props.mode === 'range' && Array.isArray(props.modelValue)) {
        return props.modelValue.map(parseDate).filter(Boolean)
    }

    return parseDate(props.modelValue)
}

onMounted(() => {
    fp = flatpickr(inputRef.value, {
        dateFormat: 'd-m-Y',
        mode: props.mode,
        minDate: getMinDate(),
        allowInput: true,
        disableMobile: true,
        defaultDate: getDefaultDate(),
        onChange: (selectedDates) => {
            if (props.mode === 'range') {
                emit('update:modelValue', selectedDates.map(formatDate))
                return
            }

            if (selectedDates.length > 0) {
                emit('update:modelValue', formatDate(selectedDates[0]))
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
        :placeholder="props.placeholder"
        :class="props.class"
        readonly
    />
</template>

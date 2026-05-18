import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { createAutoAttach } from '@siyabasa/singlish'

export function useSinhalaInput() {
    const enabled = ref(false)
    const page = usePage()
    const currentLocale = computed(() => page.props.locale)
    let autoAttachInstance = null

    // Auto-enable when locale is Sinhala
    const updateFromLocale = () => {
        const isSinhala = currentLocale.value === 'si'

        console.log('Locale check:', currentLocale.value, 'Is Sinhala:', isSinhala, 'Currently enabled:', enabled.value)

        if (isSinhala && !enabled.value) {
            console.log('Enabling Sinhala input')
            enabled.value = true
            if (autoAttachInstance) {
                autoAttachInstance.start()
            }
        } else if (!isSinhala && enabled.value) {
            console.log('Disabling Sinhala input')
            enabled.value = false
            if (autoAttachInstance) {
                autoAttachInstance.stop()
            }
        }
    }

    const toggle = () => {
        if (!autoAttachInstance) return
        autoAttachInstance.toggle()
        enabled.value = autoAttachInstance.isRunning()
    }

    const start = () => {
        if (!autoAttachInstance) return
        autoAttachInstance.start()
        enabled.value = true
    }

    const stop = () => {
        if (!autoAttachInstance) return
        autoAttachInstance.stop()
        enabled.value = false
    }

    onMounted(async () => {
        // Initialize autoAttach with no UI
        autoAttachInstance = createAutoAttach({
            enabled: false,
            selector: 'input[type="text"], textarea',
            showUI: false, // Disable the UI overlay
        })

        // Auto-enable if locale is Sinhala
        updateFromLocale()

        // Watch for locale changes
        watch(currentLocale, () => {
            updateFromLocale()
        }, { immediate: true })
    })

    onBeforeUnmount(() => {
        if (autoAttachInstance) {
            autoAttachInstance.stop()
            autoAttachInstance = null
        }
    })

    return { enabled, toggle, start, stop }
}

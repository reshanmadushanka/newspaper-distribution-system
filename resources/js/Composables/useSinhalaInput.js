import { ref, onMounted, onBeforeUnmount } from 'vue'
import { createAutoAttach } from '@siyabasa/singlish'

export function useSinhalaInput() {
    const enabled = ref(false)
    let autoAttach = null

    const toggle = () => {
        if (!autoAttach) return
        autoAttach.toggle()
        enabled.value = autoAttach.isRunning()
    }

    const start = () => {
        if (!autoAttach) return
        autoAttach.start()
        enabled.value = true
    }

    const stop = () => {
        if (!autoAttach) return
        autoAttach.stop()
        enabled.value = false
    }

    onMounted(() => {
        autoAttach = createAutoAttach({
            enabled: false,
            selector: 'input[type="text"], input[type="search"], textarea, input:not([type])',
        })
    })

    onBeforeUnmount(() => {
        if (autoAttach) autoAttach.stop()
    })

    return { enabled, toggle, start, stop }
}

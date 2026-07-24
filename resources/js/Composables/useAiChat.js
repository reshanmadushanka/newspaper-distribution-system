import { ref, computed } from 'vue'

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (meta) return meta

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
    return match ? decodeURIComponent(match[1]) : ''
}

export function useAiChat() {
    const messages = ref([])
    const conversationId = ref(null)
    const isSending = ref(false)
    const error = ref(null)
    const progress = ref(null)
    let progressTimer = null

    const hasMessages = computed(() => messages.value.length > 0)

    function resetConversation() {
        stopProgressPolling()
        messages.value = []
        conversationId.value = null
        error.value = null
        progress.value = null
    }

    function pushMessage({ role, content, actions = null, local = false }) {
        messages.value.push({
            id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            role,
            content,
            actions: actions || [],
            local,
        })
    }

    async function sendMessage(text) {
        const message = (text || '').trim()
        if (!message || isSending.value) return

        error.value = null
        isSending.value = true
        pushMessage({ role: 'user', content: message, local: true })

        try {
            const response = await fetch('/admin/chat/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken() || '',
                    'X-XSRF-TOKEN': csrfToken() || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message,
                    conversation_id: conversationId.value,
                }),
            })

            const data = await response.json().catch(() => ({}))

            if (!response.ok) {
                throw new Error(data.message || `Request failed (${response.status})`)
            }

            conversationId.value = data.conversation_id ?? conversationId.value
            pushMessage({
                role: 'assistant',
                content: data.reply || '',
                actions: data.actions || [],
            })

            handleActions(data.actions || [])
        } catch (e) {
            error.value = e.message || 'Request failed'
            pushMessage({
                role: 'assistant',
                content: e.message || 'Something went wrong.',
                local: true,
            })
        } finally {
            isSending.value = false
        }
    }

    function handleActions(actions) {
        for (const action of actions) {
            if (action.type === 'auto_generate_started' || action.type === 'auto_generate_progress') {
                startProgressPolling()
            }
            if (action.type === 'auto_generate_result') {
                progress.value = action.payload || null
                stopProgressPolling()
            }
            if (action.type === 'auto_generate_preview') {
                // Preview rendered via action card; no poll
            }
        }
    }

    function startProgressPolling() {
        stopProgressPolling()
        pollProgress()
        progressTimer = setInterval(pollProgress, 2000)
    }

    function stopProgressPolling() {
        if (progressTimer) {
            clearInterval(progressTimer)
            progressTimer = null
        }
    }

    async function pollProgress() {
        try {
            const response = await fetch('/admin/invoices/auto-generate/progress', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
            if (!response.ok) return
            const data = await response.json()
            progress.value = data

            const done =
                data.status === 'completed' ||
                (data.total > 0 && data.processed >= data.total)

            if (done) {
                stopProgressPolling()
                // Attach a result action to the last assistant message if needed
                const last = [...messages.value].reverse().find((m) => m.role === 'assistant')
                if (last) {
                    const hasResult = (last.actions || []).some((a) => a.type === 'auto_generate_result')
                    if (!hasResult) {
                        last.actions = [
                            ...(last.actions || []),
                            { type: 'auto_generate_result', payload: data },
                        ]
                    }
                }
            }
        } catch {
            // ignore transient poll errors
        }
    }

    /**
     * Confirm generation via chat (natural language) so Python/Laravel tool path is used.
     */
    async function confirmAutoGenerate(date) {
        if (!date) return
        await sendMessage(`Yes, confirm and start auto-generating invoices for ${date}.`)
    }

    return {
        messages,
        conversationId,
        isSending,
        error,
        progress,
        hasMessages,
        sendMessage,
        confirmAutoGenerate,
        resetConversation,
        stopProgressPolling,
    }
}

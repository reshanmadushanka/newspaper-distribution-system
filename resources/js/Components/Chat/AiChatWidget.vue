<script setup>
import { ref, computed, nextTick, watch, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    MessageCircle, X, Send, Sparkles, Plus, Loader2,
} from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { useTranslation } from '@/Composables/useTranslation'
import { useAiChat } from '@/Composables/useAiChat'
import ChatActionCard from '@/Components/Chat/ChatActionCard.vue'

const page = usePage()
const { t } = useTranslation()
const permissions = computed(() => page.props.auth?.user?.permissions ?? [])

const canUseChat = computed(() => permissions.value.includes('use ai chat'))
const canGenerate = computed(() =>
    permissions.value.includes('auto generate invoice')
    || permissions.value.includes('manage invoices')
)

const isOpen = ref(false)
const draft = ref('')
const listEl = ref(null)

const {
    messages,
    isSending,
    error,
    sendMessage,
    confirmAutoGenerate,
    resetConversation,
    stopProgressPolling,
} = useAiChat()

const toggle = () => {
    isOpen.value = !isOpen.value
}

const close = () => {
    isOpen.value = false
}

const newChat = () => {
    resetConversation()
    draft.value = ''
}

const scrollToBottom = async () => {
    await nextTick()
    if (listEl.value) {
        listEl.value.scrollTop = listEl.value.scrollHeight
    }
}

watch(messages, () => scrollToBottom(), { deep: true })
watch(isSending, () => scrollToBottom())

const onSubmit = async () => {
    const text = draft.value
    if (!text.trim() || isSending.value) return
    draft.value = ''
    await sendMessage(text)
}

const onKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        onSubmit()
    }
}

const onConfirmGenerate = async (date) => {
    await confirmAutoGenerate(date)
}

const onCancelGenerate = () => {
    // no-op; user can continue chatting
}

onUnmounted(() => {
    stopProgressPolling()
})
</script>

<template>
    <div v-if="canUseChat" class="pointer-events-none fixed bottom-5 right-5 z-[60] flex flex-col items-end gap-3">
        <!-- Panel -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-3 scale-95"
        >
            <div
                v-if="isOpen"
                class="pointer-events-auto flex h-[min(32rem,calc(100vh-6rem))] w-[min(24rem,calc(100vw-1.5rem))] flex-col overflow-hidden rounded-2xl border bg-card shadow-2xl shadow-black/20"
            >
                <!-- Header -->
                <div class="flex items-center justify-between gap-2 border-b bg-primary px-4 py-3 text-primary-foreground">
                    <div class="flex min-w-0 items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15">
                            <Sparkles class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold leading-tight">{{ t('chat.title') }}</p>
                            <p class="truncate text-xs text-primary-foreground/80">{{ t('chat.subtitle') }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button
                            type="button"
                            class="rounded-lg p-1.5 hover:bg-white/15"
                            :title="t('chat.new_chat')"
                            @click="newChat"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 hover:bg-white/15"
                            :title="t('chat.close')"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div ref="listEl" class="flex-1 space-y-3 overflow-y-auto p-4">
                    <div v-if="messages.length === 0" class="rounded-xl border border-dashed bg-secondary/30 p-4 text-sm text-muted-foreground">
                        {{ t('chat.empty') }}
                    </div>

                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex"
                        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[92%] rounded-2xl px-3 py-2 text-sm leading-relaxed shadow-sm"
                            :class="msg.role === 'user'
                                ? 'rounded-br-md bg-primary text-primary-foreground'
                                : 'rounded-bl-md border bg-background text-foreground'"
                        >
                            <p class="whitespace-pre-wrap break-words">{{ msg.content }}</p>
                            <template v-if="msg.actions?.length">
                                <ChatActionCard
                                    v-for="(action, idx) in msg.actions"
                                    :key="idx"
                                    :action="action"
                                    :can-generate="canGenerate"
                                    :is-sending="isSending"
                                    @confirm="onConfirmGenerate"
                                    @cancel="onCancelGenerate"
                                />
                            </template>
                        </div>
                    </div>

                    <div v-if="isSending" class="flex items-center gap-2 text-xs text-muted-foreground">
                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                        {{ t('chat.thinking') }}
                    </div>

                    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
                </div>

                <!-- Composer -->
                <div class="border-t bg-card p-3">
                    <div class="flex items-end gap-2">
                        <textarea
                            v-model="draft"
                            rows="1"
                            class="max-h-28 min-h-10 flex-1 resize-none rounded-xl border bg-secondary/40 px-3 py-2 text-sm outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10"
                            :placeholder="t('chat.placeholder')"
                            :disabled="isSending"
                            @keydown="onKeydown"
                        />
                        <Button
                            size="sm"
                            class="h-10 w-10 shrink-0 rounded-xl p-0"
                            :disabled="isSending || !draft.trim()"
                            :aria-label="t('chat.send')"
                            @click="onSubmit"
                        >
                            <Loader2 v-if="isSending" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- FAB -->
        <button
            type="button"
            class="pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/30 transition hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/30"
            :title="t('chat.open')"
            :aria-label="t('chat.open')"
            @click="toggle"
        >
            <X v-if="isOpen" class="h-6 w-6" />
            <MessageCircle v-else class="h-6 w-6" />
        </button>
    </div>
</template>

<div
    x-data="chatInterface(@js($conversationId), @js(route('chat.send')), @js($initialMessage), @js($messages), @js(auth()->id()))"
    x-init="init()"
    class="flex h-full flex-col"
>
    {{-- Messages --}}
    <div
        x-ref="messages"
        class="flex-1 overflow-y-auto px-4 py-6"
    >
        <template x-if="messages.length === 0 && !isStreaming">
            <div class="flex h-full items-center justify-center">
                <div class="text-center">
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        Start a conversation...
                    </p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-3xl space-y-6">
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    {{-- User message --}}
                    <template x-if="msg.role === 'user'">
                        <div class="flex justify-end">
                            <div class="max-w-[80%] rounded-2xl rounded-br-md bg-primary-600 px-4 py-3 text-sm text-white">
                                <span x-text="msg.content"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Assistant message --}}
                    <template x-if="msg.role === 'assistant'">
                        <div class="flex justify-start">
                            <div class="prose prose-sm dark:prose-invert max-w-[80%] rounded-2xl rounded-bl-md bg-gray-100 px-4 py-3 text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                <div x-html="msg.content"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Pending action cards --}}
                    <template x-if="msg.pending_actions && msg.pending_actions.length > 0">
                        <div class="mt-3 space-y-3">
                            <template x-for="action in msg.pending_actions" :key="action.pending_action_id">
                                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                                            :class="{
                                                'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400': action.operation === 'create',
                                                'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400': action.operation === 'update',
                                                'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400': action.operation === 'delete',
                                            }"
                                            x-text="action.operation.charAt(0).toUpperCase() + action.operation.slice(1)"
                                        ></span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="action.display?.summary"></span>
                                    </div>

                                    <div class="mt-2 space-y-1">
                                        <template x-for="field in (action.display?.fields || [])" :key="field.label">
                                            <div class="flex gap-2 text-sm">
                                                <span class="font-medium text-gray-500 dark:text-gray-400" x-text="field.label + ':'"></span>
                                                <span class="text-gray-900 dark:text-white" x-text="field.new || field.value"></span>
                                                <template x-if="field.old">
                                                    <span class="text-gray-400 line-through" x-text="field.old"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Action buttons --}}
                                    <template x-if="action.status === 'pending'">
                                        <div class="mt-3 flex gap-2">
                                            <button
                                                x-on:click="approveAction(action)"
                                                class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                            >
                                                <x-heroicon-o-check class="h-3.5 w-3.5" />
                                                Approve
                                            </button>
                                            <button
                                                x-on:click="rejectAction(action)"
                                                class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                                            >
                                                <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                                                Reject
                                            </button>
                                        </div>
                                    </template>

                                    {{-- Error state --}}
                                    <template x-if="action.error">
                                        <div class="mt-2 text-xs text-red-600 dark:text-red-400" x-text="action.error"></div>
                                    </template>

                                    {{-- Resolved state --}}
                                    <template x-if="action.status !== 'pending' && !action.error">
                                        <div class="mt-3">
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                                                :class="{
                                                    'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400': action.status === 'approved',
                                                    'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400': action.status === 'rejected',
                                                    'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-400': action.status === 'expired',
                                                }"
                                                x-text="action.status.charAt(0).toUpperCase() + action.status.slice(1)"
                                            ></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Streaming indicator --}}
            <template x-if="isStreaming">
                <div class="flex justify-start">
                    <div class="rounded-2xl rounded-bl-md bg-gray-100 px-4 py-3 dark:bg-gray-800">
                        <div class="flex items-center gap-1">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s]"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s]"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Input area --}}
    <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
        <div class="mx-auto max-w-3xl">
            <form x-on:submit.prevent="sendMessage()" class="relative">
                <textarea
                    x-model="input"
                    x-ref="chatInput"
                    @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                    placeholder="Ask anything..."
                    rows="1"
                    class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-3 pr-12 text-sm shadow-sm transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    :disabled="isStreaming"
                ></textarea>
                <div class="absolute bottom-3 right-3">
                    <button
                        type="submit"
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white transition hover:bg-primary-700 disabled:opacity-40"
                        :disabled="isStreaming || !input.trim()"
                    >
                        <x-heroicon-s-arrow-up class="h-4 w-4" />
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('chatInterface', (initialConversationId, sendUrl, initialMessage, initialMessages, userId) => ({
    conversationId: initialConversationId,
    messages: initialMessages || [],
    input: '',
    isStreaming: false,
    channel: null,

    init() {
        this.setupEchoListener();

        if (initialMessage) {
            this.$nextTick(() => {
                this.input = initialMessage;
                this.sendMessage();
            });
        }
    },

    setupEchoListener() {
        if (!window.Echo) return;

        this.channel = window.Echo.private(`chat.${userId}`)
            .listen('.text_delta', (e) => this.handleTextDelta(e))
            .listen('.tool_result', (e) => this.handleToolResult(e))
            .listen('.stream_end', () => this.handleStreamEnd())
            .listen('.conversation.resolved', (e) => this.handleConversationResolved(e));
    },

    async sendMessage() {
        const text = this.input.trim();
        if (!text || this.isStreaming) return;

        this.messages.push({ role: 'user', content: text });
        this.input = '';
        this.isStreaming = true;

        this.messages.push({ role: 'assistant', content: '', pending_actions: [] });

        const url = this.conversationId
            ? sendUrl.replace(/\/$/, '') + '/' + this.conversationId
            : sendUrl;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ message: text }),
            });

            if (!response.ok) {
                const body = await response.json();
                const assistantMsg = this.messages[this.messages.length - 1];
                assistantMsg.content = body.message || `Error ${response.status}: ${response.statusText}`;
                this.isStreaming = false;
            }
        } catch {
            const assistantMsg = this.messages[this.messages.length - 1];
            assistantMsg.content = 'Network error. Please try again.';
            this.isStreaming = false;
        }

        this.scrollToBottom();
    },

    handleTextDelta(event) {
        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant') {
            assistantMsg.content += event.delta || '';
            this.scrollToBottom();
        }
    },

    handleToolResult(event) {
        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant' && event.result) {
            try {
                const result = typeof event.result === 'string' ? JSON.parse(event.result) : event.result;
                if (result.type === 'pending_action') {
                    result.status = 'pending';
                    assistantMsg.pending_actions.push(result);
                    this.scrollToBottom();
                }
            } catch { /* not pending action JSON */ }
        }
    },

    handleStreamEnd() {
        this.isStreaming = false;
        this.scrollToBottom();
    },

    handleConversationResolved(event) {
        this.conversationId = event.conversationId;

        const path = window.location.pathname
            .replace(/\/chats\/.*$/, '/chats/' + event.conversationId)
            .replace(/\/chats\/?$/, '/chats/' + event.conversationId);
        history.replaceState(null, '', path);

        window.dispatchEvent(new CustomEvent('chat:conversation-created', {
            detail: { id: event.conversationId }
        }));
    },

    async approveAction(action) {
        try {
            const res = await fetch(@js(url('/chat/actions')) + '/' + action.pending_action_id + '/approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (res.ok) {
                action.status = 'approved';
            } else {
                const body = await res.json();
                action.error = body.error || 'Failed to approve';
            }
        } catch {
            action.error = 'Network error';
        }
    },

    async rejectAction(action) {
        try {
            const res = await fetch(@js(url('/chat/actions')) + '/' + action.pending_action_id + '/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (res.ok) {
                action.status = 'rejected';
            } else {
                const body = await res.json();
                action.error = body.error || 'Failed to reject';
            }
        } catch {
            action.error = 'Network error';
        }
    },

    scrollToBottom() {
        this.$nextTick(() => {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        });
    },
}));
</script>
@endscript

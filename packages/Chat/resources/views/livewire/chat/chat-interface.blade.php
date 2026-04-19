<div
    x-data="chatInterface(@js($conversationId), @js(route('chat.send')), @js($initialMessage), @js($messages), @js(auth()->id()))"
    x-init="init()"
    class="flex h-full flex-col"
>
    {{-- Messages --}}
    <div
        x-ref="messages"
        role="log"
        aria-live="polite"
        aria-relevant="additions text"
        aria-atomic="false"
        class="flex-1 overflow-y-auto px-4 py-6"
    >
        <template x-if="messages.length === 0 && !isStreaming">
            <div class="flex h-full items-center justify-center px-6">
                <div class="mx-auto max-w-md text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                        <x-heroicon-o-sparkles class="h-6 w-6" />
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        How can I help?
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ask about your CRM data, or try one of these:
                    </p>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <template x-for="prompt in starterPrompts" :key="prompt">
                            <button
                                type="button"
                                x-on:click="input = prompt; $nextTick(() => sendMessage())"
                                x-text="prompt"
                                class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-700 dark:hover:bg-primary-900/20 dark:hover:text-primary-300"
                            ></button>
                        </template>
                    </div>
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
                            <div
                                class="prose prose-sm dark:prose-invert max-w-[85%] rounded-2xl rounded-bl-md bg-white px-4 py-3 text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 prose-p:my-2 prose-headings:mb-2 prose-headings:mt-3 prose-headings:text-gray-900 dark:prose-headings:text-white prose-pre:my-2 prose-ul:my-2 prose-ol:my-2 prose-li:my-0.5 prose-table:my-2 prose-table:border-collapse prose-thead:border-b prose-thead:border-gray-300 dark:prose-thead:border-gray-600 prose-th:px-2 prose-th:py-1 prose-th:text-left prose-td:border-t prose-td:border-gray-100 prose-td:px-2 prose-td:py-1 dark:prose-td:border-gray-700 prose-code:rounded prose-code:bg-gray-100 prose-code:px-1 prose-code:py-0.5 prose-code:text-[0.85em] prose-code:before:content-none prose-code:after:content-none dark:prose-code:bg-gray-900 prose-pre:rounded-lg prose-pre:bg-gray-900 prose-pre:text-gray-100 first:prose-headings:mt-0"
                                x-html="window.renderMarkdown(msg.content)"
                            ></div>
                        </div>
                    </template>

                    {{-- Paywall card for credits_exhausted state --}}
                    <template x-if="msg.paywall">
                        <div class="flex justify-start">
                            <div class="flex max-w-[85%] flex-col gap-3 rounded-2xl rounded-bl-md border border-amber-200 bg-amber-50 px-4 py-4 dark:border-amber-900/50 dark:bg-amber-900/10">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-sparkles class="h-5 w-5 text-amber-600 dark:text-amber-400" aria-hidden="true" />
                                    <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100" x-text="msg.paywall.heading"></h4>
                                </div>
                                <p class="text-sm text-amber-800 dark:text-amber-200" x-text="msg.paywall.body"></p>
                                <div class="flex gap-2">
                                    <a :href="msg.paywall.upgrade_url" class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700">
                                        Add credits
                                    </a>
                                </div>
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

            {{-- Streaming indicator (only before first token arrives) --}}
            <template x-if="isStreaming && (messages.length === 0 || messages[messages.length-1].role !== 'assistant' || !messages[messages.length-1].content)">
                <div class="flex justify-start" aria-label="Assistant is typing" role="status">
                    <div class="rounded-2xl rounded-bl-md bg-white px-4 py-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div class="flex items-center gap-1.5 motion-reduce:animate-none" aria-hidden="true">
                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s] motion-reduce:animate-none"></span>
                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s] motion-reduce:animate-none"></span>
                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 motion-reduce:animate-none"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Input area --}}
    <div class="border-t border-gray-200 bg-white px-4 py-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="mx-auto max-w-3xl">
            <form x-on:submit.prevent="sendMessage()">
                <div class="relative flex items-end gap-2 rounded-2xl border border-gray-200 bg-white px-3 py-2 shadow-sm transition focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800">
                    <label for="chat-message-input" class="sr-only">Message the assistant</label>
                    <textarea
                        id="chat-message-input"
                        x-model="input"
                        x-ref="chatInput"
                        @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage() }"
                        @input="autosize($event.target)"
                        placeholder="Ask anything..."
                        rows="1"
                        aria-label="Message the assistant"
                        class="block min-h-[28px] w-full resize-none border-0 bg-transparent px-1 py-1 text-sm leading-6 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                        style="max-height: 200px;"
                        :disabled="isStreaming"
                    ></textarea>
                    <button
                        type="submit"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400 disabled:shadow-none dark:disabled:bg-gray-700 dark:disabled:text-gray-500"
                        :disabled="isStreaming || !input.trim() || input.length > 5000"
                        aria-label="Send message"
                    >
                        <template x-if="!isStreaming">
                            <x-heroicon-s-arrow-up class="h-4 w-4" />
                        </template>
                        <template x-if="isStreaming">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                    </button>
                </div>
                <div class="mt-1.5 flex items-center justify-between px-1 text-[11px] text-gray-400 dark:text-gray-500">
                    <div>
                        <kbd class="rounded border border-gray-200 bg-gray-50 px-1 py-0.5 font-sans text-[10px] dark:border-gray-700 dark:bg-gray-900">Enter</kbd> to send
                        <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                        <kbd class="rounded border border-gray-200 bg-gray-50 px-1 py-0.5 font-sans text-[10px] dark:border-gray-700 dark:bg-gray-900">Shift</kbd>
                        +
                        <kbd class="rounded border border-gray-200 bg-gray-50 px-1 py-0.5 font-sans text-[10px] dark:border-gray-700 dark:bg-gray-900">Enter</kbd>
                        for newline
                    </div>
                    <span
                        x-show="input.length > 4000"
                        x-text="`${input.length.toLocaleString()} / 5,000`"
                        :class="{
                            'text-gray-500 dark:text-gray-400': input.length <= 4900,
                            'text-amber-600 dark:text-amber-400': input.length > 4900 && input.length <= 5000,
                            'text-red-600 dark:text-red-400': input.length > 5000,
                        }"
                        aria-live="polite"
                    ></span>
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
    streamTimeoutId: null,
    streamTimeoutMs: 60000,

    starterPrompts: [
        'Give me a CRM overview',
        'Show overdue tasks',
        'Recent companies',
        'Pipeline summary',
    ],

    autosize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 200) + 'px';
    },

    init() {
        if (this.conversationId) {
            this.subscribeToConversation(this.conversationId);
        }

        if (initialMessage) {
            this.$nextTick(() => {
                this.input = initialMessage;
                this.sendMessage();
            });
        }
    },

    destroy() {
        this.clearStreamTimeout();
        this.unsubscribe();
    },

    unsubscribe() {
        if (this.channel && window.Echo) {
            window.Echo.leave(this.channel.name);
            this.channel = null;
        }
    },

    subscribeToConversation(conversationId) {
        if (!window.Echo) return;
        if (this.channel && this.channel.conversationId === conversationId) return;

        this.unsubscribe();

        const channelName = `chat.conversation.${conversationId}`;
        this.channel = window.Echo.private(channelName);
        this.channel.name = channelName;
        this.channel.conversationId = conversationId;

        this.channel
            .listen('.text_delta', (e) => this.handleTextDelta(e))
            .listen('.tool_result', (e) => this.handleToolResult(e))
            .listen('.stream_end', () => this.handleStreamEnd())
            .listen('.stream.failed', (e) => this.handleStreamFailed(e))
            .listen('.conversation.resolved', (e) => this.handleConversationResolved(e));
    },

    startStreamTimeout() {
        this.clearStreamTimeout();
        this.streamTimeoutId = setTimeout(() => {
            if (!this.isStreaming) return;
            const assistantMsg = this.messages[this.messages.length - 1];
            if (assistantMsg?.role === 'assistant' && !assistantMsg.content) {
                assistantMsg.content = 'The assistant took too long to respond. Please try again.';
            }
            this.isStreaming = false;
        }, this.streamTimeoutMs);
    },

    clearStreamTimeout() {
        if (this.streamTimeoutId) {
            clearTimeout(this.streamTimeoutId);
            this.streamTimeoutId = null;
        }
    },

    async sendMessage() {
        const text = this.input.trim();
        if (!text || this.isStreaming) return;

        this.messages.push({ role: 'user', content: text });
        this.input = '';
        this.isStreaming = true;

        this.messages.push({ role: 'assistant', content: '', pending_actions: [], paywall: null });

        const url = this.conversationId
            ? sendUrl.replace(/\/$/, '') + '/' + this.conversationId
            : sendUrl;

        this.startStreamTimeout();

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
                const body = await response.json().catch(() => ({}));
                const assistantMsg = this.messages[this.messages.length - 1];

                if (response.status === 402 && body?.error === 'credits_exhausted') {
                    const resetLabel = body.reset_at ? new Date(body.reset_at).toLocaleDateString() : null;
                    assistantMsg.paywall = {
                        heading: "You've used all your AI credits",
                        body: resetLabel ? `Your plan resets on ${resetLabel}.` : 'Add credits to keep chatting.',
                        upgrade_url: body.upgrade_url || '/app',
                    };
                    assistantMsg.content = '';
                } else {
                    assistantMsg.content = body.message || `Error ${response.status}: ${response.statusText}`;
                }

                this.isStreaming = false;
                this.clearStreamTimeout();
                return;
            }

            const body = await response.json();
            if (body.conversation_id && !this.conversationId) {
                this.conversationId = body.conversation_id;
                this.subscribeToConversation(body.conversation_id);

                const url = new URL(window.location.href);
                url.pathname = url.pathname
                    .replace(/\/chats\/.*$/, `/chats/${body.conversation_id}`)
                    .replace(/\/chats\/?$/, `/chats/${body.conversation_id}`);
                url.search = '';
                url.hash = '';
                history.replaceState(null, '', url.toString());

                window.dispatchEvent(new CustomEvent('chat:conversation-created', {
                    detail: { id: body.conversation_id }
                }));
            }
        } catch {
            const assistantMsg = this.messages[this.messages.length - 1];
            assistantMsg.content = 'Network error. Please try again.';
            this.isStreaming = false;
            this.clearStreamTimeout();
        }

        this.scrollToBottom();
    },

    handleTextDelta(event) {
        this.startStreamTimeout();
        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant') {
            assistantMsg.content += event.delta || '';
            this.scrollToBottom();
        }
    },

    handleToolResult(event) {
        this.startStreamTimeout();
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
        this.clearStreamTimeout();
        this.scrollToBottom();
    },

    handleStreamFailed(event) {
        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant' && !assistantMsg.content) {
            assistantMsg.content = event?.message || 'The assistant encountered an error. Please try again.';
        }
        this.isStreaming = false;
        this.clearStreamTimeout();
    },

    handleConversationResolved(event) {
        if (!event?.conversationId) return;
        if (!this.conversationId) {
            this.conversationId = event.conversationId;
        }
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

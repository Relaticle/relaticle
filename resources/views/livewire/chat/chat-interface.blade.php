<div
    x-data="chatInterface(@js($conversationId), @js(route('chat.send')))"
    x-init="init()"
    class="flex h-[calc(100vh-12rem)] flex-col"
>
    {{-- Conversation sidebar --}}
    <div class="flex flex-1 overflow-hidden">
        {{-- Sidebar --}}
        <div class="hidden w-64 flex-shrink-0 overflow-y-auto border-r border-gray-200 dark:border-gray-700 lg:block">
            <div class="p-4">
                <button
                    x-on:click="startNew()"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
                >
                    <x-heroicon-o-plus class="h-4 w-4" />
                    New Chat
                </button>
            </div>
            <div class="space-y-1 px-2">
                <template x-for="conv in conversations" :key="conv.id">
                    <button
                        x-on:click="loadConversation(conv.id)"
                        x-text="conv.title || 'Untitled'"
                        class="w-full truncate rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        :class="{ 'bg-gray-100 dark:bg-gray-800': conversationId === conv.id }"
                    ></button>
                </template>
            </div>
        </div>

        {{-- Main chat area --}}
        <div class="flex flex-1 flex-col">
            {{-- Messages --}}
            <div
                x-ref="messages"
                class="flex-1 overflow-y-auto px-4 py-6"
            >
                <template x-if="messages.length === 0">
                    <div class="flex h-full items-center justify-center">
                        <div class="text-center">
                            <x-heroicon-o-chat-bubble-left-right class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                No messages yet
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Ask anything about your CRM data.
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
                                    <div class="max-w-[80%] rounded-2xl rounded-bl-md bg-gray-100 px-4 py-3 text-sm text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                        <div x-html="renderMarkdown(msg.content)"></div>
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

                                            {{-- Resolved state --}}
                                            <template x-if="action.status !== 'pending'">
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
                    <form x-on:submit.prevent="sendMessage()" class="flex gap-3">
                        <input
                            x-model="input"
                            type="text"
                            placeholder="Ask anything about your CRM..."
                            class="flex-1 rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            :disabled="isStreaming"
                            x-ref="chatInput"
                        />
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-50"
                            :disabled="isStreaming || !input.trim()"
                        >
                            <x-heroicon-o-paper-airplane class="h-5 w-5" />
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('chatInterface', (initialConversationId, sendUrl) => ({
    conversationId: initialConversationId,
    conversations: [],
    messages: [],
    input: '',
    isStreaming: false,

    init() {
        this.fetchConversations();
        if (this.conversationId) {
            this.$wire.loadConversation();
        }
    },

    async fetchConversations() {
        try {
            const res = await fetch(@js(route('chat.conversations')));
            const data = await res.json();
            this.conversations = data.data || [];
        } catch (e) {
            console.error('Failed to load conversations', e);
        }
    },

    startNew() {
        this.conversationId = null;
        this.messages = [];
        this.$wire.startNewConversation();
    },

    loadConversation(id) {
        this.conversationId = id;
        this.$wire.set('conversationId', id);
        this.$wire.loadConversation();
    },

    async sendMessage() {
        const text = this.input.trim();
        if (!text || this.isStreaming) return;

        this.messages.push({ role: 'user', content: text });
        this.input = '';
        this.isStreaming = true;

        const assistantMsg = { role: 'assistant', content: '', pending_actions: [] };
        this.messages.push(assistantMsg);

        const url = this.conversationId
            ? sendUrl.replace(/\/$/, '') + '/' + this.conversationId
            : sendUrl;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ message: text }),
            });

            if (!response.ok) {
                const err = await response.json();
                assistantMsg.content = err.message || 'Something went wrong.';
                this.isStreaming = false;
                return;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const payload = line.slice(6).trim();
                    if (payload === '[DONE]') continue;

                    try {
                        const event = JSON.parse(payload);
                        this.handleEvent(event, assistantMsg);
                    } catch (e) {
                        // Non-JSON data line, append as text
                        assistantMsg.content += payload;
                    }
                }
            }
        } catch (e) {
            assistantMsg.content = 'Connection error. Please try again.';
            console.error('Stream error', e);
        }

        this.isStreaming = false;
        this.scrollToBottom();
        this.fetchConversations();
    },

    handleEvent(event, assistantMsg) {
        if (event.type === 'text_delta' || event.delta) {
            assistantMsg.content += event.delta || event.text || '';
            this.scrollToBottom();
        } else if (event.type === 'pending_action') {
            event.status = 'pending';
            assistantMsg.pending_actions.push(event);
        } else if (event.type === 'conversation_id' && event.id) {
            this.conversationId = event.id;
            this.$wire.set('conversationId', event.id);
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
            const data = await res.json();
            action.status = 'approved';
        } catch (e) {
            console.error('Approve failed', e);
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
            action.status = 'rejected';
        } catch (e) {
            console.error('Reject failed', e);
        }
    },

    scrollToBottom() {
        this.$nextTick(() => {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        });
    },

    renderMarkdown(text) {
        if (!text) return '';
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code class="rounded bg-gray-200 px-1 py-0.5 text-xs dark:bg-gray-700">$1</code>')
            .replace(/\n/g, '<br>');
    },
}));
</script>
@endscript

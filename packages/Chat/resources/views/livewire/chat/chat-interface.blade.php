<div
    x-data="chatInterface(@js($conversationId), @js(route('chat.send')), @js($initialMessage), @js($messages), @js(auth()->id()), @js($hasMoreMessages), @js(auth()->user()?->ai_preferences['default_model'] ?? 'auto'))"
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
            <template x-if="hasMoreMessages">
                <div class="flex justify-center py-2">
                    <button
                        type="button"
                        x-on:click="loadEarlier()"
                        class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Load earlier messages
                    </button>
                </div>
            </template>

            <template x-for="(msg, index) in messages" :key="index">
                <div class="group/message">
                    {{-- User message --}}
                    <template x-if="msg.role === 'user'">
                        <div class="flex justify-end">
                            <div class="flex max-w-[80%] flex-col items-end gap-1">
                                <template x-if="!msg.editing">
                                    <div class="[overflow-wrap:anywhere] break-words rounded-2xl rounded-br-md bg-primary-600 px-4 py-3 text-sm text-white">
                                        <span x-text="msg.content" class="whitespace-pre-wrap"></span>
                                    </div>
                                </template>

                                <template x-if="msg.editing">
                                    <div class="w-full min-w-[16rem] rounded-2xl rounded-br-md bg-primary-600 p-2">
                                        <label :for="'chat-edit-' + index" class="sr-only">Edit message</label>
                                        <textarea
                                            :id="'chat-edit-' + index"
                                            x-ref="editArea"
                                            x-model="msg.editText"
                                            @input="autosize($event.target)"
                                            @keydown.escape.prevent="cancelEdit(msg)"
                                            @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); saveEdit(msg, index) }"
                                            rows="1"
                                            maxlength="5000"
                                            aria-label="Edit message"
                                            class="block min-h-[28px] w-full resize-none rounded-xl border-0 bg-primary-700/40 px-3 py-2 text-sm leading-6 text-white placeholder:text-primary-100/70 focus:outline-none focus:ring-2 focus:ring-white/40"
                                            style="max-height: 200px;"
                                        ></textarea>
                                        <div class="mt-2 flex items-center justify-between gap-2 px-1">
                                            <span
                                                class="text-[11px]"
                                                :class="{
                                                    'text-primary-100/80': (msg.editText || '').length <= 4900,
                                                    'text-amber-200': (msg.editText || '').length > 4900 && (msg.editText || '').length <= 5000,
                                                    'text-red-200': (msg.editText || '').length > 5000,
                                                }"
                                                x-text="(msg.editText || '').length > 4000 ? `${(msg.editText || '').length.toLocaleString()} / 5,000` : ''"
                                            ></span>
                                            <div class="flex gap-2">
                                                <button
                                                    type="button"
                                                    x-on:click="cancelEdit(msg)"
                                                    class="rounded-lg bg-primary-700/40 px-2.5 py-1 text-xs font-medium text-white hover:bg-primary-700/70"
                                                >
                                                    Cancel
                                                </button>
                                                <button
                                                    type="button"
                                                    x-on:click="saveEdit(msg, index)"
                                                    :disabled="!(msg.editText || '').trim() || (msg.editText || '').length > 5000 || isStreaming"
                                                    class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 disabled:cursor-not-allowed disabled:bg-white/60 disabled:text-primary-400"
                                                >
                                                    Save &amp; resend
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!msg.editing && !isStreaming">
                                    <div class="flex items-center gap-1 px-1 opacity-0 transition group-hover/message:opacity-100 focus-within:opacity-100">
                                        <button
                                            type="button"
                                            x-on:click="canEdit(index) && startEdit(msg, index)"
                                            :disabled="!canEdit(index)"
                                            :title="canEdit(index) ? 'Edit message' : 'Cannot edit — pending approval'"
                                            :aria-label="canEdit(index) ? 'Edit message' : 'Cannot edit — pending approval'"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-transparent disabled:hover:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                        >
                                            <x-heroicon-o-pencil-square class="h-3.5 w-3.5" aria-hidden="true" />
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Assistant message --}}
                    <template x-if="msg.role === 'assistant' && (msg.rendered || msg.content || (index === messages.length - 1 && isStreaming && currentToolStatus))">
                        <div class="flex flex-col items-start">
                            <div class="flex w-full justify-start">
                                <div
                                    class="prose prose-sm dark:prose-invert max-w-[85%] rounded-2xl rounded-bl-md bg-white px-4 py-3 text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 prose-p:my-2 prose-headings:mb-2 prose-headings:mt-3 prose-headings:text-gray-900 dark:prose-headings:text-white prose-pre:my-2 prose-ul:my-2 prose-ol:my-2 prose-li:my-0.5 prose-table:my-2 prose-table:border-collapse prose-thead:border-b prose-thead:border-gray-300 dark:prose-thead:border-gray-600 prose-th:px-2 prose-th:py-1 prose-th:text-left prose-td:border-t prose-td:border-gray-100 prose-td:px-2 prose-td:py-1 dark:prose-td:border-gray-700 prose-code:rounded prose-code:bg-gray-100 prose-code:px-1 prose-code:py-0.5 prose-code:text-[0.85em] prose-code:before:content-none prose-code:after:content-none dark:prose-code:bg-gray-900 prose-pre:rounded-lg prose-pre:bg-gray-900 prose-pre:text-gray-100 first:prose-headings:mt-0"
                                >
                                    <template x-if="msg.rendered && msg.prerendered">
                                        <div x-html="msg.content"></div>
                                    </template>
                                    <template x-if="msg.rendered && !msg.prerendered">
                                        <div x-html="window.renderMarkdown(msg.content)"></div>
                                    </template>
                                    <template x-if="!msg.rendered">
                                        <div>
                                            <template x-if="msg.content">
                                                <div x-text="msg.content" class="whitespace-pre-wrap"></div>
                                            </template>
                                            <template x-if="index === messages.length - 1 && isStreaming && currentToolStatus">
                                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400" role="status" :class="{ 'mt-2': msg.content }">
                                                    <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                                    </svg>
                                                    <span x-text="currentToolStatus"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <template x-if="msg.rendered && Array.isArray(msg.follow_ups) && msg.follow_ups.length > 0">
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <template x-for="chip in msg.follow_ups" :key="chip.prompt">
                                        <button
                                            type="button"
                                            x-on:click="input = chip.prompt; $nextTick(() => sendMessage())"
                                            x-text="chip.label"
                                            class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-700 dark:hover:bg-primary-900/20 dark:hover:text-primary-300"
                                        ></button>
                                    </template>
                                </div>
                            </template>

                            <template x-if="msg.rendered && msg.content">
                                <div class="mt-1 flex items-center gap-1 px-1 opacity-0 transition group-hover/message:opacity-100 focus-within:opacity-100">
                                    <button
                                        type="button"
                                        x-on:click="copyMessage(msg)"
                                        :aria-label="(now - (msg.copiedAt || 0) < 1500) ? 'Copied' : 'Copy message'"
                                        :title="(now - (msg.copiedAt || 0) < 1500) ? 'Copied' : 'Copy message'"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                    >
                                        <template x-if="now - (msg.copiedAt || 0) < 1500">
                                            <x-heroicon-s-check class="h-3.5 w-3.5 text-green-600 dark:text-green-400" aria-hidden="true" />
                                        </template>
                                        <template x-if="!(now - (msg.copiedAt || 0) < 1500)">
                                            <x-heroicon-o-document-duplicate class="h-3.5 w-3.5" aria-hidden="true" />
                                        </template>
                                    </button>
                                    <template x-if="!isStreaming && canRegenerate(index)">
                                        <button
                                            type="button"
                                            x-on:click="regenerateMessage(index)"
                                            aria-label="Regenerate response"
                                            title="Regenerate response"
                                            class="inline-flex h-7 items-center gap-1 rounded-md px-2 text-xs text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                        >
                                            <x-heroicon-o-arrow-path class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Regenerate</span>
                                        </button>
                                    </template>
                                </div>
                            </template>
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
                                        <div class="mt-3 flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                                                :class="{
                                                    'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400': action.status === 'approved',
                                                    'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400': action.status === 'rejected',
                                                    'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-400': action.status === 'expired',
                                                }"
                                                x-text="action.status.charAt(0).toUpperCase() + action.status.slice(1)"
                                            ></span>
                                            <template x-if="action.status === 'approved' && action.record && action.record.url">
                                                <a
                                                    :href="action.record.url"
                                                    wire:navigate
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                                >
                                                    View
                                                    <x-heroicon-o-arrow-top-right-on-square class="h-3 w-3" aria-hidden="true" />
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Streaming indicator (only before first token arrives) --}}
            <template x-if="isStreaming && !currentToolStatus && (messages.length === 0 || messages[messages.length-1].role !== 'assistant' || !messages[messages.length-1].content)">
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
                        x-show="!isStreaming"
                        type="submit"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400 disabled:shadow-none dark:disabled:bg-gray-700 dark:disabled:text-gray-500"
                        :disabled="!input.trim() || input.length > 5000"
                        aria-label="Send message"
                    >
                        <x-heroicon-s-arrow-up class="h-4 w-4" />
                    </button>
                    <button
                        x-show="isStreaming"
                        type="button"
                        x-on:click="cancelStream()"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-900 text-white shadow-sm transition hover:bg-gray-700 dark:bg-gray-200 dark:text-gray-900 dark:hover:bg-gray-300"
                        aria-label="Stop generation"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <rect x="6" y="6" width="12" height="12" rx="2"/>
                        </svg>
                    </button>
                </div>
                <div class="mt-1.5 flex items-center justify-between gap-2 px-1 text-[11px] text-gray-400 dark:text-gray-500">
                    <div class="flex items-center gap-2">
                        <div x-data="{ menuOpen: false }" class="relative">
                            <button
                                type="button"
                                x-on:click="menuOpen = !menuOpen"
                                class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2 py-0.5 text-[11px] font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                                :aria-expanded="menuOpen"
                                aria-haspopup="listbox"
                                aria-label="Select AI model"
                            >
                                <span x-text="modelLabel(selectedModel)"></span>
                                <x-heroicon-o-chevron-up-down class="h-3 w-3" aria-hidden="true" />
                            </button>
                            <div
                                x-show="menuOpen"
                                x-on:click.away="menuOpen = false"
                                x-transition.opacity.duration.100ms
                                role="listbox"
                                class="absolute bottom-full left-0 z-10 mb-1 w-48 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                style="display: none;"
                            >
                                <template x-for="opt in modelOptions" :key="opt.value">
                                    <button
                                        type="button"
                                        role="option"
                                        :aria-selected="selectedModel === opt.value"
                                        x-on:click="selectModel(opt.value); menuOpen = false"
                                        class="block w-full px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700"
                                        :class="{ 'bg-gray-100 font-semibold dark:bg-gray-700': selectedModel === opt.value }"
                                    >
                                        <span x-text="opt.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <span class="hidden sm:inline">
                            <kbd class="rounded border border-gray-200 bg-gray-50 px-1 py-0.5 font-sans text-[10px] dark:border-gray-700 dark:bg-gray-900">Enter</kbd> to send
                            <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                            <kbd class="rounded border border-gray-200 bg-gray-50 px-1 py-0.5 font-sans text-[10px] dark:border-gray-700 dark:bg-gray-900">Shift</kbd>
                            +
                            <kbd class="rounded border border-gray-200 bg-gray-50 px-1 py-0.5 font-sans text-[10px] dark:border-gray-700 dark:bg-gray-900">Enter</kbd>
                            for newline
                        </span>
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
Alpine.data('chatInterface', (initialConversationId, sendUrl, initialMessage, initialMessages, userId, initialHasMoreMessages, initialModel) => ({
    conversationId: initialConversationId,
    messages: initialMessages || [],
    hasMoreMessages: !!initialHasMoreMessages,
    input: '',
    isStreaming: false,
    channel: null,
    streamTimeoutId: null,
    streamTimeoutMs: 60000,
    prependScrollAnchor: null,
    streamAbortController: null,
    currentToolStatus: null,
    now: Date.now(),
    copyTickerId: null,
    selectedModel: 'auto',

    modelOptions: [
        { value: 'auto', label: 'Auto' },
        { value: 'claude-haiku', label: 'Fast (Haiku)' },
        { value: 'claude-sonnet', label: 'Claude Sonnet' },
        { value: 'claude-opus', label: 'Claude Opus' },
        { value: 'gpt-4o', label: 'GPT-4o' },
        { value: 'gemini-pro', label: 'Gemini Pro' },
    ],

    modelLabel(value) {
        const found = this.modelOptions.find((o) => o.value === value);
        return (found || this.modelOptions[0]).label;
    },

    selectModel(value) {
        this.selectedModel = value;
        try { localStorage.setItem('chat:model', value); } catch (_) { /* ignore */ }
    },

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
        const validModels = this.modelOptions.map((o) => o.value);
        let stored = null;
        try { stored = localStorage.getItem('chat:model'); } catch (_) { /* ignore */ }
        const candidate = stored || initialModel || 'auto';
        this.selectedModel = validModels.includes(candidate) ? candidate : 'auto';

        this.messages.forEach((m) => {
            if (m.role === 'assistant') {
                m.rendered = true;
                m.prerendered = true;
                if (!Array.isArray(m.follow_ups)) {
                    m.follow_ups = [];
                }
            }
            if (m.role === 'user') {
                m.editing = false;
                m.editText = '';
            }
            if (typeof m.copiedAt === 'undefined') {
                m.copiedAt = 0;
            }
        });

        if (this.conversationId) {
            this.subscribeToConversation(this.conversationId);
        }

        if (initialMessage) {
            this.$nextTick(() => {
                this.input = initialMessage;
                this.sendMessage();
            });
        }

        try {
            const draft = localStorage.getItem('chat:draft');
            if (draft) {
                this.input = draft;
                localStorage.removeItem('chat:draft');
            }
        } catch (_) { /* ignore */ }

        this.beforeUnloadHandler = (e) => {
            if (!this.isStreaming) return;
            e.preventDefault();
            e.returnValue = 'Your message is still being generated. Leave anyway?';
        };
        window.addEventListener('beforeunload', this.beforeUnloadHandler);

        this.$wire.$on('chat:messages-prepended', (payload) => {
            const earlier = (payload && payload.messages) || [];
            const hasMore = payload ? !!payload.hasMore : false;
            if (earlier.length > 0) {
                earlier.forEach((m) => {
                    if (m.role === 'assistant') {
                        m.rendered = true;
                        m.prerendered = true;
                        if (!Array.isArray(m.follow_ups)) {
                            m.follow_ups = [];
                        }
                    }
                    if (m.role === 'user') {
                        m.editing = false;
                        m.editText = '';
                    }
                    if (typeof m.copiedAt === 'undefined') {
                        m.copiedAt = 0;
                    }
                });
                this.messages = [...earlier, ...this.messages];
            }
            this.hasMoreMessages = hasMore;

            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (!el || this.prependScrollAnchor === null) return;
                el.scrollTop = el.scrollHeight - this.prependScrollAnchor;
                this.prependScrollAnchor = null;
            });
        });
    },

    loadEarlier() {
        const el = this.$refs.messages;
        this.prependScrollAnchor = el ? el.scrollHeight : 0;
        this.$wire.loadEarlierMessages();
    },

    destroy() {
        this.clearStreamTimeout();
        this.stopCopyTicker();
        this.unsubscribe();
        window.removeEventListener('beforeunload', this.beforeUnloadHandler);
    },

    startCopyTicker() {
        if (this.copyTickerId) return;
        this.copyTickerId = setInterval(() => {
            this.now = Date.now();
            const stillActive = this.messages.some((m) => m.copiedAt && this.now - m.copiedAt < 1500);
            if (!stillActive) {
                this.stopCopyTicker();
            }
        }, 200);
    },

    stopCopyTicker() {
        if (this.copyTickerId) {
            clearInterval(this.copyTickerId);
            this.copyTickerId = null;
        }
    },

    async copyMessage(msg) {
        const text = msg?.content || '';
        if (!text) return;

        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(text);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            msg.copiedAt = Date.now();
            this.now = msg.copiedAt;
            this.startCopyTicker();
        } catch (_) { /* clipboard blocked — silently ignore */ }
    },

    canRegenerate(index) {
        for (let i = index - 1; i >= 0; i--) {
            if (this.messages[i].role === 'user') {
                return true;
            }
        }
        return false;
    },

    regenerateMessage(index) {
        if (this.isStreaming) return;

        let userIndex = -1;
        for (let i = index - 1; i >= 0; i--) {
            if (this.messages[i].role === 'user') {
                userIndex = i;
                break;
            }
        }
        if (userIndex === -1) return;

        const userText = this.messages[userIndex].content;
        this.messages.splice(userIndex);

        this.input = userText;
        this.$nextTick(() => this.sendMessage());
    },

    canEdit(index) {
        if (this.isStreaming) return false;

        for (let i = index + 1; i < this.messages.length; i++) {
            const next = this.messages[i];
            if (next.role !== 'assistant') continue;
            const hasPending = (next.pending_actions || []).some((a) => a.status === 'pending');
            if (hasPending) return false;
            break;
        }
        return true;
    },

    startEdit(msg, index) {
        if (!this.canEdit(index)) return;
        this.messages.forEach((m) => {
            if (m.role === 'user' && m.editing) {
                m.editing = false;
                m.editText = '';
            }
        });
        msg.editText = msg.content;
        msg.editing = true;

        this.$nextTick(() => {
            const el = this.$refs.editArea;
            if (!el) return;
            el.focus();
            el.setSelectionRange(el.value.length, el.value.length);
            this.autosize(el);
        });
    },

    cancelEdit(msg) {
        msg.editing = false;
        msg.editText = '';
    },

    saveEdit(msg, index) {
        if (this.isStreaming) return;

        const newText = (msg.editText || '').trim();
        if (!newText || newText.length > 5000) return;

        this.messages.splice(index);

        this.input = newText;
        this.$nextTick(() => this.sendMessage());
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
            .listen('.tool_call', (e) => this.handleToolCall(e))
            .listen('.tool_result', (e) => this.handleToolResult(e))
            .listen('.stream_end', () => this.handleStreamEnd())
            .listen('.stream.failed', (e) => this.handleStreamFailed(e))
            .listen('.conversation.resolved', (e) => this.handleConversationResolved(e))
            .listen('.follow_ups', (e) => this.handleFollowUps(e));
    },

    handleFollowUps(event) {
        const chips = Array.isArray(event?.chips) ? event.chips.slice(0, 3) : [];
        for (let i = this.messages.length - 1; i >= 0; i--) {
            if (this.messages[i].role === 'assistant') {
                this.messages[i].follow_ups = chips;
                break;
            }
        }
    },

    friendlyToolStatus(toolName) {
        if (!toolName) return 'Running tool…';
        const normalized = String(toolName)
            .replace(/Tool$/, '')
            .replace(/([a-z])([A-Z])/g, '$1_$2')
            .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
            .toLowerCase();

        if (normalized === 'get_crm_summary') return 'Reading CRM summary…';
        if (normalized === 'search_crm') return 'Searching CRM…';

        const m = normalized.match(/^(list|get|create|update|delete)_(.+)$/);
        if (!m) return `Running ${normalized}…`;

        const [, op, rest] = m;
        const entity = rest.replace(/_/g, ' ');

        if (op === 'list') return `Searching ${entity}…`;
        if (op === 'get') return `Looking up ${entity}…`;
        return `Preparing ${op} ${entity} proposal…`;
    },

    startStreamTimeout() {
        this.clearStreamTimeout();
        this.streamTimeoutId = setTimeout(() => {
            if (!this.isStreaming) return;
            const assistantMsg = this.messages[this.messages.length - 1];
            if (assistantMsg?.role === 'assistant') {
                if (!assistantMsg.content) {
                    assistantMsg.content = 'The assistant took too long to respond. Please try again.';
                }
                assistantMsg.rendered = true;
                assistantMsg.prerendered = false;
            }
            this.currentToolStatus = null;
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

        if (this.conversationId && !this.channel) {
            this.subscribeToConversation(this.conversationId);
        }

        this.messages.push({ role: 'user', content: text, editing: false, editText: '', copiedAt: 0 });
        this.input = '';
        this.isStreaming = true;
        this.currentToolStatus = null;

        this.messages.push({ role: 'assistant', content: '', pending_actions: [], paywall: null, sessionExpired: false, rendered: false, prerendered: false, copiedAt: 0, follow_ups: [] });

        const url = this.conversationId
            ? sendUrl.replace(/\/$/, '') + '/' + this.conversationId
            : sendUrl;

        this.startStreamTimeout();

        this.streamAbortController = new AbortController();

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ message: text, model: this.selectedModel }),
                signal: this.streamAbortController.signal,
            });

            if (!response.ok) {
                const body = await response.json().catch(() => ({}));
                const assistantMsg = this.messages[this.messages.length - 1];

                if (response.status === 401 || response.status === 419) {
                    try { localStorage.setItem('chat:draft', text); } catch (_) { /* ignore */ }
                    assistantMsg.content = 'Your session expired. Please sign in again — your message is saved locally.';
                    assistantMsg.sessionExpired = true;
                    assistantMsg.rendered = true;
                    this.isStreaming = false;
                    this.clearStreamTimeout();
                    return;
                }

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

                assistantMsg.rendered = true;
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
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            const assistantMsg = this.messages[this.messages.length - 1];
            assistantMsg.content = 'Network error. Please try again.';
            assistantMsg.rendered = true;
            this.isStreaming = false;
            this.clearStreamTimeout();
        }

        this.scrollToBottom();
    },

    cancelStream() {
        try { this.streamAbortController?.abort(); } catch (_) { /* ignore */ }
        this.streamAbortController = null;

        this.unsubscribe();
        this.clearStreamTimeout();

        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant') {
            if (!assistantMsg.content) {
                assistantMsg.content = 'Cancelled.';
            }
            assistantMsg.rendered = true;
            assistantMsg.prerendered = false;
        }

        this.currentToolStatus = null;
        this.isStreaming = false;
    },

    handleTextDelta(event) {
        this.startStreamTimeout();
        this.currentToolStatus = null;
        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant') {
            assistantMsg.content += event.delta || '';
            this.scrollToBottom();
        }
    },

    handleToolCall(event) {
        this.startStreamTimeout();
        this.currentToolStatus = this.friendlyToolStatus(event?.tool_name);
        this.scrollToBottom();
    },

    handleToolResult(event) {
        this.startStreamTimeout();
        this.currentToolStatus = null;
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
        this.currentToolStatus = null;
        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant') {
            assistantMsg.rendered = true;
            assistantMsg.prerendered = false;
        }
        this.isStreaming = false;
        this.clearStreamTimeout();
        this.scrollToBottom();
    },

    handleStreamFailed(event) {
        this.currentToolStatus = null;
        const assistantMsg = this.messages[this.messages.length - 1];
        if (assistantMsg?.role === 'assistant') {
            if (!assistantMsg.content) {
                assistantMsg.content = event?.message || 'The assistant encountered an error. Please try again.';
            }
            assistantMsg.rendered = true;
            assistantMsg.prerendered = false;
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
        const previousStatus = action.status;
        action.status = 'approved';
        action.error = null;

        try {
            const res = await fetch(@js(url('/chat/actions')) + '/' + action.pending_action_id + '/approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (res.ok) {
                const body = await res.json().catch(() => ({}));
                if (body.record) {
                    action.record = body.record;
                }
            } else {
                const body = await res.json().catch(() => ({}));
                action.status = previousStatus;
                action.error = body.error || 'Failed to approve';
            }
        } catch {
            action.status = previousStatus;
            action.error = 'Network error';
        }
    },

    async rejectAction(action) {
        const previousStatus = action.status;
        action.status = 'rejected';
        action.error = null;

        try {
            const res = await fetch(@js(url('/chat/actions')) + '/' + action.pending_action_id + '/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (! res.ok) {
                const body = await res.json().catch(() => ({}));
                action.status = previousStatus;
                action.error = body.error || 'Failed to reject';
            }
        } catch {
            action.status = previousStatus;
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

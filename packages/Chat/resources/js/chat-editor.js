import { Editor } from '@tiptap/core';
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import HardBreak from '@tiptap/extension-hard-break';
import Placeholder from '@tiptap/extension-placeholder';
import Mention from '@tiptap/extension-mention';
import { createMentionSuggestion } from './chat-mention-suggestion';

export function chatEditor({ initialDocument, placeholder, onSubmit, onChange, autofocus } = {}) {
    return {
        editorEl: null,
        editor: null,

        init() {
            this.editorEl = this.$refs.editor;

            const ChatMention = Mention.extend({
                addAttributes() {
                    return {
                        id: { default: null },
                        type: { default: null },
                        label: { default: null },
                    };
                },
                parseHTML() {
                    return [{ tag: 'span[data-mention-id]' }];
                },
                renderHTML({ node, HTMLAttributes }) {
                    return ['span', {
                        'data-mention-id': node.attrs.id,
                        'data-mention-type': node.attrs.type,
                        'class': 'inline-flex items-center rounded-md bg-primary-100 px-1.5 py-0.5 text-xs text-primary-800 dark:bg-primary-900/30 dark:text-primary-200',
                        ...HTMLAttributes,
                    }, '@' + (node.attrs.label ?? '')];
                },
            });

            this.editor = new Editor({
                element: this.editorEl,
                extensions: [
                    Document,
                    Paragraph,
                    Text,
                    HardBreak.configure({ keepMarks: false }),
                    Placeholder.configure({ placeholder: placeholder ?? 'Ask anything…' }),
                    ChatMention.configure({
                        HTMLAttributes: { class: 'mention' },
                        suggestion: createMentionSuggestion(),
                    }),
                ],
                content: initialDocument ?? { type: 'doc', content: [] },
                editorProps: {
                    attributes: {
                        class: 'prose prose-sm max-w-none focus:outline-none min-h-[64px] px-4 pt-3 pb-2 text-sm leading-6',
                    },
                    handleKeyDown: (view, event) => {
                        if (event.key === 'Enter' && !event.shiftKey) {
                            event.preventDefault();
                            onSubmit?.();
                            return true;
                        }
                        return false;
                    },
                },
                onUpdate: ({ editor }) => {
                    onChange?.({ document: editor.getJSON(), text: editor.getText() });
                },
            });

            if (autofocus) {
                this.$nextTick(() => this.editor?.commands.focus('end'));
            }
        },

        destroy() {
            this.editor?.destroy();
        },

        getDocument() {
            return this.editor?.getJSON() ?? { type: 'doc', content: [] };
        },

        getText() {
            return (this.editor?.getText() ?? '').trim();
        },

        setText(text) {
            this.editor?.commands.setContent({
                type: 'doc',
                content: text === '' ? [] : [{
                    type: 'paragraph',
                    content: [{ type: 'text', text }],
                }],
            });
        },

        clear() {
            this.editor?.commands.clearContent();
        },

        focus() {
            this.editor?.commands.focus('end');
        },

        isEmpty() {
            return this.editor?.isEmpty ?? true;
        },
    };
}

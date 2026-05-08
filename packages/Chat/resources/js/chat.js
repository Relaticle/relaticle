import { marked } from 'marked'
import DOMPurify from 'dompurify'

marked.setOptions({ breaks: true, gfm: true })

window.renderMarkdown = (text) => {
    if (!text) return ''
    return DOMPurify.sanitize(marked.parse(text))
}

import '../css/chat-editor.css';
import { chatEditor } from './chat-editor';

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('chatEditor', chatEditor);
    }
});

import { marked } from 'marked'
import DOMPurify from 'dompurify'

marked.setOptions({ breaks: true, gfm: true })

window.renderMarkdown = (text) => {
    if (!text) return ''
    return DOMPurify.sanitize(marked.parse(text))
}

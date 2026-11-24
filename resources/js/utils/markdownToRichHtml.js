/**
 * Converts raw Markdown (including Quill-wrapped <p>### ...</p>, **bold**, * list,
 * and [cite: ...] citation markers) into clean semantic Rich Text HTML compatible
 * with QuillEditor (<h1>–<h3>, <p>, <strong>, <em>, <u>, <ul>, <ol>, <blockquote>, <a>, <code>).
 */

const INLINE_HTML_DECODE_MAP = {
  '&amp;': '&',
  '&lt;': '<',
  '&gt;': '>',
  '&quot;': '"',
  '&#39;': "'",
  '&#039;': "'",
  '&apos;': "'",
  '&nbsp;': ' ',
};

function decodeEntities(str) {
  return String(str || '').replace(
    /&(?:amp|lt|gt|quot|#0?39|apos|nbsp);/gi,
    (m) => INLINE_HTML_DECODE_MAP[m.toLowerCase()] ?? m,
  );
}

function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * Strip Google NotebookLM / Gemini inline citation markers such as [cite: 42, 114].
 */
export function stripCitationMarkers(text) {
  let count = 0;
  const cleaned = String(text || '').replace(/\s*\[cite:\s*[\d,\s]+\]/gi, () => {
    count += 1;
    return '';
  });
  return { cleaned, count };
}

/**
 * Convert HTML from Quill (which wraps pasted raw markdown in <p>...</p><p><br></p>)
 * back into clean newline-delimited raw text lines when raw markdown is detected.
 */
function extractMarkdownLines(input) {
  const raw = String(input || '').trim();
  if (!raw) return [];

  // Normalize block separators to newlines before stripping tags
  let text = raw
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/(?:p|div|h[1-6]|li|blockquote|tr)>/gi, '\n')
    .replace(/<li[^>]*>/gi, '- ')
    .replace(/<[^>]+>/g, '');

  text = decodeEntities(text)
    .replace(/\u00A0/g, ' ')
    .replace(/\r\n?/g, '\n');

  return text.split('\n');
}

/**
 * Convert raw Markdown (or Quill-wrapped raw Markdown) into clean Rich Text HTML
 * ready for QuillEditor.
 *
 * @param {string} content Current editor content (HTML or plain text)
 * @returns {{ html: string, stats: { headings: number, bolds: number, lists: number, citationsRemoved: number }, changed: boolean }}
 */
export function convertMarkdownToRichHtml(content) {
  const stats = {
    headings: 0,
    bolds: 0,
    lists: 0,
    citationsRemoved: 0,
  };

  if (!content || !String(content).trim()) {
    return { html: '', stats, changed: false };
  }

  const lines = extractMarkdownLines(content);
  const htmlBlocks = [];

  let listType = null; // 'ul' | 'ol' | null
  let listItems = [];

  const flushList = () => {
    if (listType && listItems.length > 0) {
      htmlBlocks.push(`<${listType}>${listItems.join('')}</${listType}>`);
      stats.lists += listItems.length;
    }
    listType = null;
    listItems = [];
  };

  const countAndFormatInline = (rawLine) => {
    // Count bold tokens & citations cleanly
    const boldMatches = rawLine.match(/\*\*[^*\n]+?\*\*/g);
    if (boldMatches) stats.bolds += boldMatches.length;
    const citeMatches = rawLine.match(/\[cite:\s*[\d,\s]+\]/gi);
    if (citeMatches) stats.citationsRemoved += citeMatches.length;

    let out = escapeHtml(rawLine);
    out = out.replace(/\s*\[cite:\s*[\d,\s]+\]/gi, '');
    out = out.replace(/`([^`\n]+)`/g, '<code>$1</code>');
    out = out.replace(
      /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
      '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
    );
    out = out.replace(/\*\*([^*\n]+?)\*\*/g, '<strong>$1</strong>');
    out = out.replace(/__([^_\n]+?)__/g, (_, inner) => {
      stats.bolds += 1;
      return `<strong>${inner}</strong>`;
    });
    out = out.replace(/~~([^~\n]+?)~~/g, '<s>$1</s>');
    out = out.replace(/(^|[^*])\*([^*\n]+?)\*(?!\*)/g, '$1<em>$2</em>');
    return out.trim();
  };

  for (let i = 0; i < lines.length; i++) {
    const rawLine = lines[i];
    const trimmed = rawLine.trim();

    // Blank line flushes open list and adds visual separation naturally via block margins
    if (trimmed === '') {
      flushList();
      continue;
    }

    // Horizontal rule (---, ***, ___)
    if (/^(?:-{3,}|\*{3,}|_{3,})$/.test(trimmed)) {
      flushList();
      htmlBlocks.push('<hr>');
      continue;
    }

    // Headings (# to ###### -> Quill natively supports h1, h2, h3; h4-h6 map cleanly to h3)
    const headingMatch = trimmed.match(/^(#{1,6})\s+(.+)$/);
    if (headingMatch) {
      flushList();
      const level = Math.min(3, headingMatch[1].length);
      const headingText = countAndFormatInline(headingMatch[2]);
      htmlBlocks.push(`<h${level}>${headingText}</h${level}>`);
      stats.headings += 1;
      continue;
    }

    // Blockquote (> text)
    const quoteMatch = trimmed.match(/^>\s?(.*)$/);
    if (quoteMatch) {
      flushList();
      const quoteText = countAndFormatInline(quoteMatch[1]);
      if (quoteText) {
        htmlBlocks.push(`<blockquote>${quoteText}</blockquote>`);
      }
      continue;
    }

    // Markdown table separator row (|---|---|) -> skip separator line cleanly
    if (/^\|[\s:|\-]+\|$/.test(trimmed)) {
      continue;
    }

    // Markdown table data row (| a | b |) -> convert to clean bullet/formatted line (Quill Snow strips <table>)
    if (/^\|.+\|$/.test(trimmed)) {
      const cells = trimmed
        .slice(1, -1)
        .split('|')
        .map((c) => countAndFormatInline(c.trim()))
        .filter(Boolean);
      if (cells.length > 0) {
        if (listType !== 'ul') {
          flushList();
          listType = 'ul';
        }
        const formattedRow = cells.length >= 2
          ? `<strong>${cells[0]}</strong>: ${cells.slice(1).join(' — ')}`
          : cells[0];
        listItems.push(`<li>${formattedRow}</li>`);
      }
      continue;
    }

    // Unordered list item (* item, - item, + item, • item)
    const ulMatch = trimmed.match(/^(?:[-*+•])\s+(.+)$/);
    if (ulMatch) {
      if (listType !== 'ul') {
        flushList();
        listType = 'ul';
      }
      listItems.push(`<li>${countAndFormatInline(ulMatch[1])}</li>`);
      continue;
    }

    // Ordered list item (1. item or 1) item)
    const olMatch = trimmed.match(/^\d+[.)]\s+(.+)$/);
    if (olMatch) {
      if (listType !== 'ol') {
        flushList();
        listType = 'ol';
      }
      listItems.push(`<li>${countAndFormatInline(olMatch[1])}</li>`);
      continue;
    }

    // Normal paragraph
    flushList();
    const paragraphHtml = countAndFormatInline(trimmed);
    if (paragraphHtml) {
      htmlBlocks.push(`<p>${paragraphHtml}</p>`);
    }
  }

  flushList();

  const finalHtml = htmlBlocks.join('');
  const changed = finalHtml !== String(content).trim()
    && (stats.headings > 0 || stats.bolds > 0 || stats.lists > 0 || stats.citationsRemoved > 0 || finalHtml.length > 0);

  return {
    html: finalHtml,
    stats,
    changed,
  };
}

/**
 * Shared Vue helper for Create.vue and Edit.vue to convert raw Markdown inside
 * QuillEditor into formatted Rich Text HTML with single-click Undo support.
 */
export function useMarkdownConverter(refFn, form, quillEditorRef, toast) {
  const previousRawContent = refFn(null);

  const convertMarkdownContent = () => {
    if (!form.content || !form.content.trim()) {
      toast?.info('Konten masih kosong.');
      return;
    }

    previousRawContent.value = form.content;
    const { html, stats, changed } = convertMarkdownToRichHtml(form.content);
    if (!changed || !html) {
      previousRawContent.value = null;
      toast?.info('Tidak ada format Markdown mentah yang perlu dikonversi.');
      return;
    }

    form.content = html;
    if (quillEditorRef.value?.setHTML) {
      quillEditorRef.value.setHTML(html);
    }

    const parts = [];
    if (stats.headings) parts.push(`${stats.headings} heading`);
    if (stats.bolds) parts.push(`${stats.bolds} teks tebal`);
    if (stats.lists) parts.push(`${stats.lists} item daftar`);
    if (stats.citationsRemoved) parts.push(`${stats.citationsRemoved} [cite] dihapus`);

    toast?.success(
      parts.length
        ? `Format Markdown berhasil dikonversi (${parts.join(', ')})!`
        : 'Konten berhasil dirapikan menjadi format Rich Text!',
    );
  };

  const undoMarkdownConvert = () => {
    if (previousRawContent.value === null) return;
    form.content = previousRawContent.value;
    if (quillEditorRef.value?.setHTML) {
      quillEditorRef.value.setHTML(previousRawContent.value);
    }
    previousRawContent.value = null;
    toast?.info('Konversi dibatalkan, konten dikembalikan ke sebelumnya.');
  };

  return {
    previousRawContent,
    convertMarkdownContent,
    undoMarkdownConvert,
  };
}

<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

class RichContentProcessor
{
    /**
     * Extract and format rich content from HTML while preserving structure
     */
    public function extractRichContent(string $htmlContent): array
    {
        if (empty($htmlContent)) {
            return ['text' => '', 'has_rich_content' => false, 'elements' => []];
        }

        // Create DOM document to parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML parsing warnings

        // Wrap content in body to ensure valid HTML structure
        $wrappedContent = '<!DOCTYPE html><html><body>' . $htmlContent . '</body></html>';
        $dom->loadHTML($wrappedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        $richElements = [];
        $hasRichContent = false;

        // Extract images
        $images = $xpath->query('//img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');

            if (!empty($src)) {
                $richElements[] = [
                    'type' => 'image',
                    'src' => $src,
                    'alt' => $alt ?: 'Gambar',
                    'caption' => $this->extractImageCaption($img),
                ];
                $hasRichContent = true;
            }
        }

        // Extract links
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);

            if (!empty($href) && !empty($text)) {
                $richElements[] = [
                    'type' => 'link',
                    'url' => $href,
                    'text' => $text,
                    'title' => $link->getAttribute('title') ?: null,
                ];
                $hasRichContent = true;
            }
        }

        // Extract formatted text (headers, lists, etc.)
        $headers = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
        foreach ($headers as $header) {
            $level = (int) substr($header->nodeName, 1);
            $text = trim($header->textContent);

            if (!empty($text)) {
                $richElements[] = [
                    'type' => 'header',
                    'level' => $level,
                    'text' => $text,
                ];
                $hasRichContent = true;
            }
        }

        // Extract lists
        $lists = $xpath->query('//ul | //ol');
        foreach ($lists as $list) {
            $items = $xpath->query('.//li', $list);
            $listItems = [];

            foreach ($items as $item) {
                $itemText = trim($item->textContent);
                if (!empty($itemText)) {
                    $listItems[] = $itemText;
                }
            }

            if (!empty($listItems)) {
                $richElements[] = [
                    'type' => 'list',
                    'ordered' => $list->nodeName === 'ol',
                    'items' => $listItems,
                ];
                $hasRichContent = true;
            }
        }

        // Extract clean text content
        $textContent = $this->extractCleanText($htmlContent);

        return [
            'text' => $textContent,
            'has_rich_content' => $hasRichContent,
            'elements' => $richElements,
        ];
    }

    /**
     * Convert rich content elements to AI-friendly markdown format
     */
    public function convertToAIMarkdown(array $richContentData): string
    {
        if (!$richContentData['has_rich_content']) {
            return $richContentData['text'];
        }

        $markdown = $richContentData['text'] . "\n\n";

        foreach ($richContentData['elements'] as $element) {
            switch ($element['type']) {
                case 'header':
                    $markdown .= str_repeat('#', $element['level']) . " " . $element['text'] . "\n\n";
                    break;

                case 'list':
                    $marker = $element['ordered'] ? '1.' : '-';
                    foreach ($element['items'] as $index => $item) {
                        if ($element['ordered']) {
                            $markdown .= ($index + 1) . ". " . $item . "\n";
                        } else {
                            $markdown .= "- " . $item . "\n";
                        }
                    }
                    $markdown .= "\n";
                    break;

                case 'link':
                    $markdown .= "[" . $element['text'] . "](" . $element['url'] . ")\n";
                    break;

                case 'image':
                    $markdown .= "![" . $element['alt'] . "](" . $element['src'] . ")\n";
                    if (!empty($element['caption'])) {
                        $markdown .= "*" . $element['caption'] . "*\n";
                    }
                    break;
            }
        }

        return trim($markdown);
    }

    /**
     * Format rich content for AI response with proper formatting instructions
     */
    public function formatForAIResponse(array $richContentData): string
    {
        if (!$richContentData['has_rich_content']) {
            // Even for plain text, improve formatting
            return $this->improveTextFormatting($richContentData['text']);
        }

        $formatted = $this->improveTextFormatting($richContentData['text']);

        // Add instructions for rich content elements
        $richInstructions = [];

        foreach ($richContentData['elements'] as $element) {
            switch ($element['type']) {
                case 'link':
                    $richInstructions[] = "Link tersedia: [" . $element['text'] . "](" . $element['url'] . ")";
                    break;

                case 'header':
                    $richInstructions[] = "Heading tersedia (Level " . $element['level'] . "): " . $element['text'];
                    break;

                case 'list':
                    $listType = $element['ordered'] ? 'Daftar berurut' : 'Daftar poin';
                    $richInstructions[] = $listType . " tersedia: " . implode(', ', array_slice($element['items'], 0, 3)) . (count($element['items']) > 3 ? '...' : '');
                    break;
            }
        }

        if (!empty($richInstructions)) {
            $formatted .= "\n\nElemen Rich Content yang Tersedia:\n" . implode("\n", $richInstructions);
        }

        return $formatted;
    }

    /**
     * Improve text formatting for better readability
     */
    private function improveTextFormatting(string $text): string
    {
        // Fix missing spaces between words that got concatenated
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text); // CamelCase
        $text = preg_replace('/([A-Za-z])(\d)/', '$1 $2', $text); // Letter + Number
        $text = preg_replace('/(\d)([A-Za-z])/', '$1 $2', $text); // Number + Letter

        // Fix common spacing issues
        $text = preg_replace('/([.!?:;,])([A-Za-z])/', '$1 $2', $text); // Punctuation + Letter
        $text = preg_replace('/([A-Za-z])\(/', '$1 (', $text); // Letter + (
        $text = preg_replace('/\)([A-Za-z])/', ') $1', $text); // ) + Letter

        // Fix spacing around quotes
        $text = preg_replace('/([A-Za-z])"/', '$1 "', $text);
        $text = preg_replace('"([A-Za-z])', '" $1', $text);

        // Ensure proper paragraph breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Clean up multiple spaces
        $text = preg_replace('/\s{2,}/', ' ', $text);

        // Fix line breaks
        $text = preg_replace('/\n\s+/', "\n", $text);
        $text = preg_replace('/\s+\n/', "\n", $text);

        return trim($text);
    }

    /**
     * Generate AI instructions for presenting rich content
     */
    public function generateAIInstructions(array $richContentData): string
    {
        if (!$richContentData['has_rich_content']) {
            return '';
        }

        $instructions = [];

        foreach ($richContentData['elements'] as $element) {
            switch ($element['type']) {
                case 'link':
                    $instructions[] = "Sertakan link: [" . $element['text'] . "](" . $element['url'] . ")";
                    break;

                case 'image':
                    $instructions[] = "WAJIB sertakan gambar: ![" . $element['alt'] . "](" . $element['src'] . ")";
                    break;

                case 'header':
                    $instructions[] = "Gunakan struktur heading untuk: " . $element['text'];
                    break;

                case 'list':
                    $listType = $element['ordered'] ? 'berurut' : 'tidak berurut';
                    $instructions[] = "Format sebagai daftar " . $listType . " dengan " . count($element['items']) . " item";
                    break;
            }
        }

        if (!empty($instructions)) {
            return "\n\nPETUNJUK FORMAT JAWABAN:\n" . implode("\n", $instructions) .
                "\nJawaban harus menyertakan semua elemen di atas dengan format markdown yang tepat.";
        }

        return '';
    }

    /**
     * Extract clean text from HTML
     */
    private function extractCleanText(string $html): string
    {
        // Remove script and style tags completely
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);

        // Strip remaining HTML tags
        $text = strip_tags($html);

        // Improve the extracted text formatting
        $text = $this->improveTextFormatting($text);

        return trim($text);
    }

    /**
     * Extract image caption from surrounding elements
     */
    private function extractImageCaption($imgNode): ?string
    {
        // Look for caption in parent figure element
        $parent = $imgNode->parentNode;
        if ($parent && $parent->nodeName === 'figure') {
            $figcaption = $parent->getElementsByTagName('figcaption')->item(0);
            if ($figcaption) {
                return trim($figcaption->textContent);
            }
        }

        // Look for caption in title or data-caption attributes
        $title = $imgNode->getAttribute('title');
        if (!empty($title)) {
            return $title;
        }

        $caption = $imgNode->getAttribute('data-caption');
        if (!empty($caption)) {
            return $caption;
        }

        return null;
    }

    /**
     * Convert AI markdown response to rich HTML with lightbox support
     */
    public function convertAIMarkdownToHTML(string $markdownContent): string
    {
        if (empty($markdownContent)) {
            return '';
        }

        $html = $markdownContent;

        // First handle image references and convert to actual images with lightbox support
        $html = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($matches) {
            $alt = $matches[1] ?: 'Gambar';
            $src = trim($matches[2]);

            // Handle different URL formats
            if (strpos($src, 'http') === 0) {
                // Already a full URL (like stored images from Quill)
                $fullUrl = $src;
            } elseif (strpos($src, '/storage/') === 0) {
                // Relative storage path
                $fullUrl = url($src);
            } else {
                // Assume it's a storage path without leading slash
                $fullUrl = url('/storage/' . ltrim($src, '/'));
            }

            return '<div class="my-4 text-center">
                <a href="' . $fullUrl . '" class="kb-lightbox inline-block">
                    <img src="' . $fullUrl . '" alt="' . htmlspecialchars($alt) . '" class="max-w-full h-auto rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer border" loading="lazy" style="max-height: 400px;" />
                </a>
                ' . (!empty($alt) ? '<p class="text-sm text-gray-600 mt-2 italic">' . htmlspecialchars($alt) . '</p>' : '') . '
            </div>';
        }, $html);

        // Convert links [text](url) to clickable links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" class="text-blue-600 hover:text-blue-800 underline font-medium">$1</a>', $html);

        // Convert bold text
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-semibold text-gray-900">$1</strong>', $html);

        // Convert headers first (from most specific to least specific)
        $html = preg_replace('/^### (.+)$/m', '<h3 class="text-lg font-semibold text-gray-800 mt-6 mb-3 border-l-4 border-blue-500 pl-3">$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2 class="text-xl font-bold text-gray-900 mt-8 mb-4 border-l-4 border-purple-500 pl-3">$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1 class="text-2xl font-bold text-gray-900 mt-8 mb-5 border-l-4 border-pink-500 pl-3">$1</h1>', $html);

        // Convert numbered lists with improved regex - handle various numbering formats
        $html = preg_replace_callback('/(?:^[ ]*\d+[\.\):][ ]+.+(?:\n|$))+/m', function ($matches) {
            $listBlock = trim($matches[0]);
            $lines = explode("\n", $listBlock);

            $html = '<ol class="list-decimal list-inside ml-4 my-4 space-y-2 bg-gray-50 p-4 rounded-lg border-l-4 border-blue-300">';
            foreach ($lines as $line) {
                $line = trim($line);
                // More flexible pattern for numbered items
                if (preg_match('/^\d+[\.\):][ ]+(.+)$/', $line, $match)) {
                    $itemText = trim($match[1]);
                    $html .= '<li class="text-gray-700 leading-relaxed pl-2">' . $itemText . '</li>';
                }
            }
            $html .= '</ol>';
            return $html;
        }, $html);

        // Also handle simple numbered lists that might be missed
        $html = preg_replace_callback('/(?:^\d+[\.\):][ ]+.+$)/m', function ($matches) {
            $line = trim($matches[0]);
            if (preg_match('/^\d+[\.\):][ ]+(.+)$/', $line, $match)) {
                $itemText = trim($match[1]);
                return '<div class="ml-4 my-2 p-2 bg-blue-50 rounded border-l-2 border-blue-400">
                    <span class="text-blue-600 font-medium">•</span>
                    <span class="text-gray-700">' . $itemText . '</span>
                </div>';
            }
            return $matches[0];
        }, $html);

        // Convert bullet lists with improved styling and more flexible patterns
        $html = preg_replace_callback('/(?:^[ ]*[-*•][ ]+.+(?:\n|$))+/m', function ($matches) {
            $listBlock = trim($matches[0]);
            $lines = explode("\n", $listBlock);

            $html = '<ul class="list-none ml-4 my-4 space-y-2 bg-gray-50 p-4 rounded-lg border-l-4 border-green-300">';
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^[-*•][ ]+(.+)$/', $line, $match)) {
                    $itemText = trim($match[1]);
                    $html .= '<li class="text-gray-700 leading-relaxed pl-2">
                        <span class="text-green-600 font-medium mr-2">•</span>' . $itemText . '
                    </li>';
                }
            }
            $html .= '</ul>';
            return $html;
        }, $html);

        // Also handle simple bullet lists that might be missed
        $html = preg_replace_callback('/(?:^[-*•][ ]+.+$)/m', function ($matches) {
            $line = trim($matches[0]);
            if (preg_match('/^[-*•][ ]+(.+)$/', $line, $match)) {
                $itemText = trim($match[1]);
                return '<div class="ml-4 my-2 p-2 bg-green-50 rounded border-l-2 border-green-400">
                    <span class="text-green-600 font-medium">•</span>
                    <span class="text-gray-700">' . $itemText . '</span>
                </div>';
            }
            return $matches[0];
        }, $html);

        // Split content into paragraphs and process
        $paragraphs = preg_split('/\n\s*\n/', $html);
        $processedParagraphs = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;

            // Skip if already HTML block elements
            if (preg_match('/^<(h[1-6]|ol|ul|div|p)[\s>]/', $paragraph)) {
                $processedParagraphs[] = $paragraph;
            } else {
                // Convert single newlines to <br> and wrap in paragraph
                $paragraph = preg_replace('/(?<!\>)\n(?!\<)/', '<br>', $paragraph);
                $processedParagraphs[] = '<p class="mb-4 text-gray-700 leading-relaxed">' . $paragraph . '</p>';
            }
        }

        $html = implode("\n\n", $processedParagraphs);

        return $html;
    }
}

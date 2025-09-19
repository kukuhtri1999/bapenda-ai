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
            return $richContentData['text'];
        }

        $formatted = $richContentData['text'];

        // Add instructions for rich content elements
        $richInstructions = [];

        foreach ($richContentData['elements'] as $element) {
            switch ($element['type']) {
                case 'link':
                    $richInstructions[] = "Link: " . $element['text'] . " - " . $element['url'];
                    break;

                case 'image':
                    $richInstructions[] = "Gambar: " . $element['alt'] . " (" . $element['src'] . ")";
                    if (!empty($element['caption'])) {
                        $richInstructions[] = "Keterangan: " . $element['caption'];
                    }
                    break;

                case 'header':
                    $richInstructions[] = "Judul (Level " . $element['level'] . "): " . $element['text'];
                    break;

                case 'list':
                    $listType = $element['ordered'] ? 'Daftar Berurut' : 'Daftar';
                    $richInstructions[] = $listType . ": " . implode(', ', $element['items']);
                    break;
            }
        }

        if (!empty($richInstructions)) {
            $formatted .= "\n\nElemen Tambahan:\n" . implode("\n", $richInstructions);
        }

        return $formatted;
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
                    $instructions[] = "Referensikan gambar: " . $element['alt'];
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
                "\nJawaban harus menyertakan semua elemen di atas dengan format yang sesuai.";
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

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

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
            $src = $matches[2];

            // Check if it's a storage path and convert to full URL
            if (strpos($src, '/storage/') === 0) {
                $src = url($src);
            }

            return '<div class="my-4">
                <a href="' . $src . '" class="kb-lightbox inline-block">
                    <img src="' . $src . '" alt="' . $alt . '" class="max-w-full h-auto rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer" loading="lazy" />
                </a>
                <p class="text-sm text-gray-600 mt-2 italic">' . $alt . '</p>
            </div>';
        }, $html);

        // Convert links [text](url) to clickable links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" class="text-blue-600 hover:text-blue-800 underline">$1</a>', $html);

        // Convert bold text
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-semibold">$1</strong>', $html);

        // Convert headers first (from most specific to least specific)
        $html = preg_replace('/^### (.+)$/m', '<h3 class="text-lg font-semibold text-gray-800 mt-4 mb-2">$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2 class="text-xl font-bold text-gray-900 mt-6 mb-3">$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1 class="text-2xl font-bold text-gray-900 mt-6 mb-4">$1</h1>', $html);

        // Convert numbered lists (handle complete list blocks)
        $html = preg_replace_callback('/(?:^[ ]*\d+\.[ ]+.+(?:\n|$))+/m', function ($matches) {
            $listBlock = $matches[0];
            $lines = explode("\n", trim($listBlock));

            $html = '<ol class="list-decimal ml-6 my-3 space-y-1">';
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^\d+\.[ ]+(.+)$/', $line, $match)) {
                    $itemText = trim($match[1]);
                    $html .= '<li class="text-gray-700">' . $itemText . '</li>';
                }
            }
            $html .= '</ol>';
            return $html;
        }, $html);

        // Convert bullet lists (handle complete list blocks)
        $html = preg_replace_callback('/(?:^[ ]*[-*•][ ]+.+(?:\n|$))+/m', function ($matches) {
            $listBlock = $matches[0];
            $lines = explode("\n", trim($listBlock));

            $html = '<ul class="list-disc ml-6 my-3 space-y-1">';
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^[-*•][ ]+(.+)$/', $line, $match)) {
                    $itemText = trim($match[1]);
                    $html .= '<li class="text-gray-700">' . $itemText . '</li>';
                }
            }
            $html .= '</ul>';
            return $html;
        }, $html);

        // Split content into paragraphs and process
        $paragraphs = preg_split('/\n\s*\n/', $html);
        $processedParagraphs = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;

            // Skip if already HTML block elements
            if (preg_match('/^<(h[1-6]|ol|ul|div)[\s>]/', $paragraph)) {
                $processedParagraphs[] = $paragraph;
            } else {
                // Convert single newlines to <br> and wrap in paragraph
                $paragraph = preg_replace('/\n/', '<br>', $paragraph);
                $processedParagraphs[] = '<p class="mb-3">' . $paragraph . '</p>';
            }
        }

        $html = implode("\n", $processedParagraphs);

        return $html;
    }
}

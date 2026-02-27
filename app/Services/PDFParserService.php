<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class PDFParserService
{
  private Parser $parser;

  public function __construct()
  {
    $this->parser = new Parser();
  }

  /**
   * Extract text from PDF file
   */
  public function extractText(UploadedFile $file): ?string
  {
    try {
      $pdf = $this->parser->parseFile($file->getRealPath());
      $text = $pdf->getText();

      return $this->cleanExtractedText($text);
    } catch (Exception $e) {
      Log::error("Failed to extract text from PDF: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Extract text from PDF file path
   */
  public function extractTextFromPath(string $filePath): ?string
  {
    try {
      $pdf = $this->parser->parseFile($filePath);
      $text = $pdf->getText();

      return $this->cleanExtractedText($text);
    } catch (Exception $e) {
      Log::error("Failed to extract text from PDF path: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Extract metadata from PDF
   */
  public function extractMetadata(UploadedFile $file): array
  {
    try {
      $pdf = $this->parser->parseFile($file->getRealPath());
      $details = $pdf->getDetails();

      return [
        'title' => $details['Title'] ?? null,
        'author' => $details['Author'] ?? null,
        'subject' => $details['Subject'] ?? null,
        'creator' => $details['Creator'] ?? null,
        'producer' => $details['Producer'] ?? null,
        'creation_date' => $details['CreationDate'] ?? null,
        'modification_date' => $details['ModDate'] ?? null,
        'page_count' => count($pdf->getPages()),
      ];
    } catch (Exception $e) {
      Log::error("Failed to extract PDF metadata: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Extract text by pages
   */
  public function extractTextByPages(UploadedFile $file): array
  {
    try {
      $pdf = $this->parser->parseFile($file->getRealPath());
      $pages = $pdf->getPages();
      $pageTexts = [];

      foreach ($pages as $pageNumber => $page) {
        $text = $page->getText();
        $pageTexts[$pageNumber + 1] = $this->cleanExtractedText($text);
      }

      return $pageTexts;
    } catch (Exception $e) {
      Log::error("Failed to extract text by pages: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Check if file is a valid PDF
   */
  public function isValidPDF(UploadedFile $file): bool
  {
    try {
      $this->parser->parseFile($file->getRealPath());
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  /**
   * Clean extracted text — preserves newlines so paragraph structure survives chunking.
   */
  private function cleanExtractedText(string $text): string
  {
    // Normalize line endings first
    $text = preg_replace('/(\r\n|\r)/', "\n", $text);

    // Remove common PDF artifacts
    $text = preg_replace('/\x00/', '', $text); // Remove null bytes
    $text = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text); // Remove control characters

    // Fix encoding issues
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    // Collapse ONLY horizontal whitespace (spaces/tabs) within each line — DO NOT touch newlines
    $text = preg_replace('/[^\S\n]+/', ' ', $text);

    // Remove leading/trailing spaces on every line
    $text = preg_replace('/^ +/m', '', $text);
    $text = preg_replace('/ +$/m', '', $text);

    // Collapse 3+ consecutive blank lines to a single paragraph break
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    // Remove excessive punctuation
    $text = preg_replace('/\.{3,}/', '...', $text);
    $text = preg_replace('/\-{3,}/', '---', $text);

    return trim($text);
  }

  /**
   * Clean text from a single PDF page — preserves paragraph breaks.
   */
  private function cleanPageText(string $text): string
  {
    $text = preg_replace('/\x00/', '', $text);
    $text = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    $text = preg_replace('/(\r\n|\r)/', "\n", $text);
    $text = preg_replace('/[^\S\n]+/', ' ', $text); // only collapse horizontal spaces
    $text = preg_replace('/^ +/m', '', $text);
    $text = preg_replace('/ +$/m', '', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
  }

  /**
   * Extract text page-by-page to preserve paragraph/section structure.
   * Falls back to flat extraction if page parsing fails.
   */
  public function extractTextPreservingStructure(UploadedFile $file): ?string
  {
    try {
      $pdf = $this->parser->parseFile($file->getRealPath());
      $pages = $pdf->getPages();

      if (empty($pages)) {
        return $this->extractText($file);
      }

      $pageTexts = [];
      foreach ($pages as $page) {
        $pageText = $page->getText();
        if (trim($pageText) !== '') {
          $cleaned = $this->cleanPageText($pageText);
          if (trim($cleaned) !== '') {
            $pageTexts[] = $cleaned;
          }
        }
      }

      if (empty($pageTexts)) {
        return null;
      }

      // Join pages with a clear page-break marker (double newline = paragraph break)
      return implode("\n\n", $pageTexts);
    } catch (Exception $e) {
      Log::warning('Page-by-page PDF extraction failed, trying flat: ' . $e->getMessage());
      return $this->extractText($file);
    }
  }

  /**
   * Extract text from PDF path, page-by-page for better structure preservation.
   */
  public function extractTextPreservingStructureFromPath(string $filePath): ?string
  {
    try {
      $pdf = $this->parser->parseFile($filePath);
      $pages = $pdf->getPages();

      if (empty($pages)) {
        return $this->extractTextFromPath($filePath);
      }

      $pageTexts = [];
      foreach ($pages as $page) {
        $pageText = $page->getText();
        if (trim($pageText) !== '') {
          $cleaned = $this->cleanPageText($pageText);
          if (trim($cleaned) !== '') {
            $pageTexts[] = $cleaned;
          }
        }
      }

      if (empty($pageTexts)) {
        return null;
      }

      return implode("\n\n", $pageTexts);
    } catch (Exception $e) {
      Log::warning('Page-by-page PDF path extraction failed, trying flat: ' . $e->getMessage());
      return $this->extractTextFromPath($filePath);
    }
  }

  /**
   * Process PDF for knowledge base entry
   */
  public function processPDFForKnowledgeBase(UploadedFile $file): array
  {
    // Extract metadata first (independent of text extraction)
    $metadata = $this->extractMetadata($file);

    // Use page-by-page extraction to preserve paragraph/section structure
    $text = $this->extractTextPreservingStructure($file);

    if (!$text || trim($text) === '') {
      throw new Exception('Could not extract text from PDF. The file may be scanned/image-based.');
    }

    // Generate title from metadata or filename
    $title = !empty($metadata['title'])
      ? trim($metadata['title'])
      : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

    // Create excerpt from first 300 characters of plain text
    $plainText = strip_tags($text);
    $excerpt = mb_strlen($plainText) > 300 ? mb_substr($plainText, 0, 297) . '...' : $plainText;

    return [
      'title' => $title,
      'content' => $text,
      'excerpt' => $excerpt,
      'metadata' => $metadata,
      'source_type' => 'file',
      'mime_type' => $file->getMimeType(),
      'file_size' => $file->getSize(),
    ];
  }
}

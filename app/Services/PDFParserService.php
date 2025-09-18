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
   * Clean extracted text
   */
  private function cleanExtractedText(string $text): string
  {
    // Remove excessive whitespace and line breaks
    $text = preg_replace('/\s+/', ' ', $text);

    // Remove common PDF artifacts
    $text = preg_replace('/\x00/', '', $text); // Remove null bytes
    $text = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text); // Remove control characters

    // Fix encoding issues
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    // Remove excessive punctuation
    $text = preg_replace('/\.{3,}/', '...', $text);
    $text = preg_replace('/\-{3,}/', '---', $text);

    // Clean up spacing around punctuation
    $text = preg_replace('/\s+([,.!?;:])/', '$1', $text);
    $text = preg_replace('/([.!?])\s*([A-Z])/', '$1 $2', $text);

    return trim($text);
  }

  /**
   * Process PDF for knowledge base entry
   */
  public function processPDFForKnowledgeBase(UploadedFile $file): array
  {
    $text = $this->extractText($file);
    $metadata = $this->extractMetadata($file);

    if (!$text) {
      throw new Exception('Could not extract text from PDF');
    }

    // Generate title from metadata or filename
    $title = $metadata['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

    // Create excerpt from first 200 characters
    $excerpt = strlen($text) > 200 ? substr($text, 0, 197) . '...' : $text;

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

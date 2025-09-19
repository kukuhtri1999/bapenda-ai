<?php

namespace App\Services;

use Illuminate\Support\Str;

class DocumentChunkingService
{
  /**
   * Maximum characters per chunk (optimal for embeddings and RAG)
   */
  private const MAX_CHUNK_SIZE = 1500;

  /**
   * Overlap between chunks to maintain context
   */
  private const CHUNK_OVERLAP = 200;

  /**
   * Minimum chunk size to avoid tiny fragments
   */
  private const MIN_CHUNK_SIZE = 300;

  /**
   * Chunk a large document into smaller pieces optimized for RAG
   *
   * @param string $content The full document content
   * @param string $title The document title (used for context)
   * @return array Array of chunks with metadata
   */
  public function chunkDocument(string $content, string $title = ''): array
  {
    // Clean and prepare content
    $content = $this->cleanContent($content);

    // If content is small enough, return as single chunk
    if (strlen($content) <= self::MAX_CHUNK_SIZE) {
      return [
        [
          'content' => $content,
          'chunk_index' => 0,
          'total_chunks' => 1,
          'title' => $title,
          'char_count' => strlen($content),
          'word_count' => str_word_count($content)
        ]
      ];
    }

    // Split by paragraphs first for better semantic boundaries
    $paragraphs = $this->splitIntoParagraphs($content);

    // Group paragraphs into chunks
    $chunks = $this->groupParagraphsIntoChunks($paragraphs, $title);

    return $chunks;
  }

  /**
   * Clean the content by removing excessive whitespace and formatting
   */
  private function cleanContent(string $content): string
  {
    // Remove excessive whitespace
    $content = preg_replace('/\s+/', ' ', $content);

    // Remove HTML tags if present
    $content = strip_tags($content);

    // Normalize line breaks
    $content = str_replace(["\r\n", "\r"], "\n", $content);

    // Remove excessive blank lines
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

    return trim($content);
  }

  /**
   * Split content into paragraphs for better semantic chunking
   */
  private function splitIntoParagraphs(string $content): array
  {
    // Split by double line breaks (paragraph boundaries)
    $paragraphs = preg_split('/\n\s*\n/', $content);

    // Filter out empty paragraphs
    $paragraphs = array_filter($paragraphs, function ($p) {
      return trim($p) !== '';
    });

    return array_values($paragraphs);
  }

  /**
   * Group paragraphs into appropriately sized chunks
   */
  private function groupParagraphsIntoChunks(array $paragraphs, string $title): array
  {
    $chunks = [];
    $currentChunk = '';
    $chunkIndex = 0;

    foreach ($paragraphs as $paragraph) {
      $paragraph = trim($paragraph);

      // If adding this paragraph would exceed the limit
      if (strlen($currentChunk . "\n\n" . $paragraph) > self::MAX_CHUNK_SIZE) {
        // Save current chunk if it's substantial
        if (strlen($currentChunk) >= self::MIN_CHUNK_SIZE) {
          $chunks[] = $this->createChunk($currentChunk, $chunkIndex, $title);
          $chunkIndex++;

          // Start new chunk with overlap from previous chunk
          $currentChunk = $this->getOverlapText($currentChunk) . $paragraph;
        } else {
          // If current chunk is too small, just add the paragraph
          $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
        }
      } else {
        // Add paragraph to current chunk
        $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
      }

      // If a single paragraph is too large, split it by sentences
      if (strlen($paragraph) > self::MAX_CHUNK_SIZE) {
        $sentenceChunks = $this->splitLargeParagraph($paragraph, $title, $chunkIndex);
        $chunks = array_merge($chunks, $sentenceChunks);
        $chunkIndex += count($sentenceChunks);
        $currentChunk = '';
      }
    }

    // Add the final chunk if it exists
    if (!empty($currentChunk) && strlen($currentChunk) >= self::MIN_CHUNK_SIZE) {
      $chunks[] = $this->createChunk($currentChunk, $chunkIndex, $title);
    }

    // Update total_chunks for all chunks
    $totalChunks = count($chunks);
    foreach ($chunks as &$chunk) {
      $chunk['total_chunks'] = $totalChunks;
    }

    return $chunks;
  }

  /**
   * Get overlap text from the end of a chunk
   */
  private function getOverlapText(string $text): string
  {
    if (strlen($text) <= self::CHUNK_OVERLAP) {
      return $text . "\n\n";
    }

    // Get the last portion for overlap
    $overlap = substr($text, -self::CHUNK_OVERLAP);

    // Try to break at a sentence boundary
    $lastSentence = strrpos($overlap, '.');
    if ($lastSentence !== false) {
      $overlap = substr($overlap, $lastSentence + 1);
    }

    return trim($overlap) . "\n\n";
  }

  /**
   * Split a very large paragraph by sentences
   */
  private function splitLargeParagraph(string $paragraph, string $title, int &$chunkIndex): array
  {
    $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph);
    $chunks = [];
    $currentChunk = '';

    foreach ($sentences as $sentence) {
      if (strlen($currentChunk . ' ' . $sentence) > self::MAX_CHUNK_SIZE) {
        if (!empty($currentChunk)) {
          $chunks[] = $this->createChunk($currentChunk, $chunkIndex, $title);
          $chunkIndex++;
          $currentChunk = $sentence;
        } else {
          // Single sentence too long, truncate it
          $chunks[] = $this->createChunk(substr($sentence, 0, self::MAX_CHUNK_SIZE), $chunkIndex, $title);
          $chunkIndex++;
        }
      } else {
        $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
      }
    }

    if (!empty($currentChunk)) {
      $chunks[] = $this->createChunk($currentChunk, $chunkIndex, $title);
    }

    return $chunks;
  }

  /**
   * Create a chunk with metadata
   */
  private function createChunk(string $content, int $index, string $title): array
  {
    $content = trim($content);

    return [
      'content' => $content,
      'chunk_index' => $index,
      'total_chunks' => 0, // Will be updated later
      'title' => $title,
      'char_count' => strlen($content),
      'word_count' => str_word_count($content),
      'chunk_summary' => $this->generateChunkSummary($content, 100)
    ];
  }

  /**
   * Generate a brief summary of the chunk content
   */
  private function generateChunkSummary(string $content, int $maxLength = 100): string
  {
    $words = explode(' ', $content);
    $summary = '';

    foreach ($words as $word) {
      if (strlen($summary . ' ' . $word) > $maxLength) {
        break;
      }
      $summary .= ($summary ? ' ' : '') . $word;
    }

    return $summary . (strlen($content) > strlen($summary) ? '...' : '');
  }
}

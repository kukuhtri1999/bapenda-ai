<?php

namespace App\Services;

use OpenAI\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class EmbeddingService
{
  private Client $client;
  private string $model;

  public function __construct()
  {
    $this->client = \OpenAI::client(config('services.openai.api_key'));
    $this->model = config('services.openai.embedding_model');
  }

  /**
   * Generate embeddings for a single text
   */
  public function embed(string $text): ?array
  {
    try {
      $response = $this->client->embeddings()->create([
        'model' => $this->model,
        'input' => $text,
      ]);

      return $response->embeddings[0]->embedding ?? null;
    } catch (Exception $e) {
      Log::error("Failed to generate embedding: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Generate embeddings for multiple texts (batch)
   */
  public function embedBatch(array $texts): array
  {
    try {
      $response = $this->client->embeddings()->create([
        'model' => $this->model,
        'input' => $texts,
      ]);

      $embeddings = [];
      foreach ($response->embeddings as $embedding) {
        $embeddings[] = $embedding->embedding;
      }

      return $embeddings;
    } catch (Exception $e) {
      Log::error("Failed to generate batch embeddings: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Chunk text into smaller segments for embedding
   * For knowledge base entries, we want to preserve full content when possible
   */
  public function chunkText(string $text, int $maxChunkSize = 8000, int $overlap = 200): array
  {
    // Clean and normalize text
    $text = $this->cleanText($text);

    // For shorter texts, return as single chunk to preserve full content
    if (strlen($text) <= $maxChunkSize) {
      return [$text];
    }

    $chunks = [];
    $sentences = $this->splitIntoSentences($text);
    $currentChunk = '';

    foreach ($sentences as $sentence) {
      $testChunk = $currentChunk . ($currentChunk ? ' ' : '') . $sentence;

      if (strlen($testChunk) <= $maxChunkSize) {
        $currentChunk = $testChunk;
      } else {
        if (!empty($currentChunk)) {
          $chunks[] = $currentChunk;

          // Add overlap from the end of current chunk
          $overlapText = $this->getOverlapText($currentChunk, $overlap);
          $currentChunk = $overlapText . ($overlapText ? ' ' : '') . $sentence;
        } else {
          // Sentence is too long, split it by character
          $chunks[] = substr($sentence, 0, $maxChunkSize);
          $currentChunk = '';
        }
      }
    }

    if (!empty($currentChunk)) {
      $chunks[] = $currentChunk;
    }

    return array_filter($chunks, fn($chunk) => trim($chunk) !== '');
  }

  /**
   * Clean text for processing
   */
  private function cleanText(string $text): string
  {
    // Remove HTML tags
    $text = strip_tags($text);

    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', $text);

    // Remove extra punctuation
    $text = preg_replace('/[^\p{L}\p{N}\p{P}\s]/u', '', $text);

    return trim($text);
  }

  /**
   * Split text into sentences
   */
  private function splitIntoSentences(string $text): array
  {
    // Split by sentence-ending punctuation, keeping the punctuation
    $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

    return array_map('trim', $sentences);
  }

  /**
   * Get overlap text from the end of a chunk
   */
  private function getOverlapText(string $text, int $overlapLength): string
  {
    if (strlen($text) <= $overlapLength) {
      return $text;
    }

    // Try to get overlap at word boundary
    $overlap = substr($text, -$overlapLength);
    $spacePos = strpos($overlap, ' ');

    if ($spacePos !== false) {
      return substr($overlap, $spacePos + 1);
    }

    return $overlap;
  }

  /**
   * Create vector data for Pinecone
   */
  public function createVectorData(string $id, array $embedding, array $metadata): array
  {
    return [
      'id' => $id,
      'values' => $embedding,
      'metadata' => $metadata
    ];
  }
}

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
     * Clean text for processing while preserving URLs and proper spacing
     */
    private function cleanText(string $text): string
    {
        // Remove HTML tags but preserve URLs in href attributes
        $text = strip_tags($text);

        // Find and temporarily replace URLs to preserve special characters
        $urlPattern = '/(https?:\/\/[^\s]+)/i';
        $urls = [];
        $text = preg_replace_callback($urlPattern, function ($matches) use (&$urls) {
            $placeholder = '___URL_PLACEHOLDER_' . count($urls) . '___';
            $urls[$placeholder] = $matches[1];
            return $placeholder;
        }, $text);

        // Preserve proper spacing - only normalize excessive whitespace but keep paragraph breaks
        $text = preg_replace('/[ \t]+/', ' ', $text); // Multiple spaces/tabs to single space
        $text = preg_replace('/\n{3,}/', "\n\n", $text); // Multiple newlines to double newline max
        $text = preg_replace('/(\r\n|\r)/', "\n", $text); // Normalize line endings

        // Remove unwanted special characters but preserve basic punctuation and URL characters
        // Keep: letters, numbers, basic punctuation, spaces, newlines, and URL special chars
        $text = preg_replace('/[^\p{L}\p{N}.,;:!?()\[\]{}"\'\/\\\s\-_@#$%&*+=|~`<>=\n]/u', '', $text);

        // Restore URLs with their original special characters
        foreach ($urls as $placeholder => $originalUrl) {
            $text = str_replace($placeholder, $originalUrl, $text);
        }

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

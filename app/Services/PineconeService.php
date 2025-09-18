<?php

namespace App\Services;

use Probots\Pinecone\Client as PineconeClient;
use Illuminate\Support\Facades\Log;
use Exception;

class PineconeService
{
    private PineconeClient $client;
    private string $indexName;
    private int $dimension;

    public function __construct()
    {
        $this->client = new PineconeClient(
            apiKey: config('services.pinecone.api_key')
        );
        $this->indexName = config('services.pinecone.index_name');
        $this->dimension = config('services.pinecone.dimension');
    }

    /**
     * Create index if it doesn't exist
     */
    public function createIndex(): bool
    {
        try {
            // Check if index exists
            $response = $this->client->control()->index()->list();
            $indexes = $response->json();

            foreach ($indexes['indexes'] ?? [] as $index) {
                if ($index['name'] === $this->indexName) {
                    Log::info("Pinecone index {$this->indexName} already exists");
                    return true;
                }
            }

            // Create new serverless index
            Log::info("Creating Pinecone index: {$this->indexName}");
            $response = $this->client->control()->index($this->indexName)->createServerless(
                dimension: $this->dimension,
                metric: 'cosine',
                cloud: 'aws',
                region: 'us-east-1'
            );

            if ($response->successful()) {
                Log::info("Pinecone index {$this->indexName} created successfully");
                return true;
            }

            Log::error("Failed to create Pinecone index", ['response' => $response->body()]);
            return false;
        } catch (Exception $e) {
            Log::error("Failed to create Pinecone index: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upsert vectors to index
     */
    public function upsert(array $vectors): bool
    {
        try {
            // Get index host
            $indexHost = $this->getIndexHost();
            if (!$indexHost) {
                return false;
            }

            $this->client->setIndexHost($indexHost);
            $response = $this->client->data()->vectors()->upsert($vectors);

            return $response->successful();
        } catch (Exception $e) {
            Log::error("Failed to upsert vectors to Pinecone: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Query vectors by similarity
     */
    public function query(array $vector, int $topK = 5, array $filter = null): array
    {
        try {
            // Get index host
            $indexHost = $this->getIndexHost();
            if (!$indexHost) {
                return [];
            }

            $this->client->setIndexHost($indexHost);
            $response = $this->client->data()->vectors()->query(
                vector: $vector,
                topK: $topK,
                includeMetadata: true,
                filter: $filter ?: []
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['matches'] ?? [];
            }

            return [];
        } catch (Exception $e) {
            Log::error("Failed to query vectors from Pinecone: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete all vectors with specific metadata filter
     */
    public function deleteByFilter(array $filter): bool
    {
        try {
            // Get index host
            $indexHost = $this->getIndexHost();
            if (!$indexHost) {
                return false;
            }

            $this->client->setIndexHost($indexHost);
            $response = $this->client->data()->vectors()->delete(
                filter: $filter
            );

            return $response->successful();
        } catch (Exception $e) {
            Log::error("Failed to delete vectors from Pinecone: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get index host URL
     */
    private function getIndexHost(): ?string
    {
        try {
            $response = $this->client->control()->index($this->indexName)->describe();

            if ($response->successful()) {
                $indexData = $response->json();
                $host = $indexData['host'] ?? null;

                // Ensure HTTPS protocol
                if ($host && !str_starts_with($host, 'http')) {
                    $host = 'https://' . $host;
                }

                return $host;
            }

            return null;
        } catch (Exception $e) {
            Log::error("Failed to get index host: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get index statistics
     */
    public function getStats(): array
    {
        try {
            $indexHost = $this->getIndexHost();
            if (!$indexHost) {
                return [];
            }

            $this->client->setIndexHost($indexHost);
            $response = $this->client->data()->vectors()->stats();

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (Exception $e) {
            Log::error("Failed to get index stats: " . $e->getMessage());
            return [];
        }
    }
}

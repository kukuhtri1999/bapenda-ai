<?php

namespace App\Services;

use Probots\Pinecone\Client as PineconeClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class PineconeService
{
    private PineconeClient $client;
    private string $indexName;
    private string $apiKey;
    private int $dimension;

    public function __construct()
    {
        $this->apiKey = config('services.pinecone.api_key');
        $this->client = new PineconeClient(
            apiKey: $this->apiKey
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
     * Delete ALL vectors from the index (clear entire database)
     */
    public function deleteAll(): bool
    {
        try {
            $maxRetries = 3;
            $retryDelay = 1; // seconds

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                Log::info("Delete all attempt {$attempt}/{$maxRetries}");

                $host = $this->getIndexHost();
                if (!$host) {
                    Log::error("Failed to get index host on attempt {$attempt}");
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                    return false;
                }

                // Step 1: Try Delete All method with deleteAll flag
                $response = Http::timeout(30)->withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$host}/vectors/delete", [
                    'deleteAll' => true
                ]);

                if ($response->successful()) {
                    Log::info('Successfully cleared all vectors using deleteAll method');
                    return true;
                }

                Log::warning("Delete all method failed on attempt {$attempt}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Step 2: Try Namespace Deletion approach if deleteAll failed
                $response = Http::timeout(30)->withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$host}/vectors/delete", [
                    'deleteAll' => true,
                    'namespace' => ''
                ]);

                if ($response->successful()) {
                    Log::info('Successfully cleared all vectors using namespace method');
                    return true;
                }

                // Step 3: Try alternative namespace approach
                $response = Http::timeout(30)->withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$host}/vectors/delete", [
                    'namespace' => '',
                    'deleteAll' => true
                ]);

                if ($response->successful()) {
                    Log::info('Successfully cleared vectors from default namespace');
                    return true;
                }

                if ($attempt < $maxRetries) {
                    Log::info("Retrying in {$retryDelay} seconds...");
                    sleep($retryDelay);
                }
            }

            Log::error('All deletion methods failed after maximum retries');
            return false;
        } catch (\Exception $e) {
            Log::error('Exception during deleteAll operation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get index host URL
     */
    private function getIndexHost(): ?string
    {
        try {
            $maxRetries = 3;
            $retryDelay = 1; // seconds

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                Log::info("Getting index host attempt {$attempt}/{$maxRetries}");

                $response = Http::timeout(30)->withHeaders([
                    'Api-Key' => $this->apiKey,
                ])->get("https://api.pinecone.io/indexes/{$this->indexName}");

                if ($response->successful()) {
                    $indexData = $response->json();

                    // Try multiple possible host locations in the response
                    $host = null;
                    if (isset($indexData['host'])) {
                        $host = $indexData['host'];
                    } elseif (isset($indexData['status']['host'])) {
                        $host = $indexData['status']['host'];
                    }

                    if ($host) {
                        // Ensure the host has the https:// prefix
                        if (!str_starts_with($host, 'http')) {
                            $host = 'https://' . $host;
                        }
                        Log::info("Successfully retrieved index host: {$host}");
                        return $host;
                    }

                    Log::warning("Host not found in response data", ['data' => $indexData]);
                } else {
                    Log::warning("Failed to get index host on attempt {$attempt}", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }

                if ($attempt < $maxRetries) {
                    Log::info("Retrying host retrieval in {$retryDelay} seconds...");
                    sleep($retryDelay);
                }
            }

            Log::error("Failed to get index host after {$maxRetries} attempts");
            return null;
        } catch (\Exception $e) {
            Log::error('Exception when getting index host', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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

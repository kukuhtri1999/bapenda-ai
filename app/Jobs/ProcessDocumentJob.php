<?php

namespace App\Jobs;

use App\Models\KnowledgeBase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    protected KnowledgeBase $knowledgeBase;

    /**
     * Create a new job instance.
     */
    public function __construct(KnowledgeBase $knowledgeBase)
    {
        $this->knowledgeBase = $knowledgeBase;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->knowledgeBase->update(['processing_status' => 'processing']);

            $filePath = Storage::path($this->knowledgeBase->file_path);
            $extractedText = '';

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            switch (strtolower($this->knowledgeBase->file_type)) {
                case 'pdf':
                    $extractedText = $this->extractFromPdf($filePath);
                    break;
                
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $extractedText = $this->extractFromImage($filePath);
                    break;
                
                default:
                    throw new \Exception('Unsupported file type: ' . $this->knowledgeBase->file_type);
            }

            if (empty(trim($extractedText))) {
                throw new \Exception('No text could be extracted from the document');
            }

            // Update the knowledge base with extracted content
            $this->knowledgeBase->update([
                'content' => trim($extractedText),
                'processing_status' => 'completed',
                'processing_error' => null,
                'metadata' => [
                    'extraction_method' => $this->getExtractionMethod(),
                    'processed_at' => now()->toISOString(),
                    'word_count' => str_word_count($extractedText),
                    'character_count' => strlen($extractedText),
                ]
            ]);

            Log::info('Document processed successfully', [
                'knowledge_base_id' => $this->knowledgeBase->id,
                'file_name' => $this->knowledgeBase->file_name,
                'content_length' => strlen($extractedText),
            ]);

        } catch (\Exception $e) {
            $this->knowledgeBase->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage()
            ]);

            Log::error('Document processing failed', [
                'knowledge_base_id' => $this->knowledgeBase->id,
                'file_name' => $this->knowledgeBase->file_name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Extract text from PDF file
     */
    private function extractFromPdf(string $filePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            // If PDF text extraction is empty or minimal, try OCR
            if (strlen(trim($text)) < 50) {
                Log::info('PDF text extraction yielded minimal content, attempting OCR', [
                    'file' => $filePath,
                    'extracted_length' => strlen($text)
                ]);
                
                // Convert PDF to images and then OCR (requires ImageMagick)
                $text = $this->ocrPdfAsImages($filePath);
            }

            return $text;
        } catch (\Exception $e) {
            Log::warning('PDF text extraction failed, attempting OCR', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to OCR
            return $this->ocrPdfAsImages($filePath);
        }
    }

    /**
     * Extract text from image file using OCR
     */
    private function extractFromImage(string $filePath): string
    {
        try {
            $ocr = new TesseractOCR($filePath);
            $ocr->lang('eng', 'ind'); // English and Indonesian
            $ocr->config('preserve_interword_spaces', 1);
            
            return $ocr->run();
        } catch (\Exception $e) {
            Log::error('OCR processing failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('OCR processing failed: ' . $e->getMessage());
        }
    }

    /**
     * OCR PDF by converting to images first
     */
    private function ocrPdfAsImages(string $filePath): string
    {
        try {
            // This requires ImageMagick to be installed
            $tempDir = sys_get_temp_dir() . '/pdf_ocr_' . uniqid();
            mkdir($tempDir);

            // Convert PDF pages to images
            $command = "magick -density 300 \"{$filePath}\" -quality 100 \"{$tempDir}/page.png\"";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('PDF to image conversion failed');
            }

            $extractedText = '';
            $imageFiles = glob($tempDir . '/page*.png');

            foreach ($imageFiles as $imageFile) {
                $ocr = new TesseractOCR($imageFile);
                $ocr->lang('eng', 'ind');
                $extractedText .= $ocr->run() . "\n\n";
            }

            // Clean up temporary files
            array_map('unlink', $imageFiles);
            rmdir($tempDir);

            return $extractedText;
        } catch (\Exception $e) {
            Log::error('PDF OCR processing failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('PDF OCR processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the extraction method used
     */
    private function getExtractionMethod(): string
    {
        switch (strtolower($this->knowledgeBase->file_type)) {
            case 'pdf':
                return 'pdf_parser_with_ocr_fallback';
            case 'jpg':
            case 'jpeg':
            case 'png':
                return 'tesseract_ocr';
            default:
                return 'unknown';
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->knowledgeBase->update([
            'processing_status' => 'failed',
            'processing_error' => $exception->getMessage()
        ]);

        Log::error('Document processing job failed permanently', [
            'knowledge_base_id' => $this->knowledgeBase->id,
            'file_name' => $this->knowledgeBase->file_name,
            'error' => $exception->getMessage()
        ]);
    }
}

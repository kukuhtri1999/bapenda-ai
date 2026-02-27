<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;
use ZipArchive;

class DocxParserService
{
  /**
   * Extract plain text from a .doc or .docx uploaded file.
   * DOC is a legacy binary format — we attempt a best-effort byte extraction.
   * DOCX is a ZIP/XML format handled precisely via ZipArchive.
   */
  public function extractText(UploadedFile $file): ?string
  {
    $extension = strtolower($file->getClientOriginalExtension());

    try {
      if ($extension === 'docx') {
        return $this->extractFromDocx($file->getRealPath());
      }

      if ($extension === 'doc') {
        return $this->extractFromDoc($file->getRealPath());
      }

      return null;
    } catch (Exception $e) {
      Log::error("DocxParserService: failed to extract text from {$file->getClientOriginalName()}: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Extract text from a DOCX file (ZIP + XML).
   */
  public function extractFromDocx(string $filePath): ?string
  {
    $zip = new ZipArchive();

    if ($zip->open($filePath) !== true) {
      throw new Exception("Cannot open DOCX file as ZIP: {$filePath}");
    }

    // Primary document body
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
      throw new Exception("word/document.xml not found inside DOCX");
    }

    return $this->parseWordXml($xml);
  }

  /**
   * Best-effort text extraction from legacy binary .doc files.
   * Reads raw bytes and strips non-printable characters.
   */
  public function extractFromDoc(string $filePath): ?string
  {
    $content = file_get_contents($filePath);
    if ($content === false) {
      throw new Exception("Cannot read DOC file: {$filePath}");
    }

    // Extract printable text between null bytes (Word binary stores strings this way)
    // This is a heuristic — for production-grade DOC support, use LibreOffice or phpoffice/phpword
    preg_match_all('/[\x20-\x7E\xA0-\xFF]{4,}/', $content, $matches);

    if (empty($matches[0])) {
      return null;
    }

    $text = implode(' ', $matches[0]);
    return $this->cleanText($text);
  }

  /**
   * Parse Word XML and extract meaningful text.
   * Handles paragraph (<w:p>) and text run (<w:t>) elements.
   */
  private function parseWordXml(string $xml): string
  {
    // Use DOMDocument for reliable XML parsing
    $dom = new \DOMDocument();
    // Suppress warnings from malformed XML (common in older Word files)
    @$dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);

    $lines = [];

    // Each <w:p> is a paragraph
    $paragraphs = $dom->getElementsByTagNameNS(
      'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
      'p'
    );

    foreach ($paragraphs as $para) {
      $paraText = '';

      // Each <w:t> inside the paragraph is a text run
      $textNodes = $para->getElementsByTagNameNS(
        'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
        't'
      );

      foreach ($textNodes as $t) {
        $paraText .= $t->nodeValue;
      }

      $trimmed = trim($paraText);
      if ($trimmed !== '') {
        $lines[] = $trimmed;
      }
    }

    if (empty($lines)) {
      // Fallback: strip all XML tags
      return $this->cleanText(strip_tags($xml));
    }

    return $this->cleanText(implode("\n\n", $lines));
  }

  /**
   * Clean and normalise extracted text.
   */
  private function cleanText(string $text): string
  {
    // Remove null bytes and control characters
    $text = preg_replace('/\x00/', '', $text);
    $text = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

    // Normalise line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // Collapse excessive blank lines
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    // Fix UTF-8 encoding
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    return trim($text);
  }

  /**
   * Extract metadata from a DOCX file (core.xml).
   */
  public function extractMetadata(UploadedFile $file): array
  {
    if (strtolower($file->getClientOriginalExtension()) !== 'docx') {
      return [];
    }

    try {
      $zip = new ZipArchive();
      if ($zip->open($file->getRealPath()) !== true) {
        return [];
      }

      $coreXml = $zip->getFromName('docProps/core.xml');
      $appXml  = $zip->getFromName('docProps/app.xml');
      $zip->close();

      $meta = [];

      if ($coreXml) {
        $dom = new \DOMDocument();
        @$dom->loadXML($coreXml, LIBXML_NOERROR | LIBXML_NOWARNING);
        $meta['title']   = $this->getXmlValue($dom, 'dc:title');
        $meta['author']  = $this->getXmlValue($dom, 'dc:creator');
        $meta['subject'] = $this->getXmlValue($dom, 'dc:subject');
      }

      if ($appXml) {
        $dom = new \DOMDocument();
        @$dom->loadXML($appXml, LIBXML_NOERROR | LIBXML_NOWARNING);
        $meta['page_count'] = (int) ($this->getXmlValue($dom, 'Pages') ?? 0);
      }

      return $meta;
    } catch (Exception $e) {
      Log::error("DocxParserService: failed to extract metadata: " . $e->getMessage());
      return [];
    }
  }

  private function getXmlValue(\DOMDocument $dom, string $tagName): ?string
  {
    $nodes = $dom->getElementsByTagName($tagName);
    return $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;
  }
}

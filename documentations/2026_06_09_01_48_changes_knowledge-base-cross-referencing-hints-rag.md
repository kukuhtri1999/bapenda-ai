# Changes Documentation: Knowledge Base Cross-Referencing Hints in RAG

**Date**: 2026-06-09 01:48 (Asia/Jakarta)  
**Author**: Antigravity AI  
**Branch**: `main-local`  

---

## 1. Executive Summary

This update implements **Knowledge Base Cross-Referencing Hints in the RAG (Retrieval-Augmented Generation) pipeline**. 

If a retrieved knowledge chunk contains a textual tag like `referensi : Judwal pelayanan samsat, jam operasional samsat`, the RAG query engine now dynamically:
1. Extracts the referenced search terms (split by commas).
2. Searches the database for matching or semantically similar Knowledge Base documents.
3. Injects the parsed reference documents into the AI context prompt.
4. Prevents duplicate injections by keeping track of document IDs.

This enables authors to explicitly "guide" the AI to load related documents (like schedules or operational policies) to compose more contextually complete answers.

---

## 2. Technical Implementation Details

### AI Retrieval Service

#### [MODIFY] [OpenAIService.php](file:///c:/laragon/www/bapenda-ai/app/Services/OpenAIService.php)

- **`getVectorKnowledge(string $userQuery)` Return Points**:
  Piped the final combined results (from vector and database searches) through our new cross-referencing filter:
  ```php
  return $this->injectReferencedKnowledge($vectorResults);
  // ... and fallback ...
  return $this->injectReferencedKnowledge($dbResults);
  ```

- **`injectReferencedKnowledge(array $results)` [NEW]**:
  - Scans the raw text content of the initial set of retrieved chunks for the regex pattern `/(?:referensi|reference)\s*:\s*([^\n\r.]+)/i` (case-insensitive, accommodating optional spaces).
  - Splits matching lines by commas into trimmed, non-empty search terms.
  - Queries the database for each reference title using a **tiered lookup**:
    1. **Exact Match**: Case-insensitive match on title (`where('title', 'like', $refTitle)`).
    2. **Partial Match**: Matches entries whose titles contain the term (`where('title', 'like', "%{$refTitle}%")`).
    3. **Keyword Search**: Splits terms into words, discards words of length <= 3, and searches for active published documents whose titles contain all keywords.
  - Formats matches, assigns relative confidence scores, and appends them to the RAG context.
  - Prevents infinite loops or duplicate document overhead by auditing existing result IDs (`$injectedIds`).

- **`formatDbRowForKnowledge($match, string $source, float $score)` [NEW]**:
  Formats Eloquent model results into standard RAG associative arrays (including properties like `id`, `title`, `content`, `answer`, `category`, `search_content`, `score`, and `_source`).

---

## 3. Verification & Validation

### 3.1 Syntax Validation
- Executed `php -l app/Services/OpenAIService.php` -> Completed successfully with no syntax errors.

### 3.2 Build Verification
- Executed `npm run build` to verify PWA and client bundles -> Completed successfully.

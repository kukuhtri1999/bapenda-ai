# Changes Documentation: Fixing Word Export 500 Error on Large Data

**Date**: 2026-06-08 07:25 (Asia/Jakarta)  
**Branch**: `main-local`  
**Author**: Antigravity AI  

---

## 1. Problem Analysis

### The 500 Error Root Causes
When exporting hundreds of entries to Word, the server would return a **500 Internal Server Error** due to multiple compounding issues:

| # | Root Cause | Impact |
|---|---|---|
| 1 | **Memory exhaustion** – `->get()` loaded ALL entries with full HTML content at once | PHP hits the 128M/256M default memory limit |
| 2 | **Execution timeout** – Processing DOM HTML per entry is slow; hundreds of entries exceed `max_execution_time` (30s default) | PHP fatal error / Nginx 504 |
| 3 | **Base64 image strings in HTML** – If any entry had embedded base64 images (from the Quill editor), those strings could be megabytes each, exploding memory usage when serialised inside the PHPWord XML object | OOM crash |
| 4 | **No error fallback per entry** – If PHPWord's XML parser failed on a single malformed entry, it threw an uncaught exception crashing the entire export | 500 error |
| 5 | **No user feedback** – Form submission was silent; users had no indication the export was running | UX issue |

---

## 2. Files Modified

### [KnowledgeBaseController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/KnowledgeBaseController.php)

#### `exportToWord(array $ids)` — Complete Rewrite

**Change 1: Raise PHP runtime limits**
```php
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');
@set_time_limit(300);
```
These are applied only during the export request, not globally.

**Change 2: Select only needed columns**
```php
KnowledgeBase::whereIn('id', $ids)
    ->select(['id', 'title', 'content'])
```
Instead of loading the full Eloquent model (with metadata, keywords, tags, timestamps, etc.), only the three fields we actually write to the document are selected. This significantly reduces memory per row.

**Change 3: Process in chunks of 20**
```php
->chunk(20, function ($entries) use ($section, &$first, &$counter) { ... });
```
`chunk()` queries the database in batches of 20 rows instead of loading all IDs at once. Each batch is processed and the batch is garbage-collected before the next one. This keeps the peak memory footprint proportional to 20 entries, not 500.

**Change 4: Order by original selection order**
```php
->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $ids)) . ')')
```
Ensures the exported document respects the order in which users selected records.

**Change 5: Per-entry HTML exception fallback**
```php
try {
    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $cleanContent, false, false);
} catch (\Exception $htmlEx) {
    // Fallback: plain text if HTML parsing fails
    $plainText = html_entity_decode(strip_tags($entry->content), ENT_QUOTES, 'UTF-8');
    foreach (explode("\n", wordwrap($plainText, 120, "\n")) as $line) {
        $section->addText(trim($line), ['name' => 'Arial', 'size' => 11]);
    }
}
```
If any single entry's HTML is malformed and causes a PHP Word XML exception, the export **does not crash**. Instead, that entry falls back to plain text and the rest of the document continues normally.

**Change 6: Explicit memory cleanup per entry**
```php
unset($entry);   // inside chunk loop
unset($phpWord, $objWriter);  // after save, before download stream
```
Explicitly frees memory at two key moments: per entry inside the loop, and before streaming the file.

**Change 7: Clean temp file on error**
```php
if (file_exists($tempFile)) {
    @unlink($tempFile);
}
```
Prevents orphan temp files if the write step fails.

#### `cleanHtmlForPhpWord(string $html)` — Improved

**Change 8: Strip all images with regex before DOM parsing**
```php
$html = preg_replace('/<img[^>]*\/?>/i', '', $html);
$html = preg_replace('/data:[a-zA-Z\/]+;base64,[a-zA-Z0-9+\/=]+/i', '', $html);
```
Images (especially base64 embedded ones) are the single biggest memory/time offenders. For bulk Word export, they are stripped before DOM parsing. This makes `cleanHtmlForPhpWord` much faster per entry.

**Change 9: Explicit `unset($dom)`**
Frees the DOMDocument object immediately after we've extracted the XML string, rather than waiting for garbage collection at the end of the function.

---

### [Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/KnowledgeBase/Index.vue)

#### Loading State and Overlay

**Change 10: `exportLoading` ref**
```js
const exportLoading = ref(false);
```

**Change 11: Set loading true before form submit**
```js
exportLoading.value = true;
// ... form.submit() ...
setTimeout(() => { exportLoading.value = false; }, 8000);
```
Sets loading overlay immediately when user clicks Execute for `export_word`. Auto-dismisses after 8 seconds (the browser triggers the file download and the overlay becomes irrelevant).

**Change 12: Full-screen loading overlay in template**
```vue
<VOverlay v-model="exportLoading" persistent z-index="9999">
  <VCard class="pa-6 text-center">
    <VProgressCircular indeterminate color="primary" size="52" />
    <div>Generating Word File</div>
    <div>Processing your selected entries… This may take a moment for large exports.</div>
  </VCard>
</VOverlay>
```
A clean, centered modal card with a spinning progress indicator appears immediately on click, blocking further interaction until the download starts.

---

## 3. Verification & Validation

- Validated PHP syntax: `php -l app/Http/Controllers/KnowledgeBaseController.php` → No syntax errors.
- Compiled frontend assets: `npm run build` → Built successfully.

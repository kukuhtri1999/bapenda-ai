# Changes Documentation: Fixing DOMDocument XML Declaration and Image Load Error on Word Export

**Date**: 2026-06-08 06:55 (Asia/Jakarta)  
**Branch**: `main-local`  
**Author**: Antigravity AI  

---

## 1. Issue Resolved

### The Problems
1. **XML Declaration Exception**: 
   When using PHPWord's `\PhpOffice\PhpWord\Shared\Html::addHtml()` to parse HTML content, `DOMDocument::loadXML()` is called internally. If the HTML content has an XML declaration like `<?xml encoding="UTF-8">` placed anywhere other than the absolute start of the XML payload (e.g., inside the `<body>` element when wrapped), the parser throws:
   ```
   DOMDocument::loadXML(): XML declaration allowed only at the start of the document in Entity, line: 1
   ```
2. **Missing Image Exception**:
   If an HTML string contains an `<img>` tag pointing to a local storage file that is missing on the server, or a relative path, PHPWord throws a fatal exception:
   ```
   Could not load image <path>
   ```
   This aborts the entire Word export transaction.

### The Solution
We implemented a robust HTML sanitizer and pre-processor inside the controller before importing content to PHPWord:
1. **XML Declaration Strip**: We strip out any stray or conflicting XML declarations (`<?xml ... ?>`) from both the input string and the intermediate DOM representation.
2. **XHTML Tag Normalizer**: We parse the user's raw HTML string using a relaxed HTML parser (`DOMDocument::loadHTML`) which is highly forgiving. We then serialize the nodes back into strictly valid XHTML elements (where tags like `<br>` and `<img>` are properly closed as `<br/>` and `<img/>`), fulfilling PHPWord's requirement for strict XML.
3. **Image Path Resolver & Validator**:
   - For all `<img>` tags pointing to local Laravel storage (`/storage/...`), we convert the web URLs to absolute local filesystem paths on the server.
   - We check if the image file exists on disk using `file_exists()`. If it exists, we supply the local path. If it does not exist (or if it is an external image that could fail to load), we strip the `<img>` tag entirely to prevent PHPWord from throwing an exception.

---

## 2. File Modified

### [KnowledgeBaseController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/KnowledgeBaseController.php)
- Modified `exportToWord(array $ids)`:
  - Passed HTML content through the new `cleanHtmlForPhpWord()` helper.
- Added `cleanHtmlForPhpWord(string $html)` private helper method:
  - Uses `DOMDocument` to load and cleanly serialize HTML fragments.
  - Automatically resolves local storage image URLs to local absolute paths.
  - Filters out missing/broken image tags to prevent crash.
  - Strips conflicting XML declarations.

---

## 3. Verification & Validation

- Verified PHP syntax of [KnowledgeBaseController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/KnowledgeBaseController.php) via `php -l`.
- Successfully ran standalone parsing simulations against raw database HTML content.

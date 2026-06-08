# Documentation: Adding AI Instructions Field to Knowledge Base

**Timestamp**: 2026-06-09 02:38 WIB  
**Author**: Antigravity AI  

---

## Executive Summary

To allow admin users to provide hints or instructions directly to the AI, we added an optional `ai_instructions` field to the Knowledge Base creation and editing interface. When a Knowledge Base entry containing these instructions is retrieved during a user query, the AI is dynamically guided by these directives.

Furthermore, we implemented a dynamic cross-referencing system: the AI parses quoted categories or types in the instructions (e.g., `'peraturan & kebijakan'`) and dynamically queries and injects all related Knowledge Bases into the context on-the-fly.

---

## Detailed Changes

### 1. Database Schema
* **Migration File**: [2026_06_09_023000_add_ai_instructions_to_knowledge_bases_table.php](file:///c:/laragon/www/bapenda-ai/database/migrations/2026_06_09_023000_add_ai_instructions_to_knowledge_bases_table.php)
* **Action**: Created and ran a migration to add a nullable text field `ai_instructions` after the `content` column in the `knowledge_bases` table:
  ```php
  Schema::table('knowledge_bases', function (Blueprint $table) {
      $table->text('ai_instructions')->nullable()->after('content');
  });
  ```

---

### 2. Model Modifications
* **File**: [KnowledgeBase.php](file:///c:/laragon/www/bapenda-ai/app/Models/KnowledgeBase.php)
* **Changes**:
  * Added `ai_instructions` to the `$fillable` array.
  * Updated vector database metadata creation to include `ai_instructions`. Pinecone does not accept `null` for metadata values, so we default `ai_instructions` to an empty string:
    ```php
    'ai_instructions' => $this->ai_instructions ?? '',
    'created_at'      => $this->created_at?->toISOString() ?? '',
    'updated_at'      => $this->updated_at?->toISOString() ?? '',
    ```
  * Added `ai_instructions` to the `$this->isDirty(...)` check in `updateVectorDatabase()` to ensure that when only the instructions field changes, the vector database is properly synced.

---

### 3. Controller Updates
* **File**: [KnowledgeBaseController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/KnowledgeBaseController.php)
* **Changes**:
  * **`store()`**: Added validation rule `'ai_instructions' => 'nullable|string'`. Since `$validator->validated()` is used for creation, this is automatically stored.
  * **`update()`**: Added validation rule `'ai_instructions' => 'nullable|string'`. The update process saves this field correctly.

---

### 4. Frontend View Changes
* **Files**:
  - [Create.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/KnowledgeBase/Create.vue)
  - [Edit.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/KnowledgeBase/Edit.vue)
* **Changes**:
  * Initialized `ai_instructions` in the reactive `form` state.
  * Appended `ai_instructions` to the `FormData` on submission in `submit()`.
  * Added a `<VTextarea>` field below the Quill RichText Editor with descriptive placeholders and helper tips:
    ```vue
    <VTextarea
      v-model="form.ai_instructions"
      label="Instruksi AI / RAG Hints (Opsional)"
      variant="outlined"
      rows="3"
      :error-messages="formState.errors.ai_instructions"
      prepend-inner-icon="mdi-robot"
      placeholder="Instruksi tambahan untuk AI (misal: baca kategori 'peraturan & kebijakan' untuk detail biaya, atau berikan link web online untuk cek PKB)"
      hint="Instruksi khusus ini akan disisipkan ke sistem prompt AI saat dokumen ini digunakan sebagai referensi."
      persistent-hint
    ></VTextarea>
    ```

---

### 5. OpenAI Service & RAG Pipeline Integration
* **File**: [OpenAIService.php](file:///c:/laragon/www/bapenda-ai/app/Services/OpenAIService.php)
* **Changes**:
  * **`generateCustomerServiceResponse()`**:
    - Selects `ai_instructions` from local DB queries.
    - Captures `ai_instructions` from Pinecone vector search results metadata.
    - Compiles retrieved instructions into a distinct section of the system prompt:
      ```
      INSTRUKSI & PETUNJUK KHUSUS AI (DARI KNOWLEDGE BASE REFERENSI - HARUS DIIKUTI):
      1. [Instruksi 1]
      2. [Instruksi 2]
      ```
  * **`injectAIInstructionReferencedKnowledge()`**:
    - Scans the instructions of retrieved KBs.
    - If it matches a quoted category name/label or type name/label (e.g. `'peraturan & kebijakan'`), it dynamically queries the database for all matching Knowledge Base entries.
    - Injects these extra entries directly into the RAG results context.

---

## Verification & Testing

1. **Compilation**: Asset building completed successfully using `npm run build`.
2. **Unit / Integration Validation**:
   - Created a temporary Knowledge Base record with type `regulation` ("Peraturan & Kebijakan").
   - Created a main Knowledge Base record with `ai_instructions` pointing to `'peraturan & kebijakan'`.
   - Verified that calling the RAG pipeline correctly loaded the primary KB, parsed its instruction, retrieved the target regulation KB, and successfully injected it into the prompt context.
   - Verified that null inputs are converted to `""` (empty string) before Pinecone upserts, successfully avoiding any `400 Bad Request` exceptions from Pinecone metadata validation.

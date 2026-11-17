# Comprehensive Documentation: Implementing Multi-Turn Connected Conversational AI Chat

**Date & Time:** 2026-11-09 11:18:00 +07:00  
**Author:** AI Engineering Team & Pair Programmer  
**Target File:** `documentations/2026_11_09_11_18_changes_implementing-multi-turn-connected-conversational-ai-chat.md`  
**Scope:** Full-Stack Architecture (System Prompting, Vector RAG Retrieval, Cache Invalidation, History Window & Pinned Context, Controller Session Management, Frontend Wajib Pajak Context Injection, Unit Testing).

---

## 1. Executive Summary

Previously, users reported that subsequent messages in the same chat session (e.g. Chat 2 following Chat 1, Chat 3 following Chat 2) felt disconnected or "amnesic"—as if the AI was starting a brand new conversation on every turn. For example, when a user asked *"pirang dino proses mutasi keluar?"* and then followed up with *"Berapa biayanya?"*, the assistant often responded with fees for regular annual tax or general vehicle registration instead of mutasi fees, or repeated generic greetings like *"Halo! Saya SALMA AI..."*.

### Root Causes Identified
1. **Context-Blind RAG Vector Search (`OpenAIService.php`):** The vector search and database full-text search were performed strictly on the text of the latest user turn (`$userQuery`). When a user asked an anaphoric or follow-up question (*"Berapa biayanya?"*, *"Kalau mobil?"*, *"Syaratnya apa?"*), Pinecone and MySQL had no access to the previous turn's subject (*"mutasi keluar motor"*), causing the retrieval of completely irrelevant Knowledge Base articles.
2. **Response Cache Collisions (`OpenAIService.php`):** The response cache key was computed as `md5(normalizedQuery)` based only on the current user query string. If any user had ever asked *"Berapa biayanya?"* for annual tax, a follow-up asking *"Berapa biayanya?"* for vehicle transfer (*mutasi/balik nama*) was instantly served the cached annual tax response, completely bypassing LLM inference and conversation history.
3. **Absence of Multi-Turn Directives in System Prompt (`SalmaPromptService.php`):** The system prompt previously contained zero guidance on conversational continuity, pronoun/anaphora resolution (*"itu"*, *"tersebut"*, *"tadi"*), or prohibiting redundant greetings on subsequent turns.
4. **Short Sliding Window & History Context Eviction (`OpenAIService.php`):** The history slice was hardcoded to `array_slice($messages, -6)` (only 3 user-assistant exchanges), which immediately discarded pinned initial context (such as Wajib Pajak name, vehicle plate number, and registered vehicle data) as the dialogue progressed.
5. **Wajib Pajak Data Disconnect (`Index.vue` & `ChatController.php`):** Registered Wajib Pajak information (`props.wajibPajakData`) was displayed only in the UI header and was never passed to the backend API (`/api/chat/start` or `/api/chat/message`), leaving the AI unaware of the user's name or vehicle plate number.

---

## 2. Detailed Technical Changes

### A. System Prompt & Multi-Turn Conversational Guidelines (`app/Services/SalmaPromptService.php`)

1. **Added `multiTurnGuidelines()` Method:**
   - Injected into the primary system prompt (`buildPrompt()`) right after `thinkingChain()`.
   - **Context Continuity:** Mandates that questions on turn 2 and beyond must be interpreted as extensions, comparisons, or detail inquiries related to the ongoing topic.
   - **Pronoun & Anaphora Resolution:** Instructs the LLM that referential words (*"itu"*, *"tersebut"*, *"tadi"*, *"prosesnya"*, *"biayanya"*, *"syaratnya"*, *"lokasinya"*, *"dokumen apa saja"*) directly point to the entities discussed in preceding turns.
   - **No Redundant Greetings:** Strictly prohibits repeating introductory phrases (*"Halo! Saya SALMA AI..."*, *"Perkenalkan..."*) on turn 2 onwards once mutual greetings have occurred.
   - **User Profile Continuity:** Mandates retaining user name and license plate number throughout the entire dialogue without re-asking.

2. **Updated `thinkingChain()`:**
   - Step 1 now prompts the model to inspect conversation history for follow-ups before classifying intent or formulating the answer.

3. **Multi-Turn Intent Inheritance in `detectIntent()`:**
   - Modified signature to `detectIntent(string $query, ?string $previousTopic = null): string`.
   - When a follow-up query is conversational or short (e.g. *"kalau motor?"*, *"terus gimana?"*) and would otherwise default to `'umum'`, it intelligently inherits `$previousTopic` from the active session.

---

### B. Contextual RAG Retrieval, Cache Scoping & History Window (`app/Services/OpenAIService.php`)

1. **Contextual Query Reformulation (`buildContextualRetrievalQuery`):**
   - Implemented `buildContextualRetrievalQuery(string $userQuery, array $messages, ?array $cal = null): string`.
   - Detects follow-up questions using character length thresholds (<= 55 chars), conversational starters (*"kalau"*, *"lha"*, *"terus"*, *"bagaimana kalau"*, *"apakah bisa"*, *"berapa biayanya"*, *"syaratnya"*), and pronouns (*"itu"*, *"tersebut"*, *"tadi"*).
   - Extracts context anchors (such as vehicle license plate numbers from pinned system messages) and strips filler phrases from prior user turns (*"halo"*, *"saya mau tanya"*, *"bagaimana cara"*).
   - Combines the distilled prior subject with the follow-up question (e.g. Turn 1: *"mutasi keluar motor beat"*, Turn 2: *"berapa biayanya?"* $\rightarrow$ Query: *"mutasi keluar motor beat berapa biayanya"*).
   - Preserves standalone queries (e.g. if the user explicitly switches topic to *"Jadwal Samsat Keliling Paciran"*), preventing query contamination.

2. **Multi-Turn Detection & Smart Cache Scoping:**
   - Added `hasActiveConversationHistory(array $messages): bool` to check if a dialogue has prior user turns (`$userTurns > 1`).
   - Cached responses are **strictly bypassed** during active multi-turn dialogues (`!$isMultiTurn && strlen($normalizedQuery) > 15`), ensuring follow-up questions are always resolved with full context and never hijacked by stale generic cache keys.
   - Cache writes are restricted to standalone first-turn queries only.

3. **Pinned Context Preservation & 12-Turn Sliding Window:**
   - Separated messages into:
     - **Pinned Contexts (`role === 'system'` or `'context'`):** User profile, license plate number, and session metadata are anchored at the top of the prompt and never dropped.
     - **Dialogue Turns (`role === 'user'` or `'assistant'`):** Expanded sliding window from 6 to 12 turns (up to 6 full QA exchanges), providing rich conversational memory without overflowing token limits.

4. **Updated Streaming Implementation (`generateCustomerServiceStream`):**
   - Unified streaming response pipeline to use `buildContextualRetrievalQuery`, pinned contexts, and the 12-turn history window.

5. **Personalized Greeting Generation (`generateGreeting`):**
   - Modified to accept optional `?array $wajibPajak = null`.
   - Generates personalized greetings when user name and license plate number are known (e.g. *"Halo Pak/Bu Kukuh! Saya SALMA AI... Ada yang bisa saya bantu terkait layanan pajak atau administrasi untuk kendaraan plat S 8735 JK hari ini?"*).

---

### C. Chat Session & Context Management (`app/Http/Controllers/ChatController.php`)

1. **Automatic Context Ingestion in `startChat`:**
   - Checks `$request->input('wajib_pajak') ?? session('wajib_pajak_data')`.
   - Persists Wajib Pajak profile in `chats.metadata` and inserts an initial `role='context'` row in `chat_messages` if not already present.
   - Supplies Wajib Pajak data to `generateGreeting($wajibPajak)`.

2. **Context Persistence & History Separation in `sendMessage`:**
   - Ingests Wajib Pajak data if supplied in payload.
   - Separates pinned `context` messages (`role='context'`) from standard dialogue turns (`role in ('user', 'assistant')`), querying the latest 14 rows from database.
   - Uses `priorTopic` from the active chat session to supply to `detectIntent($request->message, $priorTopic)`.

3. **Updated `streamMessage`:**
   - Includes pinned `context` messages and latest 14 dialogue rows to match standard chat behavior.

---

### D. Frontend Context Injection (`resources/js/Pages/Chat/Index.vue`)

1. **Payload Transmission:**
   - Updated `initializeChat()`: Sends `wajib_pajak: props.wajibPajakData || null` to `/api/chat/start`.
   - Updated `startNewChat()`: Sends `wajib_pajak: props.wajibPajakData || null` to `/api/chat/start`.
   - Updated `sendMessage()`: Sends `wajib_pajak: props.wajibPajakData || null` to `/api/chat/message`.

2. **Personalized Client-Side Fallback:**
   - Fallback greetings on network failure now include the user's name and license plate number if `props.wajibPajakData` is available.

---

### E. Automated Verification Suite (`tests/Unit/MultiTurnConversationalTest.php`)

Created an automated PHPUnit test class with 5 comprehensive unit tests:
1. `test_salma_prompt_contains_multi_turn_rules`: Validates system prompt contains all multi-turn rules.
2. `test_salma_prompt_intent_inherits_prior_topic_on_follow_ups`: Verifies conversational follow-up intent inheritance.
3. `test_contextual_retrieval_query_synthesizes_follow_up`: Asserts that follow-up queries (*"Berapa biayanya?"*) synthesize prior subjects (*"mutasi keluar motor"*).
4. `test_contextual_retrieval_query_preserves_standalone_query`: Asserts that new standalone topics are not contaminated.
5. `test_personalized_greeting_generation`: Asserts personalized and generic greeting generation.

---

## 3. Verification & Test Results

1. **PHP Syntax Linter:**
   - `php -l app/Services/SalmaPromptService.php` $\rightarrow$ No syntax errors detected.
   - `php -l app/Services/OpenAIService.php` $\rightarrow$ No syntax errors detected.
   - `php -l app/Http/Controllers/ChatController.php` $\rightarrow$ No syntax errors detected.

2. **Unit Test Execution:**
   - Command: `php artisan test --filter=MultiTurnConversationalTest`
   - Result: **5 passed (13 assertions)**, duration: 12.98s, exit code 0.

3. **Frontend Production Build:**
   - Command: `npm run build`
   - Result: Client and SSR bundles compiled cleanly, exit code 0.

---

## 4. Summary of Files Changed

| File Path | Description of Changes |
|---|---|
| `app/Services/SalmaPromptService.php` | Added `multiTurnGuidelines()`, updated `thinkingChain()`, updated `detectIntent()` with topic inheritance. |
| `app/Services/OpenAIService.php` | Implemented `buildContextualRetrievalQuery()`, `hasActiveConversationHistory()`, pinned context & 12-turn history, smart cache scoping, personalized greeting. |
| `app/Http/Controllers/ChatController.php` | Auto-ingested Wajib Pajak context, separated pinned context from dialogue rows, integrated topic inheritance. |
| `resources/js/Pages/Chat/Index.vue` | Injected `wajib_pajak` in `start` and `message` API requests, personalized fallback greetings. |
| `tests/Unit/MultiTurnConversationalTest.php` | Added comprehensive automated test suite for multi-turn AI features. |
| `documentations/2026_11_09_11_18_changes_implementing-multi-turn-connected-conversational-ai-chat.md` | Created full documentation file. |


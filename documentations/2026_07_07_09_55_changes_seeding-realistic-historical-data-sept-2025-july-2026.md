# Changes Documentation: Historical Data Seeding (September 2025 - July 16, 2026)

**Timestamp**: 2026-07-07 09:55 WIB  
**Author**: Antigravity AI  

---

## 1. Executive Summary

This update implements a comprehensive historical database seeder (`ExampleDataSeeder.php`) to simulate active operational usage of the **SALMA-AI** platform starting from September 2025 up to July 16, 2026. The generated data matches the realistic East Javanese context (Lamongan district), follows precise monthly growth and flat-fluctuation boundaries, uses natural Javanese and Indonesian conversations, and conforms strictly to local license plate naming conventions.

---

## 2. Detailed Technical Changes

### 2.1 Database Seeding Architecture

#### [MODIFY] [ExampleDataSeeder.php](file:///c:/laragon/www/bapenda-ai/database/seeders/ExampleDataSeeder.php)
- **Table Truncation**: Integrated foreign key safe truncation for tables `chat_messages`, `chat_feedback`, `chats`, and `wajib_pajak` to prevent duplicate sessions and primary key constraint failures.
- **East Java / Lamongan Naming Lists**: Added a curated set of 70 realistic Javanese/Islamic first names and 50 last names to produce authentic Indonesian combinations.
- **License Plate Suffix Constraints**: Programmed a deterministic regex-safe license plate generator:
  - Starts with **`S`** (the plate prefix for Lamongan/Bojonegoro/Tuban/Mojokerto/Jombang).
  - Ends with a 2 or 3-digit character suffix starting strictly with **`I, J, K, L, M`** (e.g. `S 1234 IA`, `S 9999 KB`, `S 2345 MAJ`).
- **Indonesian Mobile Numbers**: Programmed prefixes for Telkomsel (`0812`, `0821`, etc.), Indosat (`0856`, etc.), XL (`0817`, etc.), Tri (`0896`, etc.), and Axis (`0838`), generating valid 11 to 13-digit numbers.
- **Manual Conversation (Q&A) Pool**: Written 40 distinct, natural conversations:
  - **20 Javanese dialogues** capturing dialectal variations like "*Piro dendo telat bayar pajek motor setahun mas?*" and "*Samsat keliling dino iki daerah Karanggeneng enek gak?*".
  - **20 Indonesian dialogues** capturing standard public service inquiries, such as perpanjangan STNK, mutasi, balik nama, and online payment guides.
  - Linked to specific topic categories (`denda_keterlambatan`, `tanya_samsat_keliling`, etc.) and sentiment values (`positive`, `neutral`, `negative`) to allow the Analytics engine to run successfully.
- **Manual Feedback Pool**: Created 25 natural feedback text entries reflecting community appreciation, suggestions, and reports.
- **Historical Timeline Spread**:
  - **September 2025**: 12 Taxpayers, 22 Chats, 4 Feedback
  - **October 2025**: 20 Taxpayers, 42 Chats, 8 Feedback
  - **November 2025**: 35 Taxpayers, 78 Chats, 14 Feedback
  - **December 2025 to June 2026** (monthly): Wajib Pajak: 30 - 60, Chats: 70 - 150, Feedback: 10 - 25
  - **July 2026** (partial up to 16th): Wajib Pajak: 15 - 30, Chats: 35 - 75, Feedback: 5 - 12
  - Randomly distributes the record timestamps across the respective days of each month.

#### [MODIFY] [WajibPajakSeeder.php](file:///c:/laragon/www/bapenda-ai/database/seeders/WajibPajakSeeder.php)
- Cleaned up manual arrays and delegated directly to `ExampleDataSeeder` to prevent duplicate seeding definitions and data schema out-of-sync issues.

#### [MODIFY] [ChatFeedbackSeeder.php](file:///c:/laragon/www/bapenda-ai/database/seeders/ChatFeedbackSeeder.php)
- Cleaned up manual arrays and delegated directly to `ExampleDataSeeder` to maintain consistency.

---

## 3. Data Schema Consistency

The seeder populates the columns as follows:
- **`wajib_pajak`**: `nama`, `nopol`, `nomer_wa`, `created_at`, `updated_at`.
- **`chats`**: `session_id`, `user_id` (null), `title` ("Chat dengan Asisten Bapenda Samsat"), `status` ("closed"), `metadata` (JSON storing user agent, IP address, and matched taxpayer info), `last_activity_at`, `created_at`, `updated_at`.
- **`chat_messages`**: `chat_id`, `role` ("user"), `content` (query), `answer` (reply), `topic`, `sentiment`, `response_time_seconds` (decimal), `metadata` (JSON storing nopol), `sent_at`, `created_at`, `updated_at`.
- **`chat_feedback`**: `session_id`, `nama`, `nopol`, `nomer_wa`, `rating` (1-5 stars, mostly positive 4-5 stars), `feedback_text`, `chat_summary` (JSON with message counts, chat duration, and chat title), `chat_ended_at`, `created_at`, `updated_at`.

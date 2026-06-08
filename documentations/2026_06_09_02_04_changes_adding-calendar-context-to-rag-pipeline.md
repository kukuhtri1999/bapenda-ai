# Documentation: Adding Calendar Context to RAG Pipeline

**Timestamp**: 2026-06-09 02:04 WIB  
**Author**: Antigravity AI  

---

## Executive Summary

To improve the accuracy of SALMA AI (Asisten Digital Samsat Lamongan) for queries related to daily schedules (e.g., Samsat Keliling), Ramadan schedules, and holiday closures, we have implemented a dynamic, database-configurable Indonesian calendar context injection and temporal query expansion system. 

Before this change, the RAG (Retrieval-Augmented Generation) system struggled to retrieve day-specific schedule chunks when users queried using relative terms like *"hari ini"* (today) or *"besok"* (tomorrow) because the database entries contain explicit day names (e.g., "Selasa") that did not match the user's search query text.

This update resolves the issue completely by:
1. Checking the current day, date, and time in the Indonesian timezone (`Asia/Jakarta`).
2. Expanding the search query dynamically by appending the current day name and status keywords (e.g., "Selasa", "Ramadhan", "libur") when temporal queries are detected.
3. Injecting a structured calendar and holiday context block into the system prompt.
4. Enabling database-backed administration of holiday dates and Ramadan start/end ranges via `AppSetting`.

---

## Detailed Changes

### 1. Database Schema & Configuration

We created a database migration to seed default settings into the `app_settings` table:
* **File**: `database/migrations/2026_06_09_020000_add_calendar_and_ramadan_settings_to_app_settings.php`
* **Settings Added**:
  * `calendar.ramadan_start`: Start date of Ramadan in `YYYY-MM-DD` format (default: `'2026-02-18'`).
  * `calendar.ramadan_end`: End date of Ramadan in `YYYY-MM-DD` format (default: `'2026-03-20'`).
  * `calendar.holidays`: A JSON map of holiday dates to their Indonesian names (default includes all 16 major national holidays in 2026).

These settings are automatically managed by the existing settings system, making them instantly visible and editable in the admin settings dashboard under the `system` group.

---

### 2. Timezone & Controller Integration

We updated the chat endpoint controller to ensure the user session time context matches the official local timezone of Lamongan, East Java (`Asia/Jakarta`).
* **File**: `app/Http/Controllers/ChatController.php`
* **Modification**: Updated the current time timestamp parameter:
  ```diff
  - "Session ID: {$request->session_id}, Current time: " . now()->format('Y-m-d H:i:s')
  + "Session ID: {$request->session_id}, Current time: " . now('Asia/Jakarta')->format('Y-m-d H:i:s')
  ```

---

### 3. AI Service Logic Enhancements

We implemented the core business logic inside the OpenAI service class.
* **File**: `app/Services/OpenAIService.php`
* **Key Additions & Modifications**:
  * **`getCalendarDetails(): array`**: Fetches the current date and time in `Asia/Jakarta`, translates English names for days and months into Indonesian (to avoid server locale dependencies), and evaluates Ramadan/holiday status against the `AppSetting` values.
  * **`getCalendarContextString(array $cal): string`**: Formats the calendar details into a clear Markdown context block:
    ```
    INFO WAKTU & KALENDER SAAT INI:
    - Hari: Selasa
    - Tanggal: 09 Juni 2026
    - Jam: 02:04 WIB
    - Status Ramadhan: Bukan Bulan Ramadhan (Gunakan Jadwal Normal/Biasa)
    - Status Hari Libur: Hari Kerja Biasa (Bukan hari libur/cuti bersama)
    ```
  * **`expandQueryForTemporalContext(string $query, array $cal): string`**: Checks if the user's message matches temporal terms (e.g. *hari ini*, *buka*, *keliling*, *jadwal*, *ramadan*). If matching, it appends the current day name and status tags to optimize retrieval.
  * **RAG Retrieval Update (`getVectorKnowledge`)**: Passes the expanded query to both Pinecone vector search and the local MySQL full-text/LIKE keyword search.
  * **Response Generation (`generateCustomerServiceResponse`)**: Injects the Indonesian calendar context block directly into the system prompt's session context section.

---

## Verification & Testing

The implementation was validated through automated checks and live database query executions:

1. **Syntax Checks**: Checked with `php -l` to ensure no linting errors.
2. **Execution Test**: Executed a script bootstrapping Laravel and running a chat message retrieval query on the database:
   * **Query**: *"samsat keliling hari ini buka di mana?"*
   * **Current Day**: Tuesday (*Selasa*).
   * **Expansion Output**: `"samsat keliling hari ini buka di mana? Selasa"`
   * **Results**: Successfully retrieved Tuesday's Samsat Keliling schedule (Karanggeneng, Maduran, Kembangbahu) from the database and produced a highly accurate context-aware response.

---

## Admin Management Instructions

To change the holiday calendar or Ramadan schedule in future years:
1. Log into the Admin Dashboard.
2. Navigate to **Settings** -> **System**.
3. Edit the following settings:
   * **Awal Ramadan / Akhir Ramadan**: Set to the current year's date range (e.g., `2027-03-09`).
   * **Hari Libur & Cuti Bersama**: Update the JSON field with dates for the current year, e.g.:
     ```json
     {
       "2026-12-25": "Hari Raya Natal",
       "2027-01-01": "Tahun Baru Masehi"
     }
     ```
4. Click **Save**. The configuration is cached for performance and will update instantly.

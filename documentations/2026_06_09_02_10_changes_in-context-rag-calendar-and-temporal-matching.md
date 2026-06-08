# Documentation: In-Context Calendar and Temporal Matching in RAG

**Timestamp**: 2026-06-09 02:10 WIB  
**Author**: Antigravity AI  

---

## Executive Summary

Following review feedback, we rolled back the database-configured calendar settings approach and replaced it with a simpler, cleaner, and more robust in-context matching approach.

Instead of writing custom database seeders or schema columns for holiday schedules and Ramadan date ranges (which change dynamically each year and require code/database upkeep), we now leverage the LLM's date-parsing and reasoning capabilities. 

Under the new approach:
1. The backend automatically calculates and injects today's Indonesian date, day name, and time context.
2. The RAG system expands the user query to retrieve both regular schedules and any seasonal/holiday overrides from the Knowledge Base.
3. The LLM evaluates the current date against the retrieved schedules entirely in-context.

This eliminates administrative database overhead and ensures zero maintenance effort when new holidays or Ramadan ranges are added to the Knowledge Base in future years.

---

## Detailed Changes

### 1. Database Clean Up
* **Action**: Rolled back the migration to avoid polluting the `app_settings` table:
  ```bash
  php artisan migrate:rollback --step=1
  ```
* **File Removed**: `database/migrations/2026_06_09_020000_add_calendar_and_ramadan_settings_to_app_settings.php`

---

### 2. Service Code Simplification
* **File**: `app/Services/OpenAIService.php`
* **Key Refactorings**:
  * **`getCalendarDetails(): array`**: Simplified to only parse Carbon's localized Indonesian datetime parts (Day, Month, Year, Time, and Weekend indicators). Removed all references to `AppSetting`.
  * **`getCalendarContextString(array $cal): string`**: Updated the block to output only general, factual time markers:
    ```
    INFO WAKTU & KALENDER SAAT INI:
    - Hari: [Nama Hari]
    - Tanggal: [Tanggal]
    - Jam: [Jam] WIB
    - Status Hari Kerja/Libur: [Hari Kerja / Sabtu / Minggu status]
    ```
  * **`expandQueryForTemporalContext(string $query, array $cal): string`**: Refactored query expansion. When a query contains temporal keywords (e.g. *hari ini*, *keliling*, *buka*), it appends the current day name (e.g. `Selasa`) along with general keywords: `"ramadhan ramadan libur tutup"`.
    - *Why?* This query expansion guarantees that both normal day-specific schedules and holiday/seasonal calendars are retrieved and loaded in-context, enabling the LLM to inspect dates and perform comparisons.

---

## Verification & Testing

The simplified in-context matching was verified as follows:

1. **Syntax Integrity**: `php -l` verified that the service contains zero compilation/linting errors.
2. **Retrieval Test**:
   - **User Message**: *"samsat keliling hari ini buka di mana?"* on Tuesday.
   - **Expanded Query**: `"samsat keliling hari ini buka di mana? Selasa ramadhan ramadan libur tutup"`
   - **Retrieval Output**: Correctly retrieved:
     1. Tuesday schedule location entries.
     2. Holiday list and Ramadan calendar overrides.
   - **AI Response**: Resolved correctly: *"Samsat Keliling hari ini, Selasa, 9 Juni 2026, akan membuka layanan di... [List of Tuesday locations]"*. The AI verified that today's date does not fall on a holiday or Ramadan range, and thus used the Tuesday locations schedule.

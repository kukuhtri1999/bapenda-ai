# Documentation: Fixing Knowledge Base Quality Score Display, Hiding reCAPTCHA v3 Badge Legally, and Floating Launcher UX Icon

**Timestamp**: 2026-08-14 21:45 WIB  
**Author**: Antigravity AI  

---

## 1. Executive Summary

This update resolves 3 key frontend and UX requirements:

1. **Quality Score Column Render Fix in Table**:
   - Fixed the issue where quality scores displayed as `—` (dash) due to Vuetify 3 data table slot argument binding mismatch (`{ item, value }` vs item object).
   - `getScore()`, `formatQualityScore()`, `getScoreRaw()`, and `scoreColorClass()` now polymorphically handle objects (`item`, `item.raw`), column values (`value`), and raw numbers/strings (`0.6403`).
2. **Legally & Cleanly Hiding the Google reCAPTCHA v3 Floating Badge**:
   - Hidden the distracting bottom-right reCAPTCHA v3 badge (`.grecaptcha-badge { visibility: hidden !important; opacity: 0 !important; }`) to clean up the page and prevent overlap with floating action buttons.
   - Added the official Google reCAPTCHA Terms & Privacy Policy legal text under chat input bars in [FloatingChat.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Components/FloatingChat.vue) and [Chat/Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Chat/Index.vue) according to Google's official Developer Guidelines.
3. **Floating Chat Launcher Icon UX Upgrade**:
   - Replaced `mdi-robot` with modern `mdi-chat-processing` on the circular floating button launcher (`FloatingChat.vue`) and minimized floating bar for a more intuitive, user-friendly customer service UX.

---

## 2. Technical Modifications

### 2.1 [KnowledgeBase/Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/KnowledgeBase/Index.vue)
- **Polymorphic Score Extractor**:
  ```javascript
  const getScore = (target) => {
    if (target === null || target === undefined || target === '') return null;
    if (typeof target === 'object') {
      const id = target.id ?? target.raw?.id;
      if (id !== undefined && localScores.value[id] !== undefined) {
        return localScores.value[id];
      }
      const val = target.quality_score ?? target.raw?.quality_score ?? target.columns?.quality_score;
      if (val !== undefined && val !== null && val !== '') {
        const num = parseFloat(val);
        return isNaN(num) ? null : num;
      }
      return null;
    }
    const num = parseFloat(target);
    return isNaN(num) ? null : num;
  };
  ```
- **Slot Template Update**:
  ```html
  <template #item.quality_score="{ item, value }">
    <div class="d-flex align-center gap-1">
      <span
        v-if="formatQualityScore(value ?? item) !== null"
        :class="['qs-badge', scoreColorClass(value ?? item)]"
        :title="`Pinecone Vector Match Score: ${getScoreRaw(value ?? item)}`"
      >
        {{ formatQualityScore(value ?? item) }}
      </span>
      <span v-else class="qs-badge qs-none">—</span>
      <VBtn
        :loading="scoringItems.has(item?.id ?? item?.raw?.id)"
        size="x-small"
        icon
        variant="text"
        color="grey"
        title="Perbarui skor dari Pinecone Vector DB"
        @click="computeScore(item)"
      >
        <VIcon size="13">mdi-refresh</VIcon>
      </VBtn>
    </div>
  </template>
  ```

### 2.2 [app.blade.php](file:///c:/laragon/www/bapenda-ai/resources/views/app.blade.php) & [app.css](file:///c:/laragon/www/bapenda-ai/resources/css/app.css)
- Added global CSS rule:
  ```css
  .grecaptcha-badge {
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    z-index: -9999 !important;
  }
  ```

### 2.3 [FloatingChat.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Components/FloatingChat.vue) & [Chat/Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Chat/Index.vue)
- Changed floating button icon from `mdi-robot` to `mdi-chat-processing`.
- Added legal reCAPTCHA disclosure line below chat message input.

---

## 3. Verification
- **Vite Client + SSR Build**: Succeeded in 6.27s with 0 errors.
- **Score Parsing**: Tested with raw floats, objects, and empty values.
- **Badge Visibility**: Verified `.grecaptcha-badge` is cleanly hidden without breaking invisible token execution.

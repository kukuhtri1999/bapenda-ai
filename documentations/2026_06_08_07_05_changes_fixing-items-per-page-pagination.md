# Changes Documentation: Fixing Items Per Page Pagination

**Date**: 2026-06-08 07:05 (Asia/Jakarta)  
**Branch**: `main-local`  
**Author**: Antigravity AI  

---

## 1. Issue Resolved

### The Problem
The "items per page" configuration for the Knowledge Base list was hardcoded to `15` items on the backend, meaning users could not control how many records were displayed per page. Furthermore, there was no dropdown selector on the frontend to modify this setting.

### The Solution
We implemented dynamic items-per-page support across both the backend controller and the frontend Vue component:
1. **Backend Dynamic Pagination**:
   Updated the `index()` method of [KnowledgeBaseController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/KnowledgeBaseController.php) to read the `per_page` query parameter (defaulting to `15` and restricting to safe numeric values: `5, 10, 15, 25, 50, 100`). The resolved `per_page` value is then merged and passed back within the `filters` prop.
2. **Frontend Ref & Form Binding**:
   Added a new `perPage` reactive reference (`const perPage = ref(props.filters.per_page || 15)`) in [Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/KnowledgeBase/Index.vue).
3. **Frontend Dropdown Selector**:
   Added a `VSelect` dropdown in the filters bar titled "Show" which offers choices of 5, 10, 15, 25, 50, and 100 items per page. Changing this dropdown triggers an immediate automatic reload of the page (`@update:model-value="applyFilters"`).
4. **Clean Parameter Passing**:
   Ensured the parameter is preserved on manual pagination updates at the bottom of the page (`VPagination`) and when resetting filters (`clearFilters` defaults it back to 15).
5. **Data Table Sync**:
   Set `VDataTable` `:items-per-page` attribute dynamically to the `perPage` reference instead of a hardcoded `15`.

---

## 2. Files Modified

### [KnowledgeBaseController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/KnowledgeBaseController.php)
- Modified `index(Request $request)` to dynamically paginate using the validated `per_page` input.

### [Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/KnowledgeBase/Index.vue)
- Declared the `perPage` reactive variable.
- Updated `applyFilters` and `clearFilters` helpers to set and send the `per_page` parameter.
- Inserted a `VSelect` dropdown for page limit control next to the "Status" filter in the template grid.
- Bound `VDataTable` `:items-per-page` attribute to `perPage`.

---

## 3. Verification & Validation
- Ran `php -l app/Http/Controllers/KnowledgeBaseController.php` to verify backend syntax validity.
- Ran `npm run build` to compile the frontend assets, which completed successfully without warnings or errors.

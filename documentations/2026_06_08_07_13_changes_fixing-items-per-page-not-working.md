# Changes Documentation: Fixing Items Per Page Not Working in Knowledge Base

**Date**: 2026-06-08 07:13 (Asia/Jakarta)  
**Branch**: `main-local`  
**Author**: Antigravity AI  

---

## 1. Root Cause Analysis

### The Problem
Even though the previous fix added a `per_page` dropdown and passed the parameter to the backend controller, changing the dropdown had no visible effect. The root causes were:

1. **Client-side vs Server-side Pagination Mismatch**: The `VDataTable` component was being given `:items-per-page="perPage"` which triggered its **client-side** pagination footer. Since the table only receives the items for the **current server page** (e.g., 15 records), the client-side paginator saw only those 15 items and showing 25 per page made no difference — there were never more than 15 items in the array.

2. **`VDataTable` Built-in Footer Conflict**: The VDataTable was rendering its own "Items per page" footer selector at the bottom, conflicting with our custom `VPagination` control below the table.

3. **`VPagination` Not Preserving `per_page`**: The previous `VPagination` handler was using `{ ...filters }` spread, but `filters` is the original Inertia prop object that **may not have the freshly updated `perPage` ref value** in all edge cases. It should explicitly reference the reactive ref instead.

4. **`Status` VSelect missing `density` and `hide-details` attributes**: These were accidentally stripped in a previous edit, breaking the visual consistency of the filter bar.

---

## 2. Files Modified

### [Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/KnowledgeBase/Index.vue)

#### Fix 1 — VDataTable: Disable Client-side Pagination
Changed `:items-per-page="-1"` (show all items in the current server page) and added `hide-default-footer` to suppress the conflicting built-in footer:
```vue
<VDataTable
  :items-per-page="-1"
  hide-default-footer
  ...
>
```
This tells Vuetify: "render all items you receive in one go — don't paginate client-side." All real pagination is now handled server-side via the `VPagination` component below.

#### Fix 2 — VPagination: Always Pass `per_page` Explicitly
Replaced the `{ ...filters, page }` spread with an explicit list of all filter params including `per_page: perPage`, so that when navigating between pages, the per_page setting is always preserved in the URL:
```js
router.get(route('knowledge-base.index'), {
  search: search,
  category: categoryFilter,
  type: typeFilter,
  source_type: sourceTypeFilter,
  status: statusFilter,
  is_active: activeFilter,
  per_page: perPage,
  page,
})
```

#### Fix 3 — Status VSelect: Restore Missing Attributes
Restored `density="compact"` and `hide-details` on the Status filter dropdown that were accidentally removed in a previous edit.

#### Enhancement — Pagination Info Row
Added a "Showing X–Y of Z entries" text beside the `VPagination` controls so users can confirm the per_page setting is working:
```vue
<span class="text-caption text-medium-emphasis">
  Showing {{ knowledgeBases.from ?? 0 }}–{{ knowledgeBases.to ?? 0 }} of {{ knowledgeBases.total ?? 0 }} entries
</span>
```

---

## 3. Verification & Validation
- Ran `npm run build` → completed successfully in 11.47s without any errors.
- All SSR assets compiled without issues.

---
glob: resources/js/Pages/**/*.jsx, resources/js/components/**/*Table.jsx
title: Listing, Add, Edit and Add Item pages follow the Sales Orders pages
---

Sales Orders is the reference implementation for document-style CRUD screens. When building or refactoring a listing, add, edit, or add-item screen (Returns, Receipts, Stocks, Purchases, etc.), copy the structure of the matching Orders file rather than inventing a new layout.

| Screen | Reference |
|---|---|
| Listing | `Pages/Sales/Orders/OrderIndex.jsx` + `components/sales/OrderTable.jsx` |
| Add | `Pages/Sales/Orders/OrderFormNew.jsx` |
| Edit | `Pages/Sales/Orders/OrderForm.jsx` |
| Add item (inside edit) | `Pages/Sales/Orders/OrderItemForm.jsx` |

**Listing**
- `PageHeader` with `description={meta?.total ? `${meta.total} total` : undefined}` and a `btn btn-sm btn-theme` "New …" `InertiaLink` (`solar:add-bold-duotone` icon) in `buttons`, gated by `canAdd`.
- `PageFilters` > filter component, then `PageContent` > `Panel.hf-table-panel` > `PanelBody` > `<XTable>` + `NoData` when empty + `PaginationFull`.
- Table lives in `components/<module>/XTable.jsx`: `table table-hover align-middle mb-0 hf-list-table`, status as `hf-pill tone-*`, actions column `w-1 text-end` with `hf-row-actions`: `InertiaView`, then `InertiaEdit` (from `@/components/Actions`), then secondary actions in `RowActionsMenu`. Each gated by `can_*` flags.

**Add**
- Minimal create step: `PageHeader` with `description` and `BackButton` (list label) in `buttons`.
- `ValidationErrors`, then `form` > `Panel.hf-form-panel` > `PanelBody` > `FormSection` / `FormField`.
- `FormActions` with required-fields hint: `BackButton label="Cancel"` then `LoadingButton type="submit" variant="theme"`.
- On success redirect to Edit, where items are added.

**Edit**
- `PageHeader` title with document number, `description` with party name/city, `buttons` = `Back` (list label) + `PreviewButton` to show page.
- `PageContent` order: `ValidationErrors` → summary stats → items `Panel.hf-table-panel` → header form → `FormActions` → item modal.
- Items panel: `PanelHeader` with `heading` "Items (n)" and `buttons` = `btn btn-xs btn-theme` "Add item" (hidden when locked).
- Item rows: `hf-row-actions` with edit `hf-icon-btn hf-icon-btn--boxed` (`solar:pen-2-bold-duotone`) then `DeleteAjax` wrapped in `hf-icon-btn hf-icon-btn--boxed is-danger`.
- Empty items: `NoData label="No items added yet."` + `btn btn-sm btn-theme` "Add first item".
- `FormActions` with dirty/totals hint and `LoadingButton` actions.

**Add item**
- Separate named-export component `XItemForm` rendered from Edit as a `react-bootstrap` `Modal` (`backdrop="static"`, `size="lg"`), used for both add and edit (`isEdit = Boolean(item)`).
- Title `'Edit Item' : 'Add Item'`; fields via `FormField`; submits over ajax and updates parent via `setItems`.
- Footer: add mode shows hint + `Clear`, then `Button variant="white"` (`Cancel`/`Done`), then `LoadingButton` (`Update item` / `Save & add next`).

See also [[panel-header-props]], [[pageheader-buttons-on-show-pages]], [[page-content-wrapper]], [[tbody-empty-state-nodata]].

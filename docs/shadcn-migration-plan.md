# Migrate UI from Bootstrap / react-bootstrap / Color Admin to Tailwind v4 + shadcn/ui

## Context
UI today = Bootstrap 5 + Color Admin SCSS (12.6k lines, `resources/scss/default`) + react-bootstrap (116 files) + sweetalert2, react-toastify, react-select, react-datetime. Goal: full removal of Bootstrap stack, rebuild on Tailwind v4 + shadcn/ui, **same functionality and same look** (navy/gold "theme-afi" design from `_theme-modern.scss` + `hf-*` files). User chose: full removal, replace sweetalert2 / toastify / react-select / react-datetime too, phased rollout.

Key facts from exploration:
- Real theme tokens (from `_custom-variables.scss`, `_theme-modern.scss`): primary navy `#0d1e45`, accent gold `#c09748`, success `#0f8f7e`, info `#0e87a8`, warning `#b7832b`, danger `#dc2626`; body bg `#f4f1eb`, surface `#fff`, surface-2 `#faf8f4`, muted `#efebe3`, border `#e7e1d6`, input border `#d8d0c1`, hover `#f3efe8`; text `#141b2d` / `#566074` / `#99a0ad`; font **Inter** 13px base (sidebar Plus Jakarta Sans), `font-feature-settings: 'cv11','ss01','tnum'`; radius 8px (sm 6, lg 12); `--hf-shadow*`; focus ring `0 0 0 3px rgba(13,30,69,.14)`. Dark mode defined but never enabled — skip.
- Layout assigned in `resources/js/app.tsx`; active shell = `layouts/app/AppSidebarLayout.jsx` (hf-* sidebar). Dead code: `DateRangePicker.jsx`, `Dropdown.jsx`, `FlashMessage_Dep.jsx`, `components/header/`, `components/top-menu/`, `layouts/app/AppHeaderLayout.jsx`.
- High-leverage shared components (page import counts): `page.jsx` PageHeader/PageContent (99), `panel/panel.jsx` (88), `NoData` (49), `LoadingButton` (37), `button/back.tsx` (37), `CustomDate` (21), `FormSection.jsx` (20), `StyledSelect` (18), `ValidationErrors` (16), `File.jsx` (16), `Pagination*` (24).
- sweetalert2 only via `util/swal.jsx` (`confirmSwal`, `confirmDelete`). toastify only via `util/util.jsx` (`notifyMessage`, `serverSideError`, `formAlert`) + `FlashMessage.jsx`.
- react-select: 27 files, all use Controller `{...field}` with object values, `getOptionLabel/Value`, `isClearable`, 2 `isMulti`; no async/creatable.
- react-datetime: 21 files, all date-only `DD-MMM-YYYY` (`settings.SEARCH_DATE_FORMAT`), 17 are start/end pairs.
- Modals: 12, all `backdrop="static" size="lg" keyboard`.

## Approach

### Coexistence strategy (phases 0–5)
Both stacks load during migration. New entry `resources/css/app.css`:
```css
@layer theme, base, legacy, components, utilities;
@import 'tailwindcss';
```
Wrap Bootstrap SCSS output in `@layer legacy` (in `styles.scss`, `@layer legacy { @import ... }`; verify dart-sass 1.77 handles it — fallback: keep scss unlayered and use Tailwind `important` only on converted components). Result: Tailwind preflight < Bootstrap reboot < Tailwind components/utilities, so converted markup wins and unconverted markup stays intact. Each phase ships a working app.

### Phase 0 — Infrastructure
- Deps (approved by request): `tailwindcss @tailwindcss/vite tw-animate-css clsx tailwind-merge class-variance-authority radix-ui cmdk react-day-picker sonner lucide-react` (lucide needed by shadcn internals; app icons stay Iconify/FontAwesome).
- `vite.config.js`: add `tailwindcss()` plugin, add `resources/css/app.css` input. `app.blade.php` + `errors/minimal.blade.php`: `@vite` the new css.
- Load `shadcn` skill, `shadcn init` → `components.json` (tsx, aliases `@/components/ui`, `@/lib/utils`, css `resources/css/app.css`). `resources/js/lib/utils.ts` `cn()`.
- Theme in `app.css` `@theme inline` + `:root`: map shadcn vars (`--background #f4f1eb`, `--card #fff`, `--primary #0d1e45`, `--accent`/`--gold #c09748`, `--destructive #dc2626`, `--border #e7e1d6`, `--input #d8d0c1`, `--ring`, `--radius .5rem`, `--muted`, `--foreground #141b2d`, `--muted-foreground #566074`) + extra tokens (`success`, `info`, `warning`, `surface-2`, `hover`, `text-tertiary`, sidebar colors, shadows, easing). `--font-sans: Inter`, html font-size basis → 13px body (`text-[13px]` on body, keep rem scale).
- `shadcn add` button input textarea label checkbox switch native-select input-group field dialog alert-dialog alert badge table tooltip dropdown-menu popover command calendar sonner toggle-group spinner separator scroll-area skeleton. Customise `button.tsx` variants to match current look: `default` (theme navy), `success`, `primary`, `danger/destructive`, `white` (outline white), `link`, `ghost`; sizes `xs`, `sm`, `default`, `lg`, `icon`.
- Capture baseline screenshots (Firefox devtools MCP) of ~15 key pages: login, dashboard, order index/new/edit/item modal/view+print, purchase edit, customer form, journal form, ledger, a date-range report, role form, settings.

### Phase 1 — Shared primitives, same public APIs (pages untouched)
Rewrite internals only, keep props:
- `LoadingButton.jsx` → shadcn `Button` + `Spinner`, keeps `processing`, `variant='success'`, built-in icon.
- `components/ui/button.tsx` SubmitButton/FormSubmitButton → shadcn Button (rename file clash: move to `components/SubmitButton.tsx`, update 1 importer, since `ui/button.tsx` becomes the shadcn one).
- `button/back.tsx`, `button/PreviewButton.jsx`, `button/DownloadPdf.jsx` → `buttonVariants`.
- `ui/OverlayTrigger.jsx` → thin wrapper over shadcn `Tooltip` keeping `overlay`/`placement` props (Radix closes on dialog open; drop MutationObserver). Update `Actions.jsx`, `PriceView.jsx`, `File.jsx`, `CustomDate.jsx`, `panel/ErrorPanel.jsx`, `CashBankSummary.jsx`, `GraphDashboard.jsx`.
- `RowActionsMenu.jsx` + `*DropdownItem` in `Actions.jsx` → shadcn `DropdownMenu` (same `label`, children API).
- `NoData`, `Pagination`, `PaginationFull`, `ValidationErrors` (→ shadcn `Alert` warning style), `page.jsx` (also fix `"undefined"` className via `cn`), `panel/panel.jsx` (Panel/PanelHeader/PanelBody/PanelFooter as function components on Tailwind, keep `theme`, `heading`, `buttons`, `className`), `panel/Widget.jsx`, `form/FormSection.jsx` (FormSection/FormField/FormActions/SegmentedControl/OptionCards).

### Phase 2 — Feedback libraries
- `util/swal.jsx` → `util/confirm.jsx`: module-level store + `<ConfirmDialogHost/>` (shadcn `AlertDialog`) mounted in `AppSidebarLayout`. Same exports/signatures: `confirmSwal({...}) → Promise<{isConfirmed}>`, `confirmDelete({text,onConfirm})` with loading state, blocked dismiss while pending, error message on reject. Update 7 importers' import path only.
- `util/util.jsx` `notifyMessage` / `serverSideError` / `formAlert` → `sonner` `toast.success/error/...` (keep signatures, durations 2s/5s). `FlashMessage.jsx` → `<Toaster position="bottom-right" richColors />` + existing flash `useEffect`.

### Phase 3 — Select and date inputs
- New `components/Combobox.jsx` (Popover + Command) with react-select-compatible subset: `options`, `value` (object | array), `onChange(obj|arr|null)`, `onBlur`, `name`, `getOptionLabel`, `getOptionValue`, `isClearable`, `isMulti` (chips), `placeholder`, `autoFocus`, `isInvalid`, forwarded ref (Controller). Re-implement `StyledSelect.jsx` as re-export of it → 18 files unchanged; switch 9 raw `react-select` files to `StyledSelect`.
- New `components/DatePicker.jsx` (Popover + Calendar + button showing `DD-MMM-YYYY` via date-fns). API: `value` (`YYYY-MM-DD` string | Date), `onChange(string 'YYYY-MM-DD')`, `min`/`max`, `placeholder`, `clearable`. New `components/DateRangeInputs.jsx` for start/end pairs (constrains each other like old `isValidDate`). Update 21 files (17 range filters, 4 single) — replace moment `.format('YYYY-MM-DD')` handlers.
- `pages/Settings/Role/RoleForm.jsx`: `react-perfect-scrollbar` → shadcn `ScrollArea`; keep `checkbox-tree-react-19` (own CSS, restyle via `app.css`).

### Phase 4 — App shell and custom CSS
- Port app-owned standalone CSS (no Bootstrap dependency) from SCSS to plain CSS files under `resources/css/` imported by `app.css` in `@layer components`, replacing Bootstrap vars/mixins with theme vars: `_hf-sidebar` → `sidebar.css`, `_hf-list` → `list.css`, `_hf-form` → `form.css`, `_hf-orders` → `orders.css`, `_hf-customers` → `customers.css`, standalone bits of `_custom.scss` (animate-fade-up + stagger, page wrappers, no-data, file-row, num, w-1, input-100/150, urdu) → `custom.css`, `_print.scss` → `print.css` (keep `.hidden-print`, `.print-only`, `.page-break`, `.pdf-export`), Color Admin `pages/_invoice` → `invoice.css`, `ui/_widget-stats` + `_widget-chart` → `widgets.css`. Class names kept, so 30+ view pages and `hf-*` usage need no JSX change.
- `AppSidebarLayout.jsx` drop `app-content`/`app-footer` (Tailwind), sidebar `SidebarUser` Dropdown → `DropdownMenu`. `AuthLayout.jsx` news-feed split → Tailwind grid.

### Phase 5 — Pages, module by module
Order: Auth → Settings → Catalog → Sales → Purchases → Stock → Accounts → Reports → Dashboard/Widgets (Sales first = reference per `.ai/rules/order-pages-as-crud-reference.md`). Per file:
| Bootstrap | Replacement |
|---|---|
| `Row`/`Col md=6`, `row g-3` | `grid grid-cols-12 gap-3` + `col-span-12 md:col-span-6` |
| `Form.Group`/`Form.Label` | `Field`/`Label` (or existing `FormField`) |
| `Form.Control`, `FormControl`, `as="textarea"` | `Input` / `Textarea`, `isInvalid` → `aria-invalid` |
| `InputGroup` + `.Text` | `InputGroup` + `InputGroupAddon`/`InputGroupText` |
| `Form.Select`/`FormSelect` | `NativeSelect` (keeps `register`) |
| `Form.Check` switch / checkbox | `Switch` / `Checkbox` via `Controller` |
| `FloatingLabel` (5 auth pages) | small `FloatingLabelInput` (peer-placeholder-shown) to keep look |
| `Modal backdrop=static size=lg` | `Dialog` + `DialogContent className="sm:max-w-[800px]" onInteractOutside={e=>e.preventDefault()}` |
| `Table`, `table table-hover table-bordered` | shadcn `Table` / Tailwind table classes |
| `Badge`, `Alert`, `Spinner` | shadcn equivalents |
| `ToggleButtonGroup` (BalanceReport) | `ToggleGroup` |
| `btn btn-sm btn-theme` on `InertiaLink` | `buttonVariants({size:'sm'})` |
| utilities `mb-3`, `text-end`, `d-flex`, `fw-600`, `m-t-5`, `ms-5px`, `text-inverse`, `text-muted`, `float-end`, `text-nowrap`, `table-responsive` | Tailwind (`mb-3`→`mb-3` scale check: BS 1rem vs TW .75rem → use `mb-4`; `text-end`→`text-right`, `fw-600`→`font-semibold`, `m-t-5`→`mt-[5px]`, `text-inverse`→`text-foreground`, `text-muted`→`text-muted-foreground`, `table-responsive`→`overflow-x-auto`) |
Write a full mapping cheat-sheet as part of Phase 0 so every page converts consistently. Honour `.ai/rules` (PageContent wrapper, LoadingButton no icon, NoData in tbody, PageHeader buttons) while touching each page.

### Phase 6 — Removal
- Delete `resources/scss/`, remove `styles.scss` from `vite.config.js` and Blade; drop `@layer legacy`.
- `npm uninstall bootstrap react-bootstrap sweetalert2 sweetalert2-react-content react-toastify react-select react-datetime react-perfect-scrollbar sass`; remove `sweetalert2.css` import in `app.tsx`.
- Delete dead code listed in Context. Grep gate: zero hits for `react-bootstrap|sweetalert|toastify|react-select|react-datetime|btn-|form-control|col-md-|panel-heading`.
- `.ai/rules/order-pages-as-crud-reference.md` and `loading-button-no-icon-variant.md` mention react-bootstrap Modal / `btn` classes — ask user before updating rules.

## Critical files
`vite.config.js`, `resources/views/app.blade.php`, `resources/js/app.tsx`, `resources/css/app.css` (new), `components.json` (new), `resources/js/components/{LoadingButton,page,StyledSelect,Actions,RowActionsMenu,NoData,Pagination,PaginationFull,ValidationErrors,CustomDate,File,PriceView,FlashMessage}.jsx`, `components/panel/panel.jsx`, `components/form/FormSection.jsx`, `components/button/*`, `components/ui/*`, `components/sidebar/*`, `layouts/app/AppSidebarLayout.jsx`, `layouts/AuthLayout.jsx`, `util/swal.jsx`, `util/util.jsx`, then ~140 pages (e.g. `pages/Sales/Orders/OrderForm.jsx`, `OrderItemForm.jsx`, `pages/Accounts/Journals/JournalForm.jsx`, `pages/Reports/DateRangeReportFilter.jsx`).

## Verification (every phase)
- `npm run build` and `npm run lint:check` clean.
- Firefox devtools MCP: screenshot same baseline pages, compare visually; exercise: login, create order → add item modal (combobox, save & add next), delete row (confirm dialog loading state), flash toast, date-range report filter, print preview + PDF download, sidebar collapse/mobile, role form tree.
- `browser-logs` Boost tool for JS errors.
- `php artisan test --compact` (Inertia page tests must still resolve components); ask user to run full suite at end.
- Phase 6 grep gate returns nothing.

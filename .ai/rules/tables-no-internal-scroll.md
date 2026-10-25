---
glob: resources/js/**/*.jsx, resources/scss/**/*.scss
title: Tables — no internal scroll boxes; single-line cells; header sticks to the viewport
---

Tables scroll with the page, never inside their own box. The app-wide styles in `resources/scss/default/_theme-modern.scss` already handle this for every `.table`:

- Cells are `white-space: nowrap` on screen so rows stay on one line and use space efficiently.
- On desktop (≥ 992px) `thead` is `position: sticky; top: 0` (and a `scroll-state` container, so header cells can restyle while stuck), so the header row pins to the top of the viewport once the table scrolls up to it. `.table-responsive` is `overflow: visible` on desktop so it doesn't trap the sticky header.

Avoid this pattern:

```jsx
<div className="table-responsive" style={{ maxHeight: 500, overflowY: 'auto' }}>
```

```scss
.my-table-wrapper {
  max-height: calc(100vh - 20rem);
  overflow: auto;
}
```

Use a plain wrapper instead:

```jsx
<div className="table-responsive">
    <table className="table table-hover align-middle mb-0 hf-list-table">
```

Rules:

- Do not add `max-height` + `overflow: auto/scroll` around a table, and do not reintroduce a scrollable-table utility.
- Do not put `overflow: hidden` on a table or on an element that wraps one (it creates a scroll container and breaks the sticky header). Use `overflow: clip` when you need to clip rounded corners.
- Do not add per-table `position: sticky` styles; the global rule covers it.
- Do not add `text-wrap`/`white-space: normal` to cells unless a column genuinely needs wrapping (e.g. long free-text notes); prefer truncation with a `title` attribute.

---
glob: resources/js/Pages/**/*.jsx
title: PanelHeader — use `heading`/`buttons` props, not inline title + float-end div
---

`PanelHeader` (`resources/js/components/panel/panel.jsx`) accepts `heading` and `buttons` props for this exact case. Do not put the title as a text child with a `<div className="float-end">` wrapping the action buttons.

Avoid this pattern:

```jsx
<PanelHeader>
    Create Order
    <div className="float-end">
        <LoadingButton ...>...</LoadingButton>
        <BackButton href={route("sales.orders.index")} size="xs" />
    </div>
</PanelHeader>
```

Replace with:

```jsx
<PanelHeader heading={"Create Order"} buttons={(
    <>
        <LoadingButton ...>...</LoadingButton>
        <BackButton href={route("sales.orders.index")} size="xs" />
    </>
)} />
```

When adding or updating a page's `PanelHeader`, check for the old text-child + `float-end` pattern and migrate it as part of the change.

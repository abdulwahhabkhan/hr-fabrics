---
glob: resources/js/Pages/**/*.jsx
title: PageHeader on Sales/Purchases show pages — put actions in `buttons` prop, not a `float-end` div in the body
---

`PageHeader` (`resources/js/components/page.jsx`) accepts a `buttons` prop. On show/view pages (`*View.jsx`) in `Sales/**` and `Purchases/**`, do not render Back/Edit/Print/file-info actions as a `float-end`/`hidden-print`/`d-print-none` div inside `invoice-company`. Move them into `PageHeader`'s `buttons` prop instead.

Avoid this pattern:

```jsx
<PageHeader title="Order View" />
...
<div className="invoice-company text-inverse fw-600">
    <div className="float-end ms-10px hidden-print">
        <div className="d-flex align-items-center gap-2">
            <BackButton href={route("sales.orders.index")} />
            {order.status === 0 && <InertiaLink href={route("sales.orders.edit", order.id)} className="btn btn-sm btn-white">Edit</InertiaLink>}
            <Print />
        </div>
    </div>
    {appName}
</div>
```

Replace with:

```jsx
<PageHeader title="Order View" buttons={(
    <>
        <BackButton href={route("sales.orders.index")} label="Orders List" />
        {order.status === 0 && <InertiaLink href={route("sales.orders.edit", order.id)} className="btn btn-sm btn-white">Edit</InertiaLink>}
        <Print />
    </>
)} />
...
<div className="invoice-company text-inverse fw-600">
    {appName}
</div>
```

Order of buttons: `BackButton` first, then conditional `Edit`, then `Print`, then any file/attachment icon (`FileIcon`). Drop the now-redundant `mb-10px ms-5px me-5px` spacing classes on individual buttons — `PageHeader`'s `buttons` wrapper (`d-lg-flex align-items-center gap-3`) already spaces them.

Applied to: `Sales/Orders/OrderView.jsx`, `Purchases/Receipts/ReceiptView.jsx`, `Purchases/Stocks/StockView.jsx`, `Purchases/Returns/ReturnView.jsx`, `Sales/Returns/ReturnView.jsx`. See also [[panel-header-props]] for the equivalent rule on `PanelHeader` (form pages, not `PageHeader`).

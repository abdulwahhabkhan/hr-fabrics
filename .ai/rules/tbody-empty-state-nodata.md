---
glob: resources/js/Pages/**/*.jsx
title: Use `NoData` for empty-state rows instead of a `<tr><td className="no-data">` row inside `tbody`
---

Do not render the empty-state message as a table row inside `<tbody>`:

```jsx
{data.length === 0 && (
    <tr>
        <td className="no-data" colSpan="7">
            No data found.
        </td>
    </tr>
)}
```

Replace with `resources/js/components/NoData.jsx`, placed after the closing `</table>` (inside the same wrapping `div`, e.g. `.table-responsive`), with a label describing what the page lists:

```jsx
</table>
{data.length === 0 && (<NoData label="No fabric purchases found." />)}
```

Import it with `import NoData from '@/components/NoData.jsx';`. `NoData` renders its own bordered box with an icon, not a `<tr>`/`<td>`, so it cannot live inside `tbody`. The label is dynamic per page — describe the specific list (e.g. "No suppliers found.", "No purchase returns found."), not a generic "No data found."

The same applies to item tables inside forms (e.g. `ReceiptForm.jsx`, `OrderForm.jsx`) — move the `NoData` check to just after `</table>`, describing the items (e.g. "No order items added.").

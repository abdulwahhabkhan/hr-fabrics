---
glob: resources/js/**/*.jsx
title: LoadingButton — never pass an `Icon` child; only pass `variant` for non-success colors
---

`LoadingButton` (`resources/js/components/LoadingButton.jsx`) renders its own icon internally and defaults `variant="success"`. Never pass an `<Icon>`/`<FontAwesomeIcon>` as a child — it always duplicates the built-in one. Only pass `variant` when the button needs a non-success color (`danger`, `primary`, `theme`, `white`, etc.); omit it for success buttons.

Avoid this pattern:

```jsx
<LoadingButton variant="success" processing={processing} onClick={handleSubmit(sendRequest)}>
    <Icon icon={"solar:diskette-bold-duotone"} /> Save Changes
</LoadingButton>
```

Replace with:

```jsx
<LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
    Save Changes
</LoadingButton>
```

A non-success button keeps its explicit `variant`, just drops the icon:

```jsx
<LoadingButton variant="danger" processing={processing} onClick={handleSubmit(confirmRequest)}>
    Confirm & Close
</LoadingButton>
```

When adding or updating any `LoadingButton` usage, check for a manually passed icon child and a redundant `variant="success"` and remove both as part of the change.

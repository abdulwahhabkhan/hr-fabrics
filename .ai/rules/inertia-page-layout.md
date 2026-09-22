---
glob: resources/js/Pages/**/*.jsx
title: Inertia page layout — no per-page `.layout`, use Head + PageHeader
---

`resources/js/app.jsx` already applies `AppLayout` to every page by default (see the `layout:` switch in `createInertiaApp`). A page component does not need to set its own `.layout`.

Avoid this pattern:

```jsx
AttendanceReport.layout = page => {
    return <Authenticated
        title={'Attendance Report'}
        children={page}
        header={<h1 className="page-header">Attendance Report <small></small></h1>}
    />
}
```

Replace with `Head` + `PageHeader` rendered inline in the component, matching the rest of the codebase (e.g. `resources/js/Pages/Accounts/Accounts/AccountForm.jsx`):

```jsx
<>
    <Head title="Attendance Report" />
    <PageHeader title="Attendance Report" />
    ...
</>
```

Drop the `Authenticated` import and the `.layout` assignment entirely once converted.

When modifying any page component under `resources/js/Pages`, check for this `.layout` pattern first and migrate it as part of the change.

---
glob: resources/js/Pages/**/*.jsx
title: Wrap page body in `PageContent` for the staggered fade-up entrance animation
---

`resources/js/components/page.jsx` exports `PageContent` alongside `PageHeader`. The fade-up entrance animation (`.animate-fade-up` in `resources/scss/default/_custom.scss`) is staggered by `animation-delay` per wrapper class: `#header` (0s), `#top-menu` (50ms), `#content` (100ms), `.page-header-wrapper` (120ms, applied automatically inside `PageHeader`), `.page-content-wrapper` (250ms, applied automatically inside `PageContent`). Without `PageContent`, the body content skips its staggered fade-in and pops in with `#content`'s animation only.

Every page component that renders `PageHeader` must wrap everything that follows it in `PageContent`:

```jsx
<>
    <Head title="..." />
    <PageHeader title="..." />
    <PageContent>
        <Panel>
            ...
        </Panel>
    </PageContent>
</>
```

Import `PageContent` from the same module: `import { PageContent, PageHeader } from '@/components/page.jsx';`

When adding a new page or modifying an existing one under `resources/js/Pages`, check for a `PageHeader` without a matching `PageContent` wrapper and add it as part of the change. See also [[inertia-page-layout]].

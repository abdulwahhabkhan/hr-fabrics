import React from 'react';
import { InertiaLink } from '@/util/Inertia';

/** HR Fabrics lockup: H|R monogram + wordmark (wordmark hides when collapsed). */
export default function SidebarBrand() {
    return (
        <InertiaLink href={route('dashboard')} className="hf-brand" aria-label="HR Fabrics — Dashboard">
            <span className="hf-monogram" aria-hidden="true">
                <span>H</span>
                <i />
                <span className="hf-monogram-accent">R</span>
            </span>
            <span className="hf-wordmark">
                <span className="hf-wordmark-name">HR Fabrics</span>
                <span className="hf-wordmark-tag">International</span>
            </span>
        </InertiaLink>
    );
}

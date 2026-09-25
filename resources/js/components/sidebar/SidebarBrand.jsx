import React from 'react';
import cx from 'classnames';
import { InertiaLink } from '@/util/Inertia';
import { dashboard } from '@/routes';
import logoLight from '@/img/brand/hr-fabrics-logo-horizontal.svg';
import logoDark from '@/img/brand/hr-fabrics-logo-horizontal-reverse.svg';
import monogramLight from '@/img/brand/hr-fabrics-monogram.svg';
import monogramDark from '@/img/brand/hr-fabrics-monogram-reverse.svg';

/**
 * HR Fabrics brand mark (vector SVGs in img/brand, no web-font dependency).
 * tone="light" → original navy + gold, for light backgrounds (sidebar, mobile bar)
 * tone="dark"  → white + gold, for navy backgrounds
 * The full lockup swaps to the H|R monogram when the sidebar is collapsed (CSS).
 */
export default function SidebarBrand({ tone = 'dark' }) {
    const dark = tone === 'dark';

    return (
        <InertiaLink href={dashboard()} className={cx('hf-brand', `hf-brand--${tone}`)} aria-label="HR Fabrics International — Dashboard">
            <img src={dark ? logoDark : logoLight} alt="HR Fabrics International" className="hf-brand-logo" />
            <img src={dark ? monogramDark : monogramLight} alt="" aria-hidden="true" className="hf-brand-monogram" />
        </InertiaLink>
    );
}

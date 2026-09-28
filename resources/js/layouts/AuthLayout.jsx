import React from 'react';
import { usePage } from '@/util/Inertia.jsx';
import authBg from '@/img/bg/auth-weave.svg';
import logoReverse from '@/img/brand/hr-fabrics-logo-reverse.svg';
import logo from '@/img/brand/hr-fabrics-logo-horizontal.svg';

/**
 * Split-screen auth shell: navy woven-fabric brand panel on the left,
 * form card on the right. Below `lg` the brand panel is hidden and the
 * horizontal logo sits above the form instead.
 */
export default function AuthLayout({ children, title, description }) {
    const { appName, store } = usePage().props;
    const brandName = appName || 'HR Fabrics';

    return (
        <div className="hf-auth">
            <aside
                className="hf-auth-brand"
                style={{ backgroundImage: `url(${authBg})` }}
            >
                <a href="/" className="hf-auth-brand-logo">
                    <img src={logoReverse} alt={brandName} />
                </a>

                <div className="hf-auth-brand-copy">
                    <span className="hf-auth-eyebrow">
                        Fabric trading suite
                    </span>
                    <h2>Every roll, order and ledger — woven together.</h2>
                    <p>
                        Sales, purchases, stock and accounts for{' '}
                        {store?.branch_name
                            ? `the ${store.branch_name} branch`
                            : 'your branch'}
                        , in one place.
                    </p>
                </div>

                <div className="hf-auth-brand-footer">
                    © {new Date().getFullYear()} {brandName}
                    {store?.branch_name && <span> · {store.branch_name}</span>}
                </div>
            </aside>

            <main className="hf-auth-main">
                <div className="hf-auth-card">
                    <a href="/" className="hf-auth-logo">
                        <img src={logo} alt={brandName} />
                    </a>

                    {(title || description) && (
                        <div className="hf-auth-header">
                            {title && <h1>{title}</h1>}
                            {description && <p>{description}</p>}
                        </div>
                    )}

                    <div className="hf-auth-content">{children}</div>
                </div>
            </main>
        </div>
    );
}

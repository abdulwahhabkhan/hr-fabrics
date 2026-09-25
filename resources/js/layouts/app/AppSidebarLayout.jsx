import React, { useEffect, useState } from 'react';
import { usePage } from '@/util/Inertia';
import AppSidebar from '@/components/sidebar/AppSidebar';
import SidebarBrand from '@/components/sidebar/SidebarBrand';
import SidebarGlyph from '@/components/sidebar/SidebarGlyph';
import useSidebarState from '@/components/sidebar/useSidebarState';
import FlashMessage from '@/components/FlashMessage';
import { AppName } from '@/config/page-settings';

export default function AppSidebarLayout({ header, children }) {
    const { collapsed, toggle } = useSidebarState();
    const [mobileOpen, setMobileOpen] = useState(false);
    const { url } = usePage();

    // Close the mobile drawer after every navigation.
    useEffect(() => setMobileOpen(false), [url]);

    return (
        <div className="hf-app">
            <AppSidebar
                collapsed={collapsed}
                onToggle={toggle}
                mobileOpen={mobileOpen}
                onMobileClose={() => setMobileOpen(false)}
            />

            <div className="hf-main">
                {/* Mobile only: the sidebar becomes a drawer. */}
                <div className="hf-mobile-bar">
                    <button type="button" className="hf-mobile-toggle" onClick={() => setMobileOpen(true)} aria-label="Open menu">
                        <SidebarGlyph name="menu" size={20} />
                    </button>
                    <SidebarBrand />
                </div>

                <main id="content" className="app-content animate-fade-up">
                    {header && <>{header}</>}
                    {children}
                </main>

                <footer id="footer" className="app-footer hf-footer">
                    <span>&copy; {new Date().getFullYear()} {AppName}. All rights reserved.</span>
                    <span>
                        Developed by{' '}
                        <a href="https://sudotech.co.uk" target="_blank" rel="noreferrer">
                            SUDOTECH
                        </a>
                    </span>
                </footer>
            </div>

            <FlashMessage />
        </div>
    );
}

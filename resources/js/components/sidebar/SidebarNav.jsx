import React, { useEffect, useState } from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { InertiaLink, usePage } from '@/util/Inertia';
import useSidebarMenu from './useSidebarMenu';
import SidebarGlyph from './SidebarGlyph';

/**
 * Expanded: accordion (the active section opens automatically).
 * Collapsed: icon rail; hovering or focusing an item shows a flyout with its
 * pages (pure CSS, see _hf-sidebar.scss).
 */
export default function SidebarNav() {
    const items = useSidebarMenu();
    const { url } = usePage();
    const activeSection = items.find((item) => item.active && item.children.length)?.name ?? null;
    const [openSection, setOpenSection] = useState(activeSection);

    // Follow navigation: open the section that owns the new page.
    useEffect(() => {
        setOpenSection(activeSection);
    }, [url, activeSection]);

    return (
        <nav className="hf-nav" aria-label="Main">
            {items.map((item) => {
                const hasChildren = item.children.length > 0;
                const isOpen = hasChildren && openSection === item.name;
                // Fixed-size slot so labels never shift while an icon loads.
                const icon = (
                    <span className="hf-nav-icon" aria-hidden="true">
                        {item.icon && <Icon icon={item.icon} />}
                    </span>
                );

                return (
                    <div key={item.name} className={cx('hf-nav-item', { 'is-active': item.active, 'is-open': isOpen })}>
                        {hasChildren ? (
                            <button
                                type="button"
                                className="hf-nav-link"
                                aria-expanded={isOpen}
                                onClick={() => setOpenSection(isOpen ? null : item.name)}
                            >
                                {icon}
                                <span className="hf-nav-text">{item.title}</span>
                                <SidebarGlyph name="chevronDown" size={14} className="hf-nav-caret" />
                            </button>
                        ) : (
                            <InertiaLink href={item.path} className="hf-nav-link">
                                {icon}
                                <span className="hf-nav-text">{item.title}</span>
                            </InertiaLink>
                        )}

                        {hasChildren && (
                            // Outer grid animates 0fr -> 1fr (smooth height); inner wrapper clips.
                            <div className="hf-subnav" aria-hidden={!isOpen}>
                                <div className="hf-subnav-inner">
                                    {item.children.map((child) => (
                                        <InertiaLink
                                            key={child.name}
                                            href={child.path}
                                            className={cx('hf-subnav-link', { 'is-active': child.active })}
                                        >
                                            {child.title}
                                        </InertiaLink>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Collapsed-mode flyout (hidden in expanded mode via CSS). */}
                        <div className="hf-flyout" role="menu">
                            <div className="hf-flyout-title">{item.title}</div>
                            {hasChildren ? (
                                item.children.map((child) => (
                                    <InertiaLink
                                        key={child.name}
                                        href={child.path}
                                        className={cx('hf-flyout-link', { 'is-active': child.active })}
                                    >
                                        {child.title}
                                    </InertiaLink>
                                ))
                            ) : null}
                        </div>
                    </div>
                );
            })}
        </nav>
    );
}

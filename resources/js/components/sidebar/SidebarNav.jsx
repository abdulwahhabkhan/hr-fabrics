import React, { useEffect, useRef, useState } from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { InertiaLink, usePage } from '@/util/Inertia';
import useSidebarMenu from './useSidebarMenu';
import SidebarGlyph from './SidebarGlyph';

/**
 * Expanded: accordion (the active section opens automatically).
 * Collapsed: icon rail; hovering shows a flyout with its pages,
 * clicking an item pins/persists the flyout open until clicked again,
 * closed, or clicked outside.
 */
export default function SidebarNav({ collapsed = false }) {
    const items = useSidebarMenu();
    const { url } = usePage();
    const activeSection = items.find((item) => item.active && item.children.length)?.name ?? null;
    const [openSection, setOpenSection] = useState(activeSection);
    const [pinnedFlyout, setPinnedFlyout] = useState(null);
    const [hoverDisabledItem, setHoverDisabledItem] = useState(null);
    const navRef = useRef(null);

    // Follow navigation: open the section that owns the new page and close any open flyout.
    useEffect(() => {
        setOpenSection(activeSection);
        setPinnedFlyout(null);
        setHoverDisabledItem(null);
    }, [url, activeSection]);

    // Reset pinned flyout when sidebar collapse state toggles.
    useEffect(() => {
        setPinnedFlyout(null);
        setHoverDisabledItem(null);
    }, [collapsed]);

    // Close pinned flyout on click outside or Escape key.
    useEffect(() => {
        if (!pinnedFlyout) {
            return;
        }

        const handlePointerDown = (event) => {
            if (navRef.current && !navRef.current.contains(event.target)) {
                setPinnedFlyout(null);
                setHoverDisabledItem(null);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setPinnedFlyout(null);
                setHoverDisabledItem(null);
            }
        };

        document.addEventListener('pointerdown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [pinnedFlyout]);

    const handleItemClick = (item) => {
        const isRail = collapsed || (typeof document !== 'undefined' && document.documentElement.classList.contains('hf-sidebar-collapsed'));

        if (isRail) {
            if (pinnedFlyout === item.name) {
                setPinnedFlyout(null);
                setHoverDisabledItem(item.name);
            } else {
                setPinnedFlyout(item.name);
                setHoverDisabledItem(null);
            }
        } else {
            const isOpen = openSection === item.name;
            setOpenSection(isOpen ? null : item.name);
        }
    };

    return (
        <nav ref={navRef} className="hf-nav" aria-label="Main">
            {items.map((item) => {
                const hasChildren = item.children.length > 0;
                const isOpen = hasChildren && openSection === item.name;
                const isFlyoutOpen = hasChildren && pinnedFlyout === item.name;
                const isHoverDisabled = hoverDisabledItem === item.name;

                // Fixed-size slot so labels never shift while an icon loads.
                const icon = (
                    <span className="hf-nav-icon" aria-hidden="true">
                        {item.icon && <Icon icon={item.icon} />}
                    </span>
                );

                return (
                    <div
                        key={item.name}
                        className={cx('hf-nav-item', {
                            'is-active': item.active,
                            'is-open': isOpen,
                            'is-flyout-open': isFlyoutOpen,
                            'is-hover-disabled': isHoverDisabled,
                        })}
                        onMouseLeave={() => {
                            if (hoverDisabledItem === item.name) {
                                setHoverDisabledItem(null);
                            }
                        }}
                    >
                        {hasChildren ? (
                            <button
                                type="button"
                                className="hf-nav-link"
                                aria-expanded={isOpen || isFlyoutOpen}
                                onClick={() => handleItemClick(item)}
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
                                        onClick={() => setPinnedFlyout(null)}
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

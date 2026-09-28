import React, { useEffect, useRef, useState } from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { InertiaLink, usePage } from '@/util/Inertia';
import useSidebarMenu from './useSidebarMenu';
import SidebarGlyph from './SidebarGlyph';

/**
 * Nested sub-menu section (third level) inside an expanded accordion.
 * Starts open when it holds the current page.
 */
function SubnavGroup({ group }) {
    const [isOpen, setIsOpen] = useState(group.active);

    useEffect(() => {
        if (group.active) {
            setIsOpen(true);
        }
    }, [group.active]);

    return (
        <div className={cx('hf-subnav-group', { 'is-open': isOpen, 'is-active': group.active })}>
            <button type="button" className="hf-subnav-link hf-subnav-toggle" aria-expanded={isOpen} onClick={() => setIsOpen((open) => !open)}>
                <span>{group.title}</span>
                <SidebarGlyph name="chevronDown" size={12} className="hf-nav-caret" />
            </button>
            <div className="hf-subnav" aria-hidden={!isOpen}>
                <div className="hf-subnav-inner">
                    {group.children.map((child) => (
                        <InertiaLink key={child.name} href={child.path} className={cx('hf-subnav-link', { 'is-active': child.active })}>
                            {child.title}
                        </InertiaLink>
                    ))}
                </div>
            </div>
        </div>
    );
}

function FlyoutLink({ item, onNavigate }) {
    return (
        <InertiaLink href={item.path} className={cx('hf-flyout-link', { 'is-active': item.active })} onClick={onNavigate}>
            {item.title}
        </InertiaLink>
    );
}

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
            {items.map((item, index) => {
                const startsGroup = item.group && item.group !== items[index - 1]?.group;
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
                    <React.Fragment key={item.name}>
                        {startsGroup && (
                            <div className="hf-nav-group" role="presentation">
                                <span>{item.group}</span>
                            </div>
                        )}
                        <div
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
                                        {item.children.map((child) =>
                                            child.children.length ? (
                                                <SubnavGroup key={child.name} group={child} />
                                            ) : (
                                                <InertiaLink
                                                    key={child.name}
                                                    href={child.path}
                                                    className={cx('hf-subnav-link', { 'is-active': child.active })}
                                                >
                                                    {child.title}
                                                </InertiaLink>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Collapsed-mode flyout (hidden in expanded mode via CSS). */}
                            <div className="hf-flyout" role="menu">
                                <div className="hf-flyout-title">{item.title}</div>
                                {hasChildren ? (
                                    item.children.map((child) =>
                                        child.children.length ? (
                                            <div key={child.name} className="hf-flyout-group">
                                                <div className="hf-flyout-group-title">{child.title}</div>
                                                {child.children.map((link) => (
                                                    <FlyoutLink key={link.name} item={link} onNavigate={() => setPinnedFlyout(null)} />
                                                ))}
                                            </div>
                                        ) : (
                                            <FlyoutLink key={child.name} item={child} onNavigate={() => setPinnedFlyout(null)} />
                                        ),
                                    )
                                ) : null}
                            </div>
                        </div>
                    </React.Fragment>
                );
            })}
        </nav>
    );
}

import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
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

function FlyoutLink({ item }) {
    return (
        <InertiaLink href={item.path} className={cx('hf-flyout-link', { 'is-active': item.active })}>
            {item.title}
        </InertiaLink>
    );
}

/**
 * Collapsed-mode cascading row: shows the group title only; its links open in
 * a nested panel on hover, click, focus or ArrowRight (click acts like hover).
 */
function FlyoutGroup({ group, isOpen, onOpen }) {
    const panelRef = useRef(null);

    // Keep the nested panel inside the viewport (groups near the bottom shift up).
    useLayoutEffect(() => {
        const panel = panelRef.current;

        if (!isOpen || !panel) {
            return;
        }

        panel.style.transform = '';
        const overflow = panel.getBoundingClientRect().bottom - window.innerHeight + 12;

        if (overflow > 0) {
            panel.style.transform = `translateY(-${overflow}px)`;
        }
    }, [isOpen]);

    return (
        <div className={cx('hf-flyout-group', { 'is-open': isOpen, 'is-active': group.active })} onMouseEnter={onOpen}>
            <button
                type="button"
                className={cx('hf-flyout-link hf-flyout-group-toggle', { 'is-active': group.active })}
                aria-haspopup="menu"
                aria-expanded={isOpen}
                onClick={onOpen}
                onFocus={onOpen}
                onKeyDown={(event) => {
                    if (event.key === 'ArrowRight') {
                        event.preventDefault();
                        onOpen();
                        requestAnimationFrame(() => panelRef.current?.querySelector('a')?.focus());
                    }
                }}
            >
                <span>{group.title}</span>
                <SidebarGlyph name="chevronRight" size={12} className="hf-flyout-caret" />
            </button>
            <div
                ref={panelRef}
                className="hf-flyout hf-flyout-nested"
                role="menu"
                onKeyDown={(event) => {
                    if (event.key === 'ArrowLeft') {
                        event.preventDefault();
                        event.currentTarget.previousElementSibling?.focus();
                    }
                }}
            >
                <div className="hf-flyout-title">{group.title}</div>
                {group.children.map((link) => (
                    <FlyoutLink key={link.name} item={link} />
                ))}
            </div>
        </div>
    );
}

/**
 * Collapsed-mode flyout: first level lists the section's children; nested
 * groups cascade to the right, one at a time.
 */
function Flyout({ item }) {
    const [openGroup, setOpenGroup] = useState(null);

    return (
        <div className="hf-flyout" role="menu" onMouseLeave={() => setOpenGroup(null)}>
            <div className="hf-flyout-title">{item.title}</div>
            {item.children.map((child) =>
                child.children.length ? (
                    <FlyoutGroup
                        key={child.name}
                        group={child}
                        isOpen={openGroup === child.name}
                        onOpen={() => setOpenGroup(child.name)}
                    />
                ) : (
                    <div key={child.name} onMouseEnter={() => setOpenGroup(null)}>
                        <FlyoutLink item={child} />
                    </div>
                ),
            )}
        </div>
    );
}

/**
 * Expanded: accordion (the active section opens automatically).
 * Collapsed: icon rail; hovering (or keyboard focus) shows a flyout with its
 * pages. Clicking behaves the same as hovering, so the flyout closes as soon
 * as the pointer leaves.
 */
export default function SidebarNav({ collapsed = false }) {
    const items = useSidebarMenu();
    const { url } = usePage();
    const activeSection = items.find((item) => item.active && item.children.length)?.name ?? null;
    const [openSection, setOpenSection] = useState(activeSection);

    // Follow navigation: open the section that owns the new page.
    useEffect(() => {
        setOpenSection(activeSection);
    }, [url, activeSection]);

    const handleItemClick = (item) => {
        const isRail = collapsed || (typeof document !== 'undefined' && document.documentElement.classList.contains('hf-sidebar-collapsed'));

        if (isRail) {
            return;
        }

        setOpenSection((open) => (open === item.name ? null : item.name));
    };

    return (
        <nav className="hf-nav" aria-label="Main">
            {items.map((item, index) => {
                const startsGroup = item.group && item.group !== items[index - 1]?.group;
                const hasChildren = item.children.length > 0;
                const isOpen = hasChildren && openSection === item.name;

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
                        <div className={cx('hf-nav-item', { 'is-active': item.active, 'is-open': isOpen })}>
                            {hasChildren ? (
                                <button
                                    type="button"
                                    className="hf-nav-link"
                                    aria-expanded={isOpen}
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
                            {hasChildren ? (
                                <Flyout item={item} />
                            ) : (
                                <div className="hf-flyout" role="tooltip">
                                    <div className="hf-flyout-title">{item.title}</div>
                                </div>
                            )}
                        </div>
                    </React.Fragment>
                );
            })}
        </nav>
    );
}

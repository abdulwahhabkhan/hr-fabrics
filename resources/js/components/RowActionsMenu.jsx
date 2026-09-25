import React, { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Icon } from '@iconify/react';
import cx from 'classnames';

// Bootstrap's Popper-based Dropdown.Menu gets mispositioned inside a
// horizontally scrollable table (`.table-responsive` forces overflow-y:auto
// once overflow-x is set, and a `fixed` popper strategy loses the reference
// element's real position). Portal the menu to <body> and position it
// manually so it floats above the table instead of scrolling/misplacing.
// It opens upwards when there isn't room below the row.
const GAP = 6;

const RowActionsMenu = ({ children, label = 'More actions' }) => {
    const [open, setOpen] = useState(false);
    const [position, setPosition] = useState(null);
    const toggleRef = useRef(null);
    const menuRef = useRef(null);

    const close = useCallback(() => setOpen(false), []);

    // Measure after the menu mounts so we know its height before placing it.
    useLayoutEffect(() => {
        if (!open) {
            setPosition(null);
            return;
        }

        const toggle = toggleRef.current?.getBoundingClientRect();
        const menuHeight = menuRef.current?.offsetHeight ?? 0;

        if (!toggle) {
            return;
        }

        const roomBelow = window.innerHeight - toggle.bottom;
        const openUp = roomBelow < menuHeight + GAP * 2 && toggle.top > menuHeight + GAP * 2;

        setPosition({
            top: openUp ? toggle.top - menuHeight - GAP : toggle.bottom + GAP,
            right: window.innerWidth - toggle.right,
            up: openUp,
        });
    }, [open]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                close();
                toggleRef.current?.focus();
            }
        };

        window.addEventListener('scroll', close, true);
        window.addEventListener('resize', close);
        document.addEventListener('mousedown', close);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            window.removeEventListener('scroll', close, true);
            window.removeEventListener('resize', close);
            document.removeEventListener('mousedown', close);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open, close]);

    return (
        <>
            <button
                type="button"
                ref={toggleRef}
                className={cx('row-actions-toggle', { 'is-open': open })}
                aria-label={label}
                aria-haspopup="menu"
                aria-expanded={open}
                onMouseDown={(event) => event.stopPropagation()}
                onClick={(event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    setOpen((previous) => !previous);
                }}
            >
                <Icon icon="solar:menu-dots-bold-duotone" />
            </button>

            {open &&
                createPortal(
                    <div
                        ref={menuRef}
                        role="menu"
                        className={cx('dropdown-menu show row-actions', { 'is-up': position?.up })}
                        style={{
                            position: 'fixed',
                            top: position?.top ?? 0,
                            right: position?.right ?? 0,
                            visibility: position ? 'visible' : 'hidden',
                        }}
                        onMouseDown={(event) => event.stopPropagation()}
                        onClick={close}
                    >
                        {children}
                    </div>,
                    document.body,
                )}
        </>
    );
};

export default RowActionsMenu;

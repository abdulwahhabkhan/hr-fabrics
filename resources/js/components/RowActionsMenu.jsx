import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Icon } from '@iconify/react';

// Bootstrap's Popper-based Dropdown.Menu gets mispositioned inside a
// horizontally scrollable table (`.table-responsive` forces overflow-y:auto
// once overflow-x is set, and a `fixed` popper strategy loses the reference
// element's real position). Portal the menu to <body> and position it
// manually so it floats above the table instead of scrolling/misplacing.
const RowActionsMenu = ({ children }) => {
    const [open, setOpen] = useState(false);
    const [coords, setCoords] = useState({ top: 0, right: 0 });
    const toggleRef = useRef(null);

    const updateCoords = () => {
        const rect = toggleRef.current?.getBoundingClientRect();
        if (rect) {
            setCoords({ top: rect.bottom + 2, right: window.innerWidth - rect.right });
        }
    };

    useEffect(() => {
        if (!open) {
            return;
        }
        const close = () => setOpen(false);
        window.addEventListener('scroll', close, true);
        window.addEventListener('resize', close);
        document.addEventListener('mousedown', close);
        return () => {
            window.removeEventListener('scroll', close, true);
            window.removeEventListener('resize', close);
            document.removeEventListener('mousedown', close);
        };
    }, [open]);

    return (
        <span className={"d-inline-block"}>
            <a href={""}
               ref={toggleRef}
               className={"cursor-pointer"}
               onClick={(event) => {
                   event.preventDefault();
                   event.stopPropagation();
                   updateCoords();
                   setOpen((previous) => !previous);
               }}
            >
                <Icon icon={"solar:menu-dots-bold-duotone"} />
            </a>
            {open && createPortal(
                <div className={"dropdown-menu show row-actions"}
                     style={{ position: "fixed", top: coords.top, right: coords.right }}
                     onMouseDown={(event) => event.stopPropagation()}
                     onClick={() => setOpen(false)}
                >
                    {children}
                </div>,
                document.body
            )}
        </span>
    );
};

export default RowActionsMenu;

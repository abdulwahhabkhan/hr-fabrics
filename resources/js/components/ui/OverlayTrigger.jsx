import React, { useCallback, useEffect, useState } from 'react';
import { OverlayTrigger as BsOverlayTrigger } from 'react-bootstrap';

/**
 * Drop-in replacement for react-bootstrap's OverlayTrigger that never leaves a
 * tooltip stuck on screen when its trigger opens a Modal or SweetAlert.
 *
 * Why tooltips get stuck:
 *  - On open, the backdrop covers the trigger, so `mouseleave` never fires.
 *  - On close, the modal restores focus to the trigger, and the default
 *    `['hover', 'focus']` trigger re-opens the tooltip.
 *
 * Fix: one shared MutationObserver watches <body> for the modal/swal classes.
 * Whenever a dialog opens or closes, every tooltip hides and re-opening is
 * suppressed briefly, which swallows the restored-focus event.
 */

const DIALOG_CLASSES = ['modal-open', 'swal2-shown'];
const SUPPRESS_MS = 400;

const listeners = new Set();
let observer = null;
let lastState = '';
let suppressUntil = 0;

const readState = () =>
    DIALOG_CLASSES.map((c) => document.body.classList.contains(c)).join('|');

function ensureObserver() {
    if (observer || typeof window === 'undefined' || !document.body) {
        return;
    }

    lastState = readState();
    observer = new MutationObserver(() => {
        const state = readState();

        if (state === lastState) {
            return;
        }

        lastState = state;
        suppressUntil = Date.now() + SUPPRESS_MS;
        listeners.forEach((hide) => hide());
    });

    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
}

export default function OverlayTrigger({ show: controlledShow, onToggle, ...props }) {
    const [show, setShow] = useState(false);

    useEffect(() => {
        ensureObserver();
        const hide = () => setShow(false);
        listeners.add(hide);

        return () => {
            listeners.delete(hide);
        };
    }, []);

    const handleToggle = useCallback(
        (next) => {
            if (next && Date.now() < suppressUntil) {
                return;
            }

            setShow(next);
            onToggle?.(next);
        },
        [onToggle],
    );

    return (
        <BsOverlayTrigger
            {...props}
            show={controlledShow ?? show}
            onToggle={handleToggle}
        />
    );
}

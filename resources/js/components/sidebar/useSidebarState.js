import { useCallback, useEffect, useState } from 'react';

/**
 * Persisted collapsed/expanded state for the sidebar.
 *
 * Mirrors the Laravel React starter kit: the state lives in a `sidebar_state`
 * cookie ("true" = expanded, "false" = collapsed). A tiny inline script in
 * app.blade.php reads that cookie and sets the <html> class before first paint,
 * so a refresh never flashes the wrong width. This hook takes over after mount.
 */
export const SIDEBAR_COOKIE = 'sidebar_state';
export const COLLAPSED_CLASS = 'hf-sidebar-collapsed';
const MAX_AGE = 60 * 60 * 24 * 365;

const isCollapsedOnPage = () =>
    typeof document !== 'undefined' &&
    document.documentElement.classList.contains(COLLAPSED_CLASS);

export default function useSidebarState() {
    const [collapsed, setCollapsed] = useState(isCollapsedOnPage);

    useEffect(() => {
        document.documentElement.classList.toggle(COLLAPSED_CLASS, collapsed);
        document.cookie = `${SIDEBAR_COOKIE}=${!collapsed}; path=/; max-age=${MAX_AGE}; SameSite=Lax`;
    }, [collapsed]);

    const toggle = useCallback(() => setCollapsed((value) => !value), []);

    return { collapsed, toggle };
}

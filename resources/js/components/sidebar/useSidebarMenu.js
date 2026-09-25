import { useMemo } from 'react';
import { usePage } from '@/util/Inertia';
import menu from '@/components/top-menu/menu.jsx';

/**
 * Shared menu definition (components/top-menu/menu.jsx), filtered by the
 * user's permissions and annotated with the active item for the current URL.
 * Same rules as the legacy top menu, so both layouts always agree.
 */
export const isRouteActive = (item, currentUrl) => {
    if (!item?.path || item.path === '#' || !currentUrl) {
        return false;
    }

    const cleanUrl = currentUrl.split('?')[0].split('#')[0];
    const cleanPath = typeof item.path === 'string' ? item.path.split('?')[0].split('#')[0] : '';

    if (!cleanPath) {
        return false;
    }

    if (cleanPath === '/' || cleanPath === '/dashboard') {
        return cleanUrl === cleanPath || (cleanPath === '/dashboard' && cleanUrl === '/');
    }

    return cleanUrl === cleanPath || cleanUrl.startsWith(cleanPath + '/');
};

export default function useSidebarMenu() {
    const { url, props } = usePage();

    return useMemo(() => {
        const permissions = props.auth?.permissions ?? [];
        const allowed = (item) => item.always || permissions.includes(item.name);

        return menu.filter(allowed).map((item) => {
            const children = (item.children ?? [])
                .filter(allowed)
                .map((child) => ({ ...child, active: isRouteActive(child, url) }));

            return {
                ...item,
                children,
                active: isRouteActive(item, url) || children.some((child) => child.active),
            };
        });
        // `url` re-evaluates active state after every Inertia visit.
    }, [url, props.auth?.permissions]);
}

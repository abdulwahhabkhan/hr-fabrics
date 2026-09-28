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

        // Top-level items and links need their own permission; nested groups show when any child is allowed.
        const resolve = (items, nested = false) =>
            items.flatMap((item) => {
                const isGroup = nested && item.children;

                if (!isGroup && !allowed(item)) {
                    return [];
                }

                const children = resolve(item.children ?? [], true);

                if (isGroup && !children.length) {
                    return [];
                }

                return [
                    {
                        ...item,
                        children,
                        active: isRouteActive(item, url) || children.some((child) => child.active),
                    },
                ];
            });

        return resolve(menu);
        // `url` re-evaluates active state after every Inertia visit.
    }, [url, props.auth?.permissions]);
}

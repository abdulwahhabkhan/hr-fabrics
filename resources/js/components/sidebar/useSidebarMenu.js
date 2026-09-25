import { useMemo } from 'react';
import { usePage } from '@/util/Inertia';
import menu from '@/components/top-menu/menu.jsx';

/**
 * Shared menu definition (components/top-menu/menu.jsx), filtered by the
 * user's permissions and annotated with the active item for the current URL.
 * Same rules as the legacy top menu, so both layouts always agree.
 */
const ROUTE_SUFFIX = /\.(index|show|create|edit|store|update|destroy)$/;

export const isRouteActive = (name) => {
    if (!name || typeof route !== 'function') {
        return false;
    }

    return Boolean(route().current(name.replace(ROUTE_SUFFIX, '') + '*'));
};

export default function useSidebarMenu() {
    const { url, props } = usePage();
    const permissions = props.auth?.permissions ?? [];

    return useMemo(() => {
        const allowed = (item) => item.always || permissions.includes(item.name);

        return menu.filter(allowed).map((item) => {
            const children = (item.children ?? [])
                .filter(allowed)
                .map((child) => ({ ...child, active: isRouteActive(child.name) }));

            return {
                ...item,
                children,
                active: isRouteActive(item.name) || children.some((child) => child.active),
            };
        });
        // `url` re-evaluates active state after every Inertia visit.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [url, permissions]);
}

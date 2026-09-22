import React, { useState } from 'react';
import { InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';

const MOBILE_BREAKPOINT = 768;

const matchesCurrentRoute = (name) => {
    const base = name.replace(/\.(index|show|create|edit|store|update|destroy)$/, "");

    return route().current(base + "*");
};

const TopMenuNavList = ({ data, expand, active, topLevel = true }) => {
    const [childActive, setChildActive] = useState(-1);
    const icon = data.icon && <div className={"menu-icon"}><Icon icon={data.icon}></Icon></div>;
    const img = data.img && <div className="menu-icon-img"><img src={data.img} alt="" /></div>;
    const caret = (data.children && !data.badge) &&
        <div className={"menu-caret"}><Icon className={"icon"} icon={"solar:alt-arrow-down-bold"}></Icon></div>;
    const label = data.label && <span className="menu-label">{data.label}</span>;
    const badge = data.badge && <span className="menu-badge">{data.badge}</span>;
    const title = data.title && <span className="menu-text">{data.title} {label}</span>;
    const match = matchesCurrentRoute(data.name);
    const { auth: { permissions } } = usePage().props;

    const hasPermission = (name) => {
        return _.indexOf(permissions, name) == -1 ? false : true;
    };

    const handleClick = (e) => {
        e.preventDefault();

        // Top-level submenus open on hover on desktop (see _app-top-menu.scss);
        // click-to-toggle is only needed there on mobile where hover doesn't apply.
        // Nested submenus have no hover behaviour, so they always toggle on click.
        if (topLevel && window.innerWidth >= MOBILE_BREAKPOINT) {
            return;
        }

        if (expand) {
            expand(e);
        }
    };

    const handleChildExpand = (e, i) => {
        e.preventDefault();
        setChildActive((current) => (current === i ? -1 : i));
    };

    if (!data.always && !hasPermission(data.name)) {
        return (
            <></>
        );
    }

    return (
        <div className={"menu-item " + (match ? "active " : "") + (data.children ? "has-sub " : "") + (active ? "show " : "")}>
            {data.children ? (
                <InertiaLink className="menu-link" href={data.path}
                             onClick={handleClick}>{img} {icon} {title} {caret} {badge}</InertiaLink>
            ) : (
                <InertiaLink className="menu-link"
                             href={data.path}>{img} {icon} {title} {caret} {badge}</InertiaLink>
            )}
            {data.children && (
                <div className="menu-submenu">
                    {data.children.map((submenu, i) => (
                        <TopMenuNavList
                            data={submenu}
                            key={i}
                            expand={(e) => handleChildExpand(e, i)}
                            active={i === childActive}
                            topLevel={false}
                        />
                    ))}
                </div>
            )}
        </div>
    );
};

export default TopMenuNavList;

import React, { useEffect, useState } from "react";
import { usePage } from "@/util/Inertia";
import TopMenuNavList from "./top-menu-nav-list.jsx";
import menus from "./menu.jsx";

function TopMenuNav() {
    const [active, setActive] = useState(-1);
    const { url } = usePage();

    // Close whichever top-level submenu was open (mobile accordion) after navigating.
    useEffect(() => {
        setActive(-1);
    }, [url]);

    const handleExpand = (e, i) => {
        setActive((current) => (current === i ? -1 : i));
    };

    return (
        <div className="menu">
            {menus.map((menu, i) => (
                <TopMenuNavList
                    data={menu}
                    key={i}
                    expand={(e) => handleExpand(e, i)}
                    active={i === active}
                />
            ))}
        </div>
    );
}

export default TopMenuNav;

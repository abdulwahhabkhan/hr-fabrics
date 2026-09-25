import React, { useState } from 'react';
import Header from '@/components/header/header';
import TopMenu from '@/components/top-menu/top-menu';
import { AppName, PageSettings } from '@/config/page-settings';
import FlashMessage from '@/components/FlashMessage';

export default function AppHeaderLayout({ header, children }) {
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const [state, setState] = useState({
        pageMobileTopMenu: false,
        toggleMobileTopMenu: () => toggleMobileTopMenu()
    });
    const appName = AppName;

    const toggleMobileTopMenu = () => {
        setState(state => ({
            ...state,
            pageMobileTopMenu: !state.pageMobileTopMenu
        }));
    };
    return (
        <PageSettings.Provider value={state}>
            <div
                className="app app-header-fixed app-sidebar-fixed app-without-sidebar app-with-top-menu app-gradient-enabled">
                <Header appName={appName} />
                <TopMenu />
                <main id={"content"} className={"app-content animate-fade-up"}>
                    {header && <>{header}</>}
                    {children}
                </main>
                <div id="footer" className="app-footer m-0 text-sm-start text-center animate-fade-up">
                    &copy; 2022 {appName}, All Rights Reserved
                    <div className="float-md-end text-center mt-md-0 mt-2">
                        Developed By:{" "}
                        <a href={"https://sudotech.co.uk"} target={"_blank"}>
                            SUDOTECH
                        </a>
                    </div>
                </div>
            </div>
            <FlashMessage />
        </PageSettings.Provider>
    );
}

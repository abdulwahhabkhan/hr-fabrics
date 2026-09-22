import React from 'react';
import TopMenuNav from './top-menu-nav.jsx';
import { PageSettings } from '@/config/page-settings.jsx';

class TopMenu extends React.Component {
    render() {
        return (
            <PageSettings.Consumer>
                {({ pageMobileTopMenu }) => (
                    <div id="top-menu" className={"animate-fade-up app-top-menu " + (pageMobileTopMenu ? "d-block " : "")}>
                        <TopMenuNav />
                    </div>
                )}
            </PageSettings.Consumer>
        );
    }
}

export default TopMenu;

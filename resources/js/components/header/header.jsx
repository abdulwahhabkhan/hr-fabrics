import React from 'react';
import DropdownProfile from './dropdown/profile.jsx';
import { AppSubName, PageSettings } from '@/config/page-settings.jsx';
import logo from '@/img/logo-2.png';
import Navbar from 'react-bootstrap/Navbar';

class Header extends React.Component {
    constructor(props) {
        super(props);
        this.toggleMegaMenu = this.toggleMegaMenu.bind(this);
        this.state = { collapseMegaMenu: false };
    }

    toggleMegaMenu() {
        this.setState({ collapseMegaMenu: !this.state.collapseMegaMenu });
    }

    render() {
        return (
            <PageSettings.Consumer>
                {({ toggleMobileTopMenu }) => (
                    <div id="header" className="app-header animate-fade-up">
                        <div className="navbar-header">
                            <Navbar.Brand href="/" className="fw-500 text-theme py-1">
                                <img src={logo} alt={this.props.appName} className={"logo me-1"} />
                                <span className="brand-text">
                                    <span className="brand-name">{this.props.appName}</span>
                                    <small className="brand-sub">{AppSubName}</small>
                                </span>
                            </Navbar.Brand>
                            <button type="button" className="navbar-mobile-toggler" onClick={toggleMobileTopMenu}>
                                <span className="icon-bar"></span>
                                <span className="icon-bar"></span>
                                <span className="icon-bar"></span>
                            </button>
                        </div>

                        <div className="navbar-nav">
                            {/*<SearchForm />*/}
                            {/*<DropdownModules />*/}
                            {/*<DropdownNotification/>*/}

                            <DropdownProfile />
                        </div>
                    </div>
                )}
            </PageSettings.Consumer>
        );
    }
}

export default Header;

import React from 'react';
import {Dropdown} from 'react-bootstrap';
import { Icon } from "@iconify/react";

const DropdownModules = ({props}) => {

    return (
        <Dropdown className="navbar-item" as="div">
            <Dropdown.Toggle as="a" className="navbar-link">
                <Icon icon={"solar:widget-6-bold-duotone"}/> &nbsp;
                <span className="">Modules</span>
            </Dropdown.Toggle>
            <Dropdown.Menu className="dropdown-menu dropdown-menu-right" as="ul">
                <a className={'dropdown-item'} href={'/dashboard'}>Fresh</a>

                <div className="dropdown-divider"/>
                <a className={'dropdown-item'} href={'/exceptions'}>Exceptions</a>
            </Dropdown.Menu>
        </Dropdown>
    );

};

export default DropdownModules;

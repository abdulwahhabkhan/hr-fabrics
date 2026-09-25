import React from "react";
import { Dropdown } from "react-bootstrap";
import { InertiaLink, usePage } from "@/util/Inertia";
import { logout } from "@/routes";
import profile from "@/routes/profile";

const DropdownProfile = ({ props }) => {

    const { auth } = usePage().props;

    return (
        <Dropdown /*isOpen={this.state.dropdownOpen} toggle={this.toggle}*/ className="navbar-item navbar-user"
                                                                            as="div">
            <Dropdown.Toggle as="a" className="navbar-link">
                <img src={auth.avatar} alt="" />
                {/*<span className="d-none d-md-inline">Adam Schwartz</span>*/}
                <span className="">{auth.user.name}</span>
            </Dropdown.Toggle>
            <Dropdown.Menu className="dropdown-menu dropdown-menu-right" as="ul">
                <Dropdown.Item href={profile.index().url}>Edit Profile</Dropdown.Item>
                {/*<Dropdown.Item><span className="badge badge-danger float-end">2</span> Inbox</Dropdown.Item>
                <Dropdown.Item>Calendar</Dropdown.Item>
                <Dropdown.Item>Setting</Dropdown.Item>
                <div className="dropdown-divider"></div>*/}
                <div className="dropdown-divider"></div>
                <InertiaLink className={"dropdown-item"} href={logout()} method={"post"}>Log Out</InertiaLink>
            </Dropdown.Menu>
        </Dropdown>
    );

};

export default DropdownProfile;

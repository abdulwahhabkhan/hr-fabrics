import React from 'react';
import { Dropdown } from 'react-bootstrap';
import { InertiaLink, usePage } from '@/util/Inertia';
import SidebarGlyph from './SidebarGlyph';

const initials = (name = '') =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');

/** Initials avatar drawn in CSS (brand gold foil + subtle weave), no image needed. */
function Avatar({ user }) {
    return (
        <span className="hf-avatar" aria-hidden="true">
            <span className="hf-avatar-initials">{initials(user.name) || '?'}</span>
        </span>
    );
}

/** Account button at the bottom of the sidebar; menu opens to the right. */
export default function SidebarUser() {
    const { auth } = usePage().props;
    const { user } = auth;

    return (
        <Dropdown drop="end" className="hf-user">
            <Dropdown.Toggle as="button" type="button" className="hf-user-toggle" bsPrefix="hf-user-toggle">
                <Avatar user={user} />
                <span className="hf-user-meta">
                    <span className="hf-user-name">{user.name}</span>
                    {user.email && <span className="hf-user-email">{user.email}</span>}
                </span>
                <SidebarGlyph name="chevronsUpDown" className="hf-user-caret" />
            </Dropdown.Toggle>

            <Dropdown.Menu className="hf-user-menu" popperConfig={{ strategy: 'fixed' }}>
                <div className="hf-user-menu-head">
                    <Avatar user={user} />
                    <span className="hf-user-meta">
                        <span className="hf-user-name">{user.name}</span>
                        {user.email && <span className="hf-user-email">{user.email}</span>}
                    </span>
                </div>
                <Dropdown.Divider />
                <Dropdown.Item as={InertiaLink} href={route('profile.index')}>
                    <SidebarGlyph name="user" /> My profile
                </Dropdown.Item>
                <Dropdown.Divider />
                <InertiaLink
                    href={route('logout')}
                    method="post"
                    as="button"
                    type="button"
                    className="dropdown-item hf-user-logout"
                >
                    <SidebarGlyph name="logout" /> Log out
                </InertiaLink>
            </Dropdown.Menu>
        </Dropdown>
    );
}

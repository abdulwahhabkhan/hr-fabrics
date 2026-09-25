import React from 'react';
import cx from 'classnames';
import SidebarBrand from './SidebarBrand';
import SidebarNav from './SidebarNav';
import SidebarUser from './SidebarUser';
import SidebarGlyph from './SidebarGlyph';

export default function AppSidebar({ collapsed, onToggle, mobileOpen, onMobileClose }) {
    return (
        <>
            <aside id="sidebar" className={cx('hf-sidebar', { 'is-mobile-open': mobileOpen })}>
                <div className="hf-sidebar-head">
                    <SidebarBrand tone="light" />
                    <button
                        type="button"
                        className="hf-sidebar-close"
                        onClick={onMobileClose}
                        aria-label="Close menu"
                    >
                        <SidebarGlyph name="close" size={18} />
                    </button>
                </div>

                <button
                    type="button"
                    className="hf-sidebar-toggle"
                    onClick={onToggle}
                    aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                    title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                >
                    <SidebarGlyph name={collapsed ? 'chevronRight' : 'chevronLeft'} size={14} />
                </button>

                <div className="hf-sidebar-label">Workspace</div>
                <div className="hf-sidebar-body">
                    <SidebarNav />
                </div>

                <div className="hf-sidebar-foot">
                    <SidebarUser />
                </div>
            </aside>

            {mobileOpen && <div className="hf-sidebar-backdrop" onClick={onMobileClose} />}
        </>
    );
}

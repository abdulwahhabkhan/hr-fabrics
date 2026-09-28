import React from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import profile from '@/routes/profile';

const navItems = [
    { title: 'Profile', href: profile.index(), icon: 'solar:user-bold-duotone' },
    { title: 'Security', href: profile.security(), icon: 'solar:shield-keyhole-bold-duotone' },
];

/**
 * Settings shell (Laravel React starter kit style): side nav on the left,
 * the active settings page on the right.
 */
export default function SettingLayout({ children }) {
    const { url } = usePage();
    const currentPath = url.split('?')[0];

    return (
        <>
            <PageHeader title="Settings" description="Manage your profile and account settings" />
            <PageContent>
                <div className="hf-settings">
                    <nav className="hf-settings__nav" aria-label="Settings">
                        {navItems.map((item) => (
                            <InertiaLink
                                key={item.title}
                                href={item.href}
                                className={cx('hf-settings__link', { 'is-active': currentPath === item.href.url })}
                                aria-current={currentPath === item.href.url ? 'page' : undefined}
                            >
                                <Icon icon={item.icon} />
                                {item.title}
                            </InertiaLink>
                        ))}
                    </nav>
                    <div className="hf-settings__body">{children}</div>
                </div>
            </PageContent>
        </>
    );
}

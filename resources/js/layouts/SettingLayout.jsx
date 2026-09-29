import React from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import profile from '@/routes/profile';

const navItems = [
    { title: 'Profile', description: 'Name, email & role', href: profile.index(), icon: 'solar:user-bold-duotone' },
    {
        title: 'Security',
        description: 'Password, 2FA & passkeys',
        href: profile.security(),
        icon: 'solar:shield-keyhole-bold-duotone',
    },
];

/**
 * Settings shell: horizontal tab bar across the top, the active settings page below.
 */
export default function SettingLayout({ children }) {
    const { url } = usePage();
    const currentPath = url.split('?')[0];

    return (
        <>
            <PageHeader title="Settings" description="Manage your profile and account settings" />
            <PageContent>
                <div className="hf-settings">
                    <nav className="hf-settings__tabs" aria-label="Settings">
                        {navItems.map((item) => {
                            const isActive = currentPath === item.href.url;

                            return (
                                <InertiaLink
                                    key={item.title}
                                    href={item.href}
                                    className={cx('hf-settings__tab', { 'is-active': isActive })}
                                    aria-current={isActive ? 'page' : undefined}
                                >
                                    <span className="hf-settings__tab-icon">
                                        <Icon icon={item.icon} />
                                    </span>
                                    <span className="hf-settings__tab-text">
                                        <span className="hf-settings__tab-title">{item.title}</span>
                                        <span className="hf-settings__tab-desc">{item.description}</span>
                                    </span>
                                </InertiaLink>
                            );
                        })}
                    </nav>
                    <div className="hf-settings__body">{children}</div>
                </div>
            </PageContent>
        </>
    );
}

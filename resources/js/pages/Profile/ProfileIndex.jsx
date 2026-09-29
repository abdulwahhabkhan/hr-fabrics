import React from 'react';
import { Head, usePage } from '@/util/Inertia';
import { Form } from 'react-bootstrap';
import { Icon } from '@iconify/react';
import { FormField } from '@/components/form/FormSection';
import HeadingSmall from '@/components/settings/HeadingSmall';

/** First letters of the first two words of a name, e.g. "Abdul Wahhab" → "AW". */
const initialsOf = (name = '') =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0].toUpperCase())
        .join('');

const ProfileIndex = () => {
    const { user, role } = usePage().props;

    return (
        <>
            <Head title="Profile settings" />

            <section className="hf-settings-identity">
                <div className="hf-settings-identity__avatar" aria-hidden="true">
                    {initialsOf(user.name)}
                </div>
                <div className="hf-settings-identity__meta">
                    <div className="hf-settings-identity__name">{user.name}</div>
                    <div className="hf-settings-identity__email">
                        <Icon icon="solar:letter-bold-duotone" />
                        {user.email}
                    </div>
                </div>
                {role?.name && (
                    <span className="hf-pill tone-navy">
                        <Icon icon="solar:shield-user-bold-duotone" />
                        {role.name}
                    </span>
                )}
            </section>

            <section className="hf-settings-section">
                <HeadingSmall
                    icon="solar:user-id-bold-duotone"
                    title="Profile information"
                    description="Your name, email address and role."
                />

                <div className="hf-settings-fields">
                    <div className="hf-settings-grid">
                        <FormField label="Name" htmlFor="name">
                            <Form.Control id="name" value={user.name} readOnly disabled />
                        </FormField>
                        <FormField label="Email address" htmlFor="email">
                            <Form.Control id="email" type="email" value={user.email} readOnly disabled />
                        </FormField>
                    </div>
                    <FormField label="Role" htmlFor="role">
                        <Form.Control id="role" value={role?.name ?? '—'} readOnly disabled />
                    </FormField>

                    <div className="hf-settings-note">
                        <Icon icon="solar:info-circle-bold-duotone" />
                        Contact an administrator to change your name, email or role.
                    </div>
                </div>
            </section>
        </>
    );
};

export default ProfileIndex;

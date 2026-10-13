import React from 'react';
import { Head, useForm, usePage } from '@/util/Inertia';
import { Form } from 'react-bootstrap';
import { Icon } from '@iconify/react';
import { FormField } from '@/components/form/FormSection';
import HeadingSmall from '@/components/settings/HeadingSmall';
import LoadingButton from '@/components/LoadingButton';
import profile from '@/routes/profile';

/** First letters of the first two words of a name, e.g. "Abdul Wahhab" → "AW". */
const initialsOf = (name = '') =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0].toUpperCase())
        .join('');

const Profile = () => {
    const { auth, role } = usePage().props;
    const user = auth.user;
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: user.name,
        email: user.email,
    });

    const submit = (e) => {
        e.preventDefault();

        patch(profile.update().url, { preserveScroll: true });
    };

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
                    description="Update your name and email address."
                />

                <form onSubmit={submit} className="hf-settings-fields">
                    <div className="hf-settings-grid">
                        <FormField label="Name" htmlFor="name">
                            <Form.Control
                                id="name"
                                value={data.name}
                                autoComplete="name"
                                isInvalid={Boolean(errors.name)}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            {errors.name && <div className="invalid-feedback d-block">{errors.name}</div>}
                        </FormField>
                        <FormField label="Email address" htmlFor="email">
                            <Form.Control
                                id="email"
                                type="email"
                                value={data.email}
                                autoComplete="username"
                                isInvalid={Boolean(errors.email)}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                            />
                            {errors.email && <div className="invalid-feedback d-block">{errors.email}</div>}
                        </FormField>
                    </div>
                    <FormField label="Role" htmlFor="role">
                        <Form.Control id="role" value={role?.name ?? '—'} readOnly disabled />
                    </FormField>

                    <div className="hf-settings-note">
                        <Icon icon="solar:info-circle-bold-duotone" />
                        Contact an administrator to change your role.
                    </div>

                    <div className="hf-settings-actions">
                        <LoadingButton type="submit" variant="theme" processing={processing}>
                            Save
                        </LoadingButton>
                        {recentlySuccessful && (
                            <span className="hf-settings-saved">
                                <Icon icon="solar:check-circle-bold" />
                                Saved
                            </span>
                        )}
                    </div>
                </form>
            </section>
        </>
    );
};

export default Profile;

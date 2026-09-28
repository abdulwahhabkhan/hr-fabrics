import React from 'react';
import { Head, usePage } from '@/util/Inertia';
import { Form } from 'react-bootstrap';
import { FormField } from '@/components/form/FormSection';
import HeadingSmall from '@/components/settings/HeadingSmall';

const ProfileIndex = () => {
    const { user, role } = usePage().props;

    return (
        <>
            <Head title="Profile settings" />

            <section className="hf-settings-section">
                <HeadingSmall
                    title="Profile information"
                    description="Your name, email address and role. Contact an administrator to change them."
                />

                <div className="hf-settings-fields">
                    <FormField label="Name" htmlFor="name">
                        <Form.Control id="name" value={user.name} readOnly disabled />
                    </FormField>
                    <FormField label="Email address" htmlFor="email">
                        <Form.Control id="email" type="email" value={user.email} readOnly disabled />
                    </FormField>
                    <FormField label="Role" htmlFor="role">
                        <Form.Control id="role" value={role?.name ?? '—'} readOnly disabled />
                    </FormField>
                </div>
            </section>
        </>
    );
};

export default ProfileIndex;

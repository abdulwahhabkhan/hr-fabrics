import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Form } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import ValidationErrors from '@/components/ValidationErrors';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import PermissionMatrix from '@/components/settings/PermissionMatrix';
import roles from '@/routes/settings/roles';

const RoleForm = () => {
    const { role, permissions, rolePermission, errors: serverSideError } = usePage().props;
    const isEdit = Boolean(role);
    const title = isEdit ? 'Edit Role' : 'New Role';
    const [processing, setProcessing] = useState(false);
    const [checked, setChecked] = useState(rolePermission);
    const [permissionsDirty, setPermissionsDirty] = useState(false);
    const {
        register,
        handleSubmit,
        formState: { errors, isDirty },
    } = useForm({ defaultValues: { name: role?.name ?? '', description: role?.description ?? '' } });

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        const payload = { ...data, permissions: checked };
        if (isEdit) {
            Inertia.put(roles.update(role.id), payload, options);
        } else {
            Inertia.post(roles.store(), payload, options);
        }
    };
    const onPermissionsChange = (ids) => {
        setChecked(ids);
        setPermissionsDirty(true);
    };

    const hasChanges = isDirty || permissionsDirty;

    return (
        <>
            <Head title={title} />
            <PageHeader
                title={title}
                description={isEdit ? role.name : 'Define a role and what it can access'}
                buttons={<BackButton href={roles.index()} label="Roles" />}
            />

            <PageContent>
                <ValidationErrors errors={serverSideError} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:shield-user-bold-duotone"
                                title="Role details"
                                description="A short, recognisable name. Users are assigned this role from their user profile."
                            >
                                <div className="row g-3">
                                    <FormField label="Name" htmlFor="role-name" required className="col-md-6">
                                        <Form.Control
                                            id="role-name"
                                            {...register('name', { required: true, maxLength: 50 })}
                                            isInvalid={Boolean(errors.name)}
                                            placeholder="e.g. Sales Manager"
                                            autoFocus={!isEdit}
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback d-block">
                                                {errors.name.type === 'maxLength' ? 'Name may not exceed 50 characters.' : 'Please enter a role name.'}
                                            </div>
                                        )}
                                    </FormField>
                                    <FormField label="Description" htmlFor="role-description" className="col-md-6">
                                        <Form.Control
                                            id="role-description"
                                            {...register('description')}
                                            placeholder="What is this role for?"
                                        />
                                    </FormField>
                                </div>
                            </FormSection>

                            <FormSection
                                icon="solar:key-minimalistic-square-bold-duotone"
                                title="Permissions"
                                description="Choose what users with this role can do. Tick a module or section to grant everything inside it, or toggle individual actions."
                            >
                                <PermissionMatrix permissions={permissions} value={checked} onChange={onPermissionsChange} />
                            </FormSection>
                        </PanelBody>
                        <FormActions
                            hint={
                                hasChanges ? (
                                    <span className="hf-perms__unsaved">Unsaved changes · {checked.length} permissions selected</span>
                                ) : (
                                    <>
                                        <span className="hf-required">*</span> Required fields
                                    </>
                                )
                            }
                        >
                            <BackButton href={roles.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                {isEdit ? 'Save changes' : 'Create role'}
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default RoleForm;

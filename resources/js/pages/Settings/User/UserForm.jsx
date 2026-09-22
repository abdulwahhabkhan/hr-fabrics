import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelFooter, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';


const UserForm = () => {
    const { user, roles, errors: serverErrors } = usePage().props;
    const title = user ? "Edit User" : "Add User";
    const [processing, setProcessing] = useState(false);

    const defaultValues = user;
    const { register, handleSubmit, setError, formState: { errors } } = useForm({ defaultValues: defaultValues });
    const sendRequest = async (data) => {
        setProcessing(true);
        const options = {
            onFinish: () => {
                setProcessing(false);
            }
        };
        if (user)
            Inertia.put(route("settings.users.update", user.id), { ...data }, options);
        else
            Inertia.post(route("settings.users.store"), { ...data }, options);
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);

    return (
        <>
            <Head title="User Update" />
            <PageHeader title="User Update" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} buttons={(
                        <>
                            <InertiaLink href={route("settings.users.index")} className="btn btn-xs  btn-primary">
                                <Icon icon={"solar:reply-bold-duotone"} /> User List
                            </InertiaLink>
                        </>
                    )} />
                    <PanelBody>
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <ErrorPanel errors={serverErrors} />
                            <Row>
                                <Col>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Role:</Form.Label>
                                        <Form.Select
                                            {...register("role_id", { required: true })}
                                            isInvalid={errors.role_id}>
                                            <option value={""}>Select Role</option>
                                            {
                                                roles && roles.map(({ id, name }, index) => {
                                                    // const selected = user && id == user.role_id ? 'selectd' : ''
                                                    return (
                                                        <option key={id} value={id}>{name}</option>
                                                    );
                                                })
                                            }
                                        </Form.Select>
                                    </Form.Group>

                                </Col>
                            </Row>
                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Name:</Form.Label>
                                        <Form.Control
                                            {...register("name", { required: true })}
                                            isInvalid={errors.name}
                                            placeholder={"user name"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Email:</Form.Label>
                                        <Form.Control
                                            {...register("email")}
                                            isInvalid={errors.email}
                                            placeholder={"email"} />
                                    </Form.Group>
                                </Col>
                            </Row>
                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Password:</Form.Label>
                                        <Form.Control
                                            {...register("password")}
                                            isInvalid={errors.password}
                                            placeholder={"password"} type="password" />
                                    </Form.Group>
                                </Col>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Confirm Password:</Form.Label>
                                        <Form.Control
                                            {...register("password_confirmation")}
                                            isInvalid={errors.password_confirmation}
                                            placeholder={"password confirmation"} type="password" />
                                    </Form.Group>
                                </Col>
                            </Row>

                        </form>

                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <InertiaLink href={route("settings.users.index")} className={"btn btn-white"}>
                            <Icon icon={"solar:reply-bold-duotone"} />
                        </InertiaLink>
                        &nbsp;
                        <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default UserForm;

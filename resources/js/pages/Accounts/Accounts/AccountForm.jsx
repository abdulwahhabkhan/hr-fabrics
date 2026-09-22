import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelFooter, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import { useAccountTypes } from '@/util/util';
import BackButton from '@/components/button/back';

const AccountForm = () => {
    const { account, expense_accounts, errors: serverErrors } = usePage().props;
    const accountTypes = useAccountTypes();
    const title = account ? "Edit Account" : "Add Account";
    const [processing, setProcessing] = useState(false);

    const { register, handleSubmit, setError, watch, formState: { errors } } = useForm({ defaultValues: account });
    const options = {
        onFinish: () => {
            setProcessing(false);
        }
    };
    const type = watch("type");

    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        if (account)
            Inertia.put(route("accounts.accounts.update", account["id"]), post_data, options);
        else
            Inertia.post(route("accounts.accounts.store"), post_data, options);
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);
    return (
        <>
            <Head title="Account Update" />
            <PageHeader title="Account Update" buttons={<>
                <BackButton href={route("accounts.accounts.index")}  />
            </>} />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>

                                <Col sm={2}>
                                    <Form.Label>Account Type:</Form.Label>
                                    <Form.Select
                                        {...register("type", { required: true })}
                                        isInvalid={errors.type}
                                        >
                                        <option value={""}>Select Type</option>
                                        {
                                            accountTypes && accountTypes.map(function(val, index) {
                                                return (
                                                    <option key={index}>{val}</option>
                                                );
                                            })
                                        }
                                    </Form.Select>
                                </Col>
                                <Col sm={5}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Account Name:</Form.Label>
                                        <Form.Control
                                            {...register("name", { required: true })}
                                            isInvalid={errors.name}
                                            placeholder={"name"} />
                                    </Form.Group>
                                </Col>
                                {
                                    type == "agent" && (
                                        <Col sm={5}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Expense Account:</Form.Label>
                                                <Form.Select
                                                    {...register("expense_account", { required: true })}
                                                    isInvalid={errors.expense_account}
                                                    >
                                                    <option value={""}>Expense Account</option>
                                                    {
                                                        expense_accounts && expense_accounts.map(function({
                                                                                                              id,
                                                                                                              name,
                                                                                                              type
                                                                                                          }, index) {
                                                            return (
                                                                <option value={id} key={index}>{name}</option>
                                                            );
                                                        })
                                                    }
                                                </Form.Select>
                                            </Form.Group>
                                        </Col>
                                    )
                                }

                            </Row>

                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Phone:</Form.Label>
                                        <Form.Control
                                            {...register("phone", { email: true })}
                                            isInvalid={errors.phone}
                                            placeholder={"phone"} />
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
                            <Form.Group className="mb-3">
                                <Form.Label> Address:</Form.Label>
                                <Form.Control
                                    {...register("address.address")}
                                    placeholder={"address"} />
                            </Form.Group>
                            <Row>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label> City:</Form.Label>
                                        <Form.Control
                                            {...register("address.city")}
                                            placeholder={"city"} />
                                    </Form.Group>
                                </Col>

                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label> Region:</Form.Label>
                                        <Form.Control
                                            {...register("address.region")}
                                            placeholder={"region"} />
                                    </Form.Group>
                                </Col>

                            </Row>
                        </form>

                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <BackButton href={route("accounts.accounts.index")} size={'md'} />
                        <LoadingButton variant="theme" processing={processing} onClick={handleSubmit(sendRequest)}>
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default AccountForm;

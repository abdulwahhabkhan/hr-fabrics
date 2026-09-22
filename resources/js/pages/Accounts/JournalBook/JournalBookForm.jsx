import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelFooter, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import Select from 'react-select';
import BackButton from '@/components/button/back';

const JournalBookForm = () => {
    const { voucher, errors: serverErrors, accounts } = usePage().props;
    const title = voucher ? "Edit Journal Voucher" : "Create Journal Voucher";
    const [processing, setProcessing] = useState(false);

    const {
        register,
        handleSubmit,
        setError,
        control,
        watch,
        formState: { errors }
    } = useForm({ defaultValues: voucher });
    const options = {
        onFinish: () => {
            setProcessing(false);
        }
    };
    const type = watch("type");

    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        if (voucher)
            Inertia.put(route("accounts.journal-voucher.update", voucher["id"]), post_data, options);
        else
            Inertia.post(route("accounts.journal-voucher.store"), post_data, options);
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);
    return (
        <>
            <Head title="Journal Voucher Update" />
            <PageHeader title="Journal Voucher Update" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} buttons={(
                        <>
                            <BackButton href={route("accounts.journal-voucher.index")} size="xs" />
                        </>
                    )} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>
                                <Col sm={12}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Account:</Form.Label>
                                        {
                                            !voucher && (
                                                <Controller
                                                    render={({ field }) => (
                                                        <Select
                                                            theme={theme => ({
                                                                ...theme,
                                                                borderRadius: 0,
                                                                colors: {
                                                                    ...theme.colors,
                                                                    primary25: "#c27f69",
                                                                    primary: "#265c99b3"
                                                                }
                                                            })}
                                                            {...field}
                                                            options={accounts}
                                                            getOptionValue={option => option["id"]}
                                                            getOptionLabel={option => option["name"]}
                                                            isClearable
                                                        />
                                                    )}
                                                    inputProps={{ ref: register, name: name, placeholder: "Select date" }}
                                                    control={control}
                                                    name={"account"}
                                                />
                                            )
                                        }
                                        {
                                            voucher && (
                                                <div>{voucher.account.name}</div>
                                            )
                                        }

                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Description:</Form.Label>
                                        <Form.Control
                                            {...register("desc", { required: true })}
                                            isInvalid={errors.desc}
                                            placeholder={"desc"} />
                                    </Form.Group>
                                </Col>

                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Debit:</Form.Label>
                                        <Form.Control
                                            {...register("debit", {})}
                                            isInvalid={errors.debit}
                                            placeholder={"debit"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Credit:</Form.Label>
                                        <Form.Control
                                            {...register("credit", {})}
                                            isInvalid={errors.credit}
                                            placeholder={"credit"} />
                                    </Form.Group>
                                </Col>
                            </Row>

                        </form>

                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <InertiaLink href={route("accounts.journal-voucher.index")} className="btn btn-warning">
                            <Icon icon={"solar:close-bold-duotone"} /> Close
                        </InertiaLink>&nbsp;
                        <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default JournalBookForm;

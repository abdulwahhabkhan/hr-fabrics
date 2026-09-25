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
import { FileUpload } from '@/components/File';
import BackButton from '@/components/button/back';
import journals from '@/routes/accounts/journals';

const PaymentForm = () => {
    const { payment, accounts, fileInfo, errors: serverErrors } = usePage().props;
    const title = payment ? "Edit Payment" : "Add Payment";
    const [processing, setProcessing] = useState(false);
    const payee_account = payment ? payment.account : null;

    const [file, setFile] = useState(fileInfo);
    const {
        register,
        handleSubmit,
        setError,
        control,
        watch,
        setValue,
        formState: { errors }
    } = useForm({ defaultValues: payment });
    const options = {
        onError: () => {
            setProcessing(false);
        }
    };
    const type = watch("type");

    const sendRequest = async (data) => {
        data.payment_info = { ...data.payment_info, file: file };
        const post_data = { ...data };

        setProcessing(true);
        if (payment)
            Inertia.post(journals.store(), post_data, options);
        else
            Inertia.post(journals.store(), post_data, options);
    };
    const updateValue = (item) => {
        const { type } = { ...item };
        setValue("type", type ?? "", { shouldDirty: true });
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);

    return (
        <>
            <Head title="Payments Update" />
            <PageHeader title="Payments Update" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} buttons={(
                        <>
                            <BackButton href={journals.index()} size="xs" />
                        </>
                    )} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>

                            <Row>
                                <Col md={file ? 8 : 12}>
                                    <FileUpload directory={"payments"} progress={setProcessing} updated={setFile} />
                                </Col>
                                {
                                    file && (
                                        <Col md={4}>
                                            <img
                                                src={file.file_thumbnail_url ?? file.file_thumbnail}
                                                className={"height-150"}
                                                alt={file.file_name}
                                            />
                                        </Col>
                                    )
                                }
                            </Row>
                            <Row>
                                <Col md={10}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Payee:</Form.Label>
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
                                                    onChange={(e) => {
                                                        field.onChange(e);
                                                        updateValue(e);
                                                    }}
                                                    defaultValue={payee_account}
                                                    getOptionValue={option => option["id"]}
                                                    getOptionLabel={option => option["name"]}

                                                    isClearable
                                                />
                                            )}
                                            control={control}
                                            name={"account"}

                                        />
                                    </Form.Group>

                                </Col>
                                <Col>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Type:</Form.Label>
                                        <Form.Control
                                            {...register("type", { required: true })}
                                            isInvalid={errors.type}
                                            readOnly={true} />
                                    </Form.Group>
                                </Col>
                            </Row>
                            <Row>

                                <Col md={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Amount:</Form.Label>
                                        <Form.Control
                                            {...register("amount", { required: true, min: 1 })}
                                            isInvalid={errors.amount}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Receipt No:</Form.Label>
                                        <Form.Control
                                            {...register("payment_info.receipt_no", { required: true })}
                                            isInvalid={errors.payment_info ? errors.payment_info.receipt_no : ""}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={8}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Description:</Form.Label>
                                        <Form.Control
                                            {...register("payment_info.description", { required: true })}
                                            isInvalid={errors.payment_info ? errors.payment_info.description : ""}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>

                        </form>

                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <InertiaLink href={journals.index()} className="btn btn-warning">
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

export default PaymentForm;

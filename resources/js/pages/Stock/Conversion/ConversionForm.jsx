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

const ConversionForm = () => {
    const { products, errors: serverErrors } = usePage().props;
    const title = "Add Conversion";
    const [processing, setProcessing] = useState(false);
    const { register, handleSubmit, watch, setError, control, formState: { errors } } = useForm();
    const options = {
        onError: () => {
            setProcessing(false);
        }
    };

    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        Inertia.post(route("stocks.conversions.store"), post_data, options);
    };

    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);


    return (
        <>
            <Head title="Add Conversion" />
            <PageHeader title="Add Conversion" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} buttons={(
                        <>
                            <BackButton href={route("stocks.conversions.index")} size="xs" />
                        </>
                    )} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>
                                <Col lg={12}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Product:</Form.Label>
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
                                                    options={products}
                                                    getOptionValue={option => option["id"]}
                                                    getOptionLabel={option => option["name"]}
                                                    isClearable
                                                />
                                            )}
                                            control={control}
                                            name={"product"}
                                            rules={{ required: true }}
                                        />
                                    </Form.Group>
                                </Col>

                            </Row>
                            <Row>
                                <Col lg={5}>
                                    <Row>
                                        <Col md={4}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Unit:</Form.Label>
                                                <Form.Control
                                                    {...register("from.unit", { required: true })}
                                                    isInvalid={errors.from && errors.from.unit}
                                                    defaultValue={"Thaan"}
                                                    placeholder={"unit"}
                                                    readOnly={true}
                                                />
                                            </Form.Group>
                                        </Col>

                                        <Col md={4}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Size:</Form.Label>
                                                <Form.Control
                                                    {...register("from.size", { required: true })}
                                                    isInvalid={errors.from && errors.from.size}
                                                    placeholder={"size"}
                                                />
                                            </Form.Group>
                                        </Col>

                                        <Col md={4}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Qty:</Form.Label>
                                                <Form.Control
                                                    {...register("from.qty", { required: true, min: 1 })}
                                                    isInvalid={errors.from && errors.from.qty}
                                                    placeholder={"Qty"} />
                                            </Form.Group>
                                        </Col>
                                    </Row>
                                </Col>
                                <Col lg={2} className={"text-center"}>
                                    <Icon icon={"solar:scissors-bold-duotone"} style={{ fontSize: "3em" }} className={"mt-4"} />
                                </Col>
                                <Col lg={5}>
                                    <Row>
                                        <Col md={4}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Unit:</Form.Label>
                                                <Form.Control
                                                    {...register("to.unit", { required: true })}
                                                    isInvalid={errors.to && errors.to.unit}
                                                    defaultValue={"Suit"}
                                                    placeholder={"unit"}
                                                    readOnly={true}
                                                />
                                            </Form.Group>
                                        </Col>

                                        <Col md={4}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Size:</Form.Label>
                                                <Form.Control
                                                    {...register("to.size", { required: true })}
                                                    isInvalid={errors.to && errors.to.size}
                                                    placeholder={"size"}
                                                />
                                            </Form.Group>
                                        </Col>

                                        <Col md={4}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Qty:</Form.Label>
                                                <Form.Control
                                                    {...register("to.qty", { required: true, min: 1 })}
                                                    isInvalid={errors.to && errors.to.qty}
                                                    placeholder={"Qty"} />
                                            </Form.Group>
                                        </Col>
                                    </Row>
                                </Col>
                            </Row>

                        </form>

                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <InertiaLink href={route("stocks.conversions.index")} className="btn btn-warning">
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

export default ConversionForm;

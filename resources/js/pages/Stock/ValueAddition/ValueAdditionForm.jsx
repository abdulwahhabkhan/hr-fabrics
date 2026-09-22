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

const ValueAdditionForm = () => {
    const { accounts, data, errors: serverErrors } = usePage().props;
    const title = "Value Addition";
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
            <Head title="Value Addition" />
            <PageHeader title="Value Addition" />
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
                                        <Form.Label>Purchase From:</Form.Label>
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
                                            control={control}
                                            name={"vendor"}
                                            rules={{ required: true }}
                                        />
                                    </Form.Group>
                                </Col>

                            </Row>
                            <Row>
                                <Col sm={"6"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Material Detail:</Form.Label>
                                        <Form.Control
                                            {...register("material_detail", { required: true })}
                                            isInvalid={errors.material_detail}
                                            placeholder={"material detail"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={"3"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Meter:</Form.Label>
                                        <Form.Control
                                            {...register("meter", { required: true })}
                                            isInvalid={errors.meter}
                                            placeholder={"meters"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={"3"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Material Cost:</Form.Label>
                                        <Form.Control
                                            {...register("cost", { required: true })}
                                            isInvalid={errors.cost}
                                            placeholder={"cost"} />
                                    </Form.Group>
                                </Col>
                            </Row>
                            <hr />
                            <Row>
                                <Col lg={12}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Packed By:</Form.Label>
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
                                            control={control}
                                            name={"packed_by"}
                                            rules={{ required: true }}
                                        />
                                    </Form.Group>
                                </Col>

                            </Row>
                            <Row>
                                <Col sm={"6"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Packing Detail:</Form.Label>
                                        <Form.Control
                                            {...register("packing_detail", { required: true })}
                                            isInvalid={errors.packing_detail}
                                            placeholder={"packing detail"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={"3"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Qty:</Form.Label>
                                        <Form.Control
                                            {...register("qty", { required: true })}
                                            isInvalid={errors.qty}
                                            placeholder={"qty"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={"3"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Packing Cost:</Form.Label>
                                        <Form.Control
                                            {...register("packing_cost", { required: true })}
                                            isInvalid={errors.packing_cost}
                                            placeholder={"packing cost"} />
                                    </Form.Group>
                                </Col>
                            </Row>

                            <hr />
                            <Row>
                                <Col sm={"6"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Cut Piece Detail:</Form.Label>
                                        <Form.Control
                                            {...register("cp_detail", { required: true })}
                                            isInvalid={errors.cp_detail}
                                            placeholder={"cut piece detail"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={"3"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Cut Piece Meter:</Form.Label>
                                        <Form.Control
                                            {...register("cp_meter", { required: true })}
                                            isInvalid={errors.cp_meter}
                                            placeholder={"cut piece meter"} />
                                    </Form.Group>
                                </Col>
                                <Col sm={"3"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Cut Piece Cost:</Form.Label>
                                        <Form.Control
                                            {...register("cp_cost", { required: true })}
                                            isInvalid={errors.cp_cost}
                                            placeholder={"cut piece cost"} />
                                    </Form.Group>
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

export default ValueAdditionForm;

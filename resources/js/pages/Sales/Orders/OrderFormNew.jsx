import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Alert, Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import ValidationErrors from '@/components/ValidationErrors';
import BackButton from '@/components/button/back';

const OrderFormNew = () => {
    const { customers, errors: serverSideError } = usePage().props;

    const [processing, setProcessing] = useState(false);
    const [suspended, setSuspended] = useState(false);

    const {
        handleSubmit,
        control,
        setError,
        formState: { errors },
    } = useForm();
    const options = {
        onError: (error) => {
            setProcessing(false);
        },
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        // Check if customer is suspended before submitting
        if (data.customer && data.customer.suspended) {
            setSuspended(true);
            setProcessing(false);
            return;
        }

        const post_data = { ...data, customer_id: data.customer.customer_id };
        setProcessing(true);
        Inertia.post(route("sales.orders.store"), post_data, options);
    };

    const handleCustomerChange = (selectedCustomer, onChange) => {
        if (selectedCustomer && selectedCustomer.suspended) {
            setSuspended(true);
        } else {
            setSuspended(false);
        }
        onChange(selectedCustomer);
    };

    return (
        <>
            <Head title="Create Invoice" />
            <PageHeader title="Create Invoice" buttons={<>
                <BackButton href={route("sales.orders.index")} />
            </>} />

            <PageContent>
                <Panel theme={"default"}>
                    <PanelHeader heading={"Create Order"} buttons={(
                        <>
                            <LoadingButton
                                className={"btn-xs"}
                                processing={processing}
                                disabled={suspended}
                                onClick={handleSubmit(sendRequest)}
                            >
                                Create Order
                            </LoadingButton>
                        </>
                    )} />

                    <PanelBody>
                        <Row>
                            <ValidationErrors errors={serverSideError} />
                            <Col lg={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Customer:</Form.Label>
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                onChange={(selectedCustomer) =>
                                                    handleCustomerChange(selectedCustomer, field.onChange)
                                                }
                                                options={customers}
                                                getOptionValue={(option) => option["customer_id"]}
                                                getOptionLabel={(option) =>
                                                    option["customer_name"] + " " + option["city"]
                                                }
                                                isClearable
                                            />
                                        )}
                                        control={control}
                                        name={"customer"}
                                        rules={{ required: true }}
                                    />
                                </Form.Group>
                            </Col>
                            {suspended && (
                                <Col lg={12}>
                                    <Alert variant="warning">
                                        This customer is suspended. You cannot create an order for this customer.
                                    </Alert>
                                </Col>
                            )}
                        </Row>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default OrderFormNew;

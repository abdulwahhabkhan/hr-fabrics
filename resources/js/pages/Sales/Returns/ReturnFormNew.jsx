import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import ValidationErrors from '@/components/ValidationErrors';
import BackButton from '@/components/button/back';

const ReturnFormNew = () => {
    const { customers, errors: serverSideError } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const { handleSubmit, control, setError, formState: { errors } } = useForm();
    const options = {
        onError: (error) => {
            setProcessing(false);
        }
    };
    const sendRequest = async (data) => {
        const post_data = { ...data, customer_id: data.customer.customer_id };
        setProcessing(true);
        Inertia.post(route("sales.returns.store"), post_data, options);
    };

    return (
        <>
            <Head title="Create Invoice" />
            <PageHeader title="Create Invoice" />
            <PageContent>
                <Panel theme={"default"}>
                    <PanelHeader heading={"Create Return"} buttons={(
                        <>
                            <LoadingButton className={"btn-xs"} processing={processing}
                                           onClick={handleSubmit(sendRequest)}>
                                Create Return
                            </LoadingButton>
                            <BackButton href={route("sales.returns.index")} size="xs" />
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
                                                options={customers}
                                                getOptionValue={option => option["customer_id"]}
                                                getOptionLabel={option => option["customer_name"] + " " + option["city"]}
                                                isClearable
                                            />
                                        )}
                                        control={control}
                                        name={"customer"}
                                        rules={{ required: true }}
                                    />
                                </Form.Group>
                            </Col>

                        </Row>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ReturnFormNew;

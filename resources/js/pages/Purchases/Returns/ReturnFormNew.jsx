import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import BackButton from '@/components/button/back';
import axios from 'axios';
import { notifyMessage } from '@/util/util.jsx';
import por from '@/routes/purchases/por';

const ReturnFormNew = () => {
    const { suppliers } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const { handleSubmit, control, formState: { errors } } = useForm();

    const sendRequest = async (data) => {
        setProcessing(true);
        axios.post(por.store().url, data)
            .then(res => {
                const { data: { message, redirect } } = res;
                notifyMessage({ title: "Success", type: 'success', message: message });
                Inertia.visit(redirect);
            })
            .catch(() => {
                setProcessing(false);
            });
    };

    return (
        <>
            <Head title="Create Fabric Return" />
            <PageHeader title="Create Fabric Return" />
            <PageContent>
                <Panel theme={"default"}>
                    <PanelHeader heading={"Fabric Return"} buttons={(
                        <>
                            <LoadingButton className={"btn-xs"} processing={processing}
                                           onClick={handleSubmit(sendRequest)}>
                                Create Return
                            </LoadingButton>
                            <BackButton href={por.index()} size="xs" />
                        </>
                    )} />
                    <PanelBody>
                        <Row>
                            <Col lg={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Supplier:</Form.Label>
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                options={suppliers}
                                                getOptionValue={option => option["supplier_id"]}
                                                getOptionLabel={option => option["supplier_name"]}
                                                isClearable
                                            />
                                        )}
                                        control={control}
                                        name={"supplier"}
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

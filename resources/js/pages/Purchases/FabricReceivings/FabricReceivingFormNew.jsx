import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import BackButton from '@/components/button/back';

const FabricReceivingFormNew = () => {
    const { suppliers } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const { handleSubmit, control, setError, formState: { errors } } = useForm();
    const options = {
        onError: () => {
            setProcessing(false);
        }
    };
    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        Inertia.post(route("purchases.fabric-receivings.store"), post_data, options);
    };


    return (
        <>
            <Head title="Create Fabric Receiving" />
            <PageHeader title="Create Fabric Receiving" buttons={(
                <>
                    <BackButton href={route("purchases.fabric-receivings.index")}  />
                </>
            )}/>
            <PageContent>
                <Panel theme={"default"}>
                    <PanelHeader heading={"Fabric Receiving"} buttons={(
                        <>
                            <LoadingButton className={"btn-xs"} processing={processing}
                                           onClick={handleSubmit(sendRequest)}>
                                Create Receiving
                            </LoadingButton>
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

export default FabricReceivingFormNew;

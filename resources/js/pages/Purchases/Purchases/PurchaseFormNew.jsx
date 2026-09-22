import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { ErrorPanel } from '@/components/panel/ErrorPanel';
import BackButton from '@/components/button/back';

const PurchaseFormNew = () => {
    const { stocks, errors: serverErrors } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const { handleSubmit, control, setError, setValue, formState: { errors } } = useForm();
    const options = {
        onFinish: () => {
            setProcessing(false);
        }
    };
    const sendRequest = async (data) => {
        const stock = { ...data.stock };
        const post_data = { stock: stock };
        setProcessing(true);
        Inertia.post(route("purchases.pos.store"), post_data, options);
    };

    return (
        <>
            <Head title="Create Purchase" />
            <PageHeader title="Create Purchase" buttons={<>
                <BackButton href={route("purchases.pos.index")} />
            </>} />
            <PageContent>
                <Panel theme={"default"}>
                    <PanelHeader heading={"Create Voucher"} buttons={(
                        <>
                            <LoadingButton className={"btn-xs"} processing={processing}
                                           onClick={handleSubmit(sendRequest)}>
                                Create Voucher
                            </LoadingButton>
                        </>
                    )} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <Row>
                            <Col lg={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Purchase Order:</Form.Label>
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                isMulti
                                                options={stocks}
                                                getOptionValue={option => option["id"]}
                                                getOptionLabel={option => option["invoice_no"] + " " + option["bilti_no"] + " " + option["lot_no"]}
                                                isClearable
                                            />
                                        )}
                                        control={control}
                                        name={"stock"}
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

export default PurchaseFormNew;

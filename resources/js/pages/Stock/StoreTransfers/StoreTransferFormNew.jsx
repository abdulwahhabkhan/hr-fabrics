import React, { useState } from 'react';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Col, Form, Row } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import ValidationErrors from '@/components/ValidationErrors';
import Back from '@/components/button/back.tsx';
import LoadingButton from '@/components/LoadingButton.jsx';

const StoreTransferFormNew = () => {
    const { stores, errors: serverSideError } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const {
        handleSubmit,
        control,
        formState: { errors },
    } = useForm();
    const options = {
        onError: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        const post_data = {
            ...data,
            account_id: data.from_account.account_id,
        };
        setProcessing(true);
        Inertia.post(route('stocks.store-transfers.store'), post_data, options);
    };

    return (
        <>
            <Head title="Create Store Transfer" />
            <PageHeader
                title="Create Store Transfer"
                buttons={
                    <>
                        <Back
                            label="Store Transfers List"
                            href={route('stocks.store-transfers.index')}
                        />
                    </>
                }
            />
            <PageContent>
                <Panel theme={'default'}>
                    <PanelHeader heading={'Create Store Transfer'} />
                    <PanelBody>
                        <Row>
                            <ValidationErrors errors={serverSideError} />
                            <Col lg={8}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Send To Store:</Form.Label>
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                placeholder={
                                                    'Select a store...'
                                                }
                                                {...field}
                                                options={stores}
                                                getOptionValue={(option) =>
                                                    option['account_id']
                                                }
                                                getOptionLabel={(option) =>
                                                    option['account_name'] +
                                                    ' ' +
                                                    option['city']
                                                }
                                                isClearable
                                            />
                                        )}
                                        control={control}
                                        name={'from_account'}
                                        rules={{ required: true }}
                                    />
                                </Form.Group>
                            </Col>
                            <Col col={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>&nbsp;</Form.Label>
                                    <br />
                                    <LoadingButton
                                        processing={processing}
                                        onClick={handleSubmit(sendRequest)}
                                    >
                                        Create Transfer
                                    </LoadingButton>
                                </Form.Group>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default StoreTransferFormNew;

import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import {
    Panel,
    PanelBody,
    PanelFooter,
    PanelHeader,
} from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import StyledSelect from '@/components/StyledSelect';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import 'react-datetime/css/react-datetime.css';
import { FileDetail, FileUpload } from '@/components/File';
import now from 'lodash';
import axios from 'axios';
import { NumberFormat } from '@/util/NumberFormat';
import Back from '@/components/button/back';

const JournalSingleForm = () => {
    const { errors: serverErrors, accounts, directory } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const title = 'Create Journal Single Entry Voucher';
    const [processing, setProcessing] = useState(false);
    const [balance, setBalance] = useState('0.0');
    const [file, setFile] = useState(null);
    const {
        register,
        handleSubmit,
        setError,
        control,
        watch,
        formState: { errors },
    } = useForm({ defaultValues: { date: new Date() } });
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const selectedAccount = watch('account');
    const sendRequest = async (data) => {
        const post_data = { ...data, file: file };
        setProcessing(true);
        Inertia.post(
            route('accounts.journals.single-store'),
            post_data,
            options,
        );
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
        if (selectedAccount && selectedAccount.id) {
            setBalance(selectedAccount.balance);
            getBalance(selectedAccount.id);
        }
    }, [serverErrors, selectedAccount]);

    const getBalance = (id) => {
        axios.get(route('accounts.accounts.balance', id)).then((res) => {
            setBalance(res.data.balance);
        });
    };
    return (
        <>
            <Head title="Journal Voucher" />
            <PageHeader
                title="Journal Voucher"
                buttons={
                    <>
                        <Back
                            label="Journals List"
                            href={route('accounts.journals.index')}
                        />
                    </>
                }
            />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form
                            action=""
                            className=""
                            onSubmit={handleSubmit(sendRequest)}
                        >
                            <Row>
                                <Col sm={8}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Account:</Form.Label>
                                        {
                                            <Controller
                                                render={({ field }) => (
                                                    <StyledSelect
                                                        {...field}
                                                        options={accounts}
                                                        getOptionValue={(
                                                            option,
                                                        ) => option['id']}
                                                        getOptionLabel={(
                                                            option,
                                                        ) =>
                                                            option['name'] +
                                                            ' ' +
                                                            option['address'][
                                                                'city'
                                                            ]
                                                        }
                                                        isClearable
                                                    />
                                                )}
                                                control={control}
                                                name={'account'}
                                            />
                                        }
                                    </Form.Group>
                                </Col>
                                <Col md={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Balance:</Form.Label>

                                        <NumberFormat
                                            className={'form-control'}
                                            displayType={'text'}
                                            value={balance}
                                            thousandSeparator={true}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col sm={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Date</Form.Label>
                                        <Controller
                                            control={control}
                                            name="date"
                                            render={({ field }) => (
                                                <DatetimeComponent
                                                    initialValue={now()}
                                                    dateFormat={
                                                        settings.SEARCH_DATE_FORMAT
                                                    }
                                                    onChange={(e) =>
                                                        field.onChange(
                                                            e.format(
                                                                'YYYY-MM-DD',
                                                            ),
                                                        )
                                                    }
                                                    closeOnSelect={true}
                                                    placeholder={'date'}
                                                    timeFormat={false}
                                                />
                                            )}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Detail:</Form.Label>
                                        <Form.Control
                                            {...register('detail', {
                                                required: true,
                                            })}
                                            isInvalid={errors.detail}
                                            placeholder={'detail'}
                                        />
                                    </Form.Group>
                                </Col>

                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Amount:</Form.Label>
                                        <Form.Control
                                            {...register('amount', {
                                                required: true,
                                            })}
                                            isInvalid={errors.amount}
                                            placeholder={'amount'}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Type</Form.Label>
                                        <Form.Select
                                            name={'type'}
                                            className="input-150"
                                            autoComplete="off"
                                            {...register('type', {
                                                required: true,
                                            })}
                                        >
                                            <option value="debit">Debit</option>
                                            <option value="credit">
                                                Credit
                                            </option>
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row>
                                <Col md={file ? 8 : 12}>
                                    <FileUpload
                                        directory={directory}
                                        msg={'Image/PDF files only'}
                                        progress={setProcessing}
                                        updated={setFile}
                                    />
                                </Col>
                                {file && (
                                    <Col md={4}>
                                        <FileDetail file={file} />
                                    </Col>
                                )}
                            </Row>
                        </form>
                    </PanelBody>
                    <PanelFooter className={'text-center'}>
                        <Back
                            label="Journals List"
                            href={route('accounts.journals.index')}
                        />

                        <LoadingButton
                            processing={processing}
                            onClick={handleSubmit(sendRequest)}
                        >
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default JournalSingleForm;

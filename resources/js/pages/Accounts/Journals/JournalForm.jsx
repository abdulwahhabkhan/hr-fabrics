import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelFooter, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import StyledSelect from '@/components/StyledSelect';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import 'react-datetime/css/react-datetime.css';
import { FileUpload } from '@/components/File';
import BackButton from '@/components/button/back';
import journals from '@/routes/accounts/journals';

const JournalForm = () => {
    const { errors: serverErrors, accounts, date } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const title = 'Create Journal Voucher';
    const [processing, setProcessing] = useState(false);
    const [file, setFile] = useState({
        /* 'file_name': 'file',
         'file_thumbnail': '/file/view?path=file.svg',*/
    });
    const {
        register,
        handleSubmit,
        setError,
        control,
        watch,
        formState: { errors }
    } = useForm({
        defaultValues: {
            date: date
        }
    });
    const options = {
        onFinish: () => {
            setProcessing(false);
        }
    };
    const sendRequest = async (data) => {
        const post_data = { ...data, file: file };
        setProcessing(true);
        Inertia.post(journals.store(), post_data, options);
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);
    return (
        <>
            <Head title="Journal Voucher Update" />
            <PageHeader title="Journal Voucher Update" buttons={(
                <>
                    <BackButton href={journals.index()} />
                </>
            )} />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>From Account:</Form.Label>
                                        {
                                            <Controller
                                                render={({ field }) => (
                                                    <StyledSelect
                                                        {...field}
                                                        options={accounts}
                                                        getOptionValue={(option) => option['id']}
                                                        getOptionLabel={(option) =>
                                                            option['name'] + ' ' + option['address']['city']
                                                        }
                                                        isClearable
                                                    />
                                                )}
                                                control={control}
                                                name={'from_account'}
                                            />
                                        }
                                    </Form.Group>
                                </Col>

                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>To Account:</Form.Label>
                                        {
                                            <Controller
                                                render={({ field }) => (
                                                    <StyledSelect
                                                        {...field}
                                                        options={accounts}
                                                        getOptionValue={(option) => option['id']}
                                                        getOptionLabel={(option) =>
                                                            option['name'] + ' ' + option['address']['city']
                                                        }
                                                        isClearable
                                                    />
                                                )}
                                                control={control}
                                                name={'to_account'}
                                            />
                                        }
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Detail:</Form.Label>
                                        <Form.Control
                                            {...register('detail', { required: true })}
                                            isInvalid={errors.detail}
                                            placeholder={'detail'}
                                        />
                                    </Form.Group>
                                </Col>

                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Amount:</Form.Label>
                                        <Form.Control
                                            {...register('amount', { required: true })}
                                            isInvalid={errors.amount}
                                            placeholder={'amount'}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Date</Form.Label>
                                        <Controller
                                            control={control}
                                            name="date"
                                            render={({ field }) => (
                                                <DatetimeComponent
                                                    initialValue={field.value}
                                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                                    onChange={(e) => field.onChange(e.format('YYYY-MM-DD'))}
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
                                <Col md={file ? 8 : 12}>
                                    <FileUpload
                                        directory={'vouchers'}
                                        msg={'Image/PDF files only'}
                                        progress={setProcessing}
                                        updated={setFile}
                                    />
                                </Col>
                                {file && (
                                    <Col md={4}>
                                        <img
                                            src={file.file_thumbnail_url ?? file.file_thumbnail}
                                            className={'height-150'}
                                            alt={file.file_name}
                                        />
                                    </Col>
                                )}
                            </Row>
                        </form>
                    </PanelBody>
                    <PanelFooter className={'text-center'}>
                        <BackButton size={'md'} href={journals.index()} />

                        <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default JournalForm;

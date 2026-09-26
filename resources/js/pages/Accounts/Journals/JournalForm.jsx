import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, InputGroup, Row } from 'react-bootstrap';
import { Icon } from '@iconify/react';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import StyledSelect from '@/components/StyledSelect';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import 'react-datetime/css/react-datetime.css';
import { FileUpload } from '@/components/File';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import journals from '@/routes/accounts/journals';

const accountLabel = (option) => [option['name'], option['address']?.['city']].filter(Boolean).join(' · ');

const JournalForm = () => {
    const { errors: serverErrors, accounts, date } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const title = 'New Journal Voucher';
    const [processing, setProcessing] = useState(false);
    const [file, setFile] = useState(null);
    const {
        register,
        handleSubmit,
        setError,
        control,
        formState: { errors },
    } = useForm({
        defaultValues: {
            date: date,
        },
    });
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
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
            <Head title={title} />
            <PageHeader
                title={title}
                description="Transfer an amount between two accounts"
                buttons={<BackButton href={journals.index()} label="Journals List" />}
            />
            <PageContent>
                <ErrorPanel errors={serverErrors} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:transfer-horizontal-bold-duotone"
                                title="Transfer"
                                description="The source account is credited and the destination account is debited."
                            >
                                <div className="hf-transfer">
                                    <FormField label="From account (credit)" required>
                                        <Controller
                                            render={({ field }) => (
                                                <StyledSelect
                                                    {...field}
                                                    options={accounts}
                                                    getOptionValue={(option) => option['id']}
                                                    getOptionLabel={accountLabel}
                                                    placeholder="Select account..."
                                                    isClearable
                                                />
                                            )}
                                            control={control}
                                            name={'from_account'}
                                        />
                                    </FormField>
                                    <span className="hf-transfer__arrow" aria-hidden="true">
                                        <Icon icon="solar:arrow-right-linear" />
                                    </span>
                                    <FormField label="To account (debit)" required>
                                        <Controller
                                            render={({ field }) => (
                                                <StyledSelect
                                                    {...field}
                                                    options={accounts}
                                                    getOptionValue={(option) => option['id']}
                                                    getOptionLabel={accountLabel}
                                                    placeholder="Select account..."
                                                    isClearable
                                                />
                                            )}
                                            control={control}
                                            name={'to_account'}
                                        />
                                    </FormField>
                                </div>
                            </FormSection>

                            <FormSection
                                icon="solar:document-text-bold-duotone"
                                title="Voucher details"
                                description="Amount, posting date and a short narration for the ledger."
                            >
                                <Row className="g-3">
                                    <Col md={6}>
                                        <FormField label="Amount" htmlFor="amount" required>
                                            <InputGroup className="hf-amount">
                                                <InputGroup.Text>Rs</InputGroup.Text>
                                                <Form.Control
                                                    id="amount"
                                                    inputMode="decimal"
                                                    {...register('amount', { required: true })}
                                                    isInvalid={errors.amount}
                                                    placeholder={'0'}
                                                />
                                            </InputGroup>
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Date" required>
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
                                        </FormField>
                                    </Col>
                                    <Col md={12}>
                                        <FormField label="Narration" htmlFor="detail" required>
                                            <Form.Control
                                                id="detail"
                                                {...register('detail', { required: true })}
                                                isInvalid={errors.detail}
                                                placeholder={'e.g. Cash deposited to bank'}
                                            />
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:paperclip-bold-duotone"
                                title="Attachment"
                                description="Optional receipt or bank slip. Images and PDF files only."
                            >
                                <Row className="g-3">
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
                                            <div className="hf-attachment-preview">
                                                <img src={file.file_thumbnail_url ?? file.file_thumbnail} alt={file.file_name} />
                                            </div>
                                        </Col>
                                    )}
                                </Row>
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={journals.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                Post voucher
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default JournalForm;

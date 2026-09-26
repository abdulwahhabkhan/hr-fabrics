import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, InputGroup, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import StyledSelect from '@/components/StyledSelect';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import 'react-datetime/css/react-datetime.css';
import { FileDetail, FileUpload } from '@/components/File';
import axios from 'axios';
import { NumberFormat } from '@/util/NumberFormat';
import Back from '@/components/button/back';
import { FormActions, FormField, FormSection, SegmentedControl } from '@/components/form/FormSection';
import journals, { singleStore } from '@/routes/accounts/journals';
import accountsModule from '@/routes/accounts/accounts';

const accountLabel = (option) => [option['name'], option['address']?.['city']].filter(Boolean).join(' · ');

const ENTRY_TYPES = [
    { value: 'debit', label: 'Debit', icon: 'solar:arrow-left-down-linear' },
    { value: 'credit', label: 'Credit', icon: 'solar:arrow-right-up-linear' },
];

const JournalSingleForm = () => {
    const { errors: serverErrors, accounts, directory } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const title = 'Single Entry Voucher';
    const [processing, setProcessing] = useState(false);
    const [balance, setBalance] = useState(null);
    const [file, setFile] = useState(null);
    const {
        register,
        handleSubmit,
        setError,
        control,
        watch,
        formState: { errors },
    } = useForm({ defaultValues: { date: new Date(), type: 'debit' } });
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const selectedAccount = watch('account');
    const sendRequest = async (data) => {
        const post_data = { ...data, file: file };
        setProcessing(true);
        Inertia.post(singleStore(), post_data, options);
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
        if (selectedAccount && selectedAccount.id) {
            setBalance(selectedAccount.balance);
            getBalance(selectedAccount.id);
        } else {
            setBalance(null);
        }
    }, [serverErrors, selectedAccount]);

    const getBalance = (id) => {
        axios.get(accountsModule.balance(id).url).then((res) => {
            setBalance(res.data.balance);
        });
    };
    return (
        <>
            <Head title={title} />
            <PageHeader
                title={title}
                description="Post a debit or credit against one account"
                buttons={<Back label="Journals List" href={journals.index()} />}
            />
            <PageContent>
                <ErrorPanel errors={serverErrors} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:wallet-bold-duotone"
                                title="Account"
                                description="Pick the account to post against. Its current balance is shown for reference."
                            >
                                <Row className="g-3">
                                    <Col md={8}>
                                        <FormField label="Account" required>
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
                                                name={'account'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={4}>
                                        <FormField label="Current balance">
                                            <div className={'hf-field-static' + (balance < 0 ? ' is-negative' : '')}>
                                                {balance === null ? (
                                                    <span className="hf-muted-value fw-normal">—</span>
                                                ) : (
                                                    <>
                                                        <span className="hf-currency">Rs</span>
                                                        <NumberFormat displayType={'text'} value={balance} thousandSeparator={true} />
                                                    </>
                                                )}
                                            </div>
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:document-text-bold-duotone"
                                title="Entry details"
                                description="Direction, amount, posting date and a short narration."
                            >
                                <Row className="g-3">
                                    <Col md={12}>
                                        <FormField label="Entry type" required>
                                            <div>
                                                <SegmentedControl
                                                    name="type"
                                                    options={ENTRY_TYPES}
                                                    register={register}
                                                    className="is-debit-credit"
                                                />
                                            </div>
                                        </FormField>
                                    </Col>
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
                                                placeholder={'e.g. Opening balance adjustment'}
                                            />
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:paperclip-bold-duotone"
                                title="Attachment"
                                description="Optional receipt or supporting document. Images and PDF files only."
                            >
                                <Row className="g-3">
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
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <Back label="Cancel" href={journals.index()} />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                Post entry
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default JournalSingleForm;

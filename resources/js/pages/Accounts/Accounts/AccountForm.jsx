import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import { useAccountTypes } from '@/util/util';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import accounts from '@/routes/accounts/accounts';

const AccountForm = () => {
    const { account, expense_accounts, errors: serverErrors } = usePage().props;
    const accountTypes = useAccountTypes();
    const title = account ? 'Edit Account' : 'Add Account';
    const [processing, setProcessing] = useState(false);

    const { register, handleSubmit, setError, watch, formState: { errors } } = useForm({ defaultValues: account });
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const type = watch('type');

    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        if (account)
            Inertia.put(accounts.update(account['id']), post_data, options);
        else
            Inertia.post(accounts.store(), post_data, options);
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
                description={account ? account.name : 'Create a ledger account'}
                buttons={<BackButton href={accounts.index()} label="Accounts" />}
            />
            <PageContent>
                <ErrorPanel errors={serverErrors} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:wallet-money-bold-duotone"
                                title="Account"
                                description="The type decides where this account appears in ledgers and reports."
                            >
                                <Row className="g-3">
                                    <Col md={4}>
                                        <FormField label="Account type" htmlFor="type" required>
                                            <Form.Select
                                                id="type"
                                                {...register('type', { required: true })}
                                                isInvalid={errors.type}
                                            >
                                                <option value={''}>Select type</option>
                                                {accountTypes &&
                                                    accountTypes.map((val, index) => <option key={index}>{val}</option>)}
                                            </Form.Select>
                                        </FormField>
                                    </Col>
                                    <Col md={8}>
                                        <FormField label="Account name" htmlFor="name" required>
                                            <Form.Control
                                                id="name"
                                                {...register('name', { required: true })}
                                                isInvalid={errors.name}
                                                placeholder={'e.g. Meezan Bank, Shop Rent'}
                                            />
                                        </FormField>
                                    </Col>
                                    {type == 'agent' && (
                                        <Col md={12}>
                                            <FormField
                                                label="Expense account"
                                                htmlFor="expense_account"
                                                required
                                                hint="Agent commissions are posted against this expense account."
                                            >
                                                <Form.Select
                                                    id="expense_account"
                                                    {...register('expense_account', { required: true })}
                                                    isInvalid={errors.expense_account}
                                                >
                                                    <option value={''}>Select expense account</option>
                                                    {expense_accounts &&
                                                        expense_accounts.map(({ id, name }) => (
                                                            <option value={id} key={id}>{name}</option>
                                                        ))}
                                                </Form.Select>
                                            </FormField>
                                        </Col>
                                    )}
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:phone-calling-bold-duotone"
                                title="Contact"
                                description="Optional contact details for this account holder."
                            >
                                <Row className="g-3">
                                    <Col md={6}>
                                        <FormField label="Phone" htmlFor="phone">
                                            <Form.Control
                                                id="phone"
                                                type="tel"
                                                {...register('phone')}
                                                isInvalid={errors.phone}
                                                placeholder={'03xx xxxxxxx'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Email" htmlFor="email">
                                            <Form.Control
                                                id="email"
                                                type="email"
                                                {...register('email')}
                                                isInvalid={errors.email}
                                                placeholder={'name@example.com'}
                                            />
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:map-point-bold-duotone"
                                title="Address"
                                description="Shown on ledgers and account statements."
                            >
                                <Row className="g-3">
                                    <Col md={12}>
                                        <FormField label="Street address" htmlFor="address">
                                            <Form.Control
                                                id="address"
                                                {...register('address.address')}
                                                placeholder={'Street / building'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="City" htmlFor="city">
                                            <Form.Control id="city" {...register('address.city')} placeholder={'City'} />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Region" htmlFor="region">
                                            <Form.Control id="region" {...register('address.region')} placeholder={'Area / region'} />
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={accounts.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                Save account
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default AccountForm;

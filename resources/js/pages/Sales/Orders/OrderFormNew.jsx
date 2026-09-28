import React, { useState } from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Alert } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import ValidationErrors from '@/components/ValidationErrors';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import orders from '@/routes/sales/orders';

const DISCOUNT_SUFFIX = { percentage_on_total: '% on total', fixed_per_meter: ' per meter' };

const OrderFormNew = () => {
    const { customers, errors: serverSideError } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const {
        handleSubmit,
        control,
        watch,
        formState: { errors },
    } = useForm();
    const customer = watch('customer');
    const suspended = Boolean(customer?.suspended);

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        if (data.customer?.suspended) {
            return;
        }

        setProcessing(true);
        Inertia.post(orders.store(), { ...data, customer_id: data.customer.customer_id }, options);
    };

    return (
        <>
            <Head title="New Sales Invoice" />
            <PageHeader
                title="New Sales Invoice"
                description="Pick a customer to start a draft invoice"
                buttons={<BackButton href={orders.index()} label="Invoices" />}
            />

            <PageContent>
                <ValidationErrors errors={serverSideError} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:user-id-bold-duotone"
                                title="Customer"
                                description="The invoice is created as a draft. You can add items, discount and payment details on the next step."
                            >
                                <FormField label="Customer" required hint={!customer && 'Search by customer name or city.'}>
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                options={customers}
                                                getOptionValue={(option) => option['customer_id']}
                                                getOptionLabel={(option) =>
                                                    option['customer_name'] + (option['city'] ? ', ' + option['city'] : '')
                                                }
                                                placeholder="Select customer..."
                                                isClearable
                                                autoFocus
                                            />
                                        )}
                                        control={control}
                                        name="customer"
                                        rules={{ required: true }}
                                    />
                                    {errors.customer && <div className="invalid-feedback d-block">Please select a customer.</div>}
                                </FormField>

                                {customer && (
                                    <div className="hf-order-customer mt-3">
                                        <span className="hf-order-customer__icon">
                                            <Icon icon="solar:shop-2-bold-duotone" />
                                        </span>
                                        <div className="min-w-0">
                                            <div className="hf-cell-title">{customer.customer_name}</div>
                                            <div className="hf-cell-sub">
                                                {customer.city && <span>{customer.city}</span>}
                                                {customer.discount > 0 && (
                                                    <span className="hf-chip">
                                                        Discount {customer.discount}{DISCOUNT_SUFFIX[customer.discount_type] ?? ''}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <span className={'ms-auto hf-status ' + (suspended ? 'is-suspended' : 'is-active')}>
                                            {suspended ? 'Suspended' : 'Active'}
                                        </span>
                                    </div>
                                )}

                                {suspended && (
                                    <Alert variant="danger" className="mt-3 mb-0">
                                        This customer is suspended. You cannot create an invoice for them.
                                    </Alert>
                                )}
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={orders.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing} disabled={suspended}>
                                Create invoice
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default OrderFormNew;

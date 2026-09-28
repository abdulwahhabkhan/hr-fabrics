import React, { useState } from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import ValidationErrors from '@/components/ValidationErrors';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import returns from '@/routes/sales/returns';

const ReturnFormNew = () => {
    const { customers, errors: serverSideError } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const {
        handleSubmit,
        control,
        watch,
        formState: { errors },
    } = useForm();
    const customer = watch('customer');

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        Inertia.post(returns.store(), { ...data, customer_id: data.customer.customer_id }, options);
    };

    return (
        <>
            <Head title="New Sales Return" />
            <PageHeader
                title="New Sales Return"
                description="Pick the customer returning goods"
                buttons={<BackButton href={returns.index()} label="Returns" />}
            />

            <PageContent>
                <ValidationErrors errors={serverSideError} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:undo-left-round-bold-duotone"
                                title="Customer"
                                description="The return is created as a draft. You can add returned items, order no and refund mode on the next step."
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
                                            {customer.city && <div className="hf-cell-sub">{customer.city}</div>}
                                        </div>
                                        {customer.suspended ? (
                                            <span className="ms-auto hf-status is-suspended">Suspended</span>
                                        ) : (
                                            <span className="ms-auto hf-status is-active">Active</span>
                                        )}
                                    </div>
                                )}
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={returns.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                Create return
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default ReturnFormNew;

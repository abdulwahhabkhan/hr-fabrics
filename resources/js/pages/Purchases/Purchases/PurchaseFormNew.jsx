import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import ValidationErrors from '@/components/ValidationErrors';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import pos from '@/routes/purchases/pos';

const PurchaseFormNew = () => {
    const { stocks, errors: serverSideError } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const {
        handleSubmit,
        control,
        formState: { errors },
    } = useForm();

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        Inertia.post(pos.store(), { stock: { ...data.stock } }, options);
    };

    return (
        <>
            <Head title="New Fabric Purchase" />
            <PageHeader
                title="New Fabric Purchase"
                description="Pick the fabric receivings to invoice"
                buttons={<BackButton href={pos.index()} label="Purchases" />}
            />

            <PageContent>
                <ValidationErrors errors={serverSideError} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:delivery-bold-duotone"
                                title="Fabric receivings"
                                description="The purchase is created as a draft from the selected receivings. You can review items, prices and bill details on the next step."
                            >
                                <FormField label="Receivings" required hint="Search by ref no, bilti no or lot no.">
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                isMulti
                                                options={stocks}
                                                getOptionValue={(option) => option['id']}
                                                getOptionLabel={(option) =>
                                                    option['invoice_no'] + ' ' + option['bilti_no'] + ' ' + option['lot_no']
                                                }
                                                placeholder="Select receivings..."
                                                isClearable
                                                autoFocus
                                            />
                                        )}
                                        control={control}
                                        name="stock"
                                        rules={{ required: true }}
                                    />
                                    {errors.stock && <div className="invalid-feedback d-block">Please select at least one receiving.</div>}
                                </FormField>
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={pos.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                Create purchase
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default PurchaseFormNew;

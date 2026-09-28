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
import fabricReceivings from '@/routes/purchases/fabric-receivings';

const FabricReceivingFormNew = () => {
    const { suppliers, errors: serverSideError } = usePage().props;

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
        Inertia.post(fabricReceivings.store(), { ...data }, options);
    };

    return (
        <>
            <Head title="New Fabric Receiving" />
            <PageHeader
                title="New Fabric Receiving"
                description="Pick a supplier to start a draft receiving"
                buttons={<BackButton href={fabricReceivings.index()} label="Receivings" />}
            />

            <PageContent>
                <ValidationErrors errors={serverSideError} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:delivery-bold-duotone"
                                title="Supplier"
                                description="The receiving is created as a draft. You can add items, bilti and lot details on the next step."
                            >
                                <FormField label="Supplier" required hint="Search by supplier name.">
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                options={suppliers}
                                                getOptionValue={(option) => option['supplier_id']}
                                                getOptionLabel={(option) => option['supplier_name']}
                                                placeholder="Select supplier..."
                                                isClearable
                                                autoFocus
                                            />
                                        )}
                                        control={control}
                                        name="supplier"
                                        rules={{ required: true }}
                                    />
                                    {errors.supplier && <div className="invalid-feedback d-block">Please select a supplier.</div>}
                                </FormField>
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={fabricReceivings.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                Create receiving
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default FabricReceivingFormNew;

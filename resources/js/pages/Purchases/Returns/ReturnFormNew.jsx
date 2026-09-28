import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import axios from 'axios';
import { notifyMessage, serverSideError } from '@/util/util.jsx';
import por from '@/routes/purchases/por';

const ReturnFormNew = () => {
    const { suppliers } = usePage().props;

    const [processing, setProcessing] = useState(false);

    const {
        handleSubmit,
        control,
        formState: { errors },
    } = useForm();

    const sendRequest = async (data) => {
        setProcessing(true);
        axios
            .post(por.store().url, data)
            .then((res) => {
                const {
                    data: { message, redirect },
                } = res;
                notifyMessage({ title: 'Success', type: 'success', message: message });
                Inertia.visit(redirect);
            })
            .catch((error) => {
                serverSideError(error);
                setProcessing(false);
            });
    };

    return (
        <>
            <Head title="New Fabric Return" />
            <PageHeader
                title="New Fabric Return"
                description="Pick a supplier to start a draft return"
                buttons={<BackButton href={por.index()} label="Returns" />}
            />

            <PageContent>
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:undo-left-round-bold-duotone"
                                title="Supplier"
                                description="The return is created as a draft. You can add items, bilti and bill details on the next step."
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
                            <BackButton href={por.index()} label="Cancel" />
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

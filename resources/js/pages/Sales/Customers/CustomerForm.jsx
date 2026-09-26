import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, InputGroup, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import StyledSelect from '@/components/StyledSelect';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import customers from '@/routes/sales/customers';

const CustomerForm = () => {
    const {
        customer,
        errors: serverErrors,
        agents,
        brands,
        cities,
        discountTypes,
    } = usePage().props;
    const title = customer ? 'Edit Customer' : 'Add Customer';
    const [processing, setProcessing] = useState(false);
    const customer_agent = customer ? customer.agent : null;
    const {
        register,
        handleSubmit,
        setError,
        watch,
        control,
        formState: { errors },
    } = useForm({ defaultValues: customer });
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const agent = watch('agent', customer_agent);
    const credit = watch('credit');
    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        if (customer)
            Inertia.put(
                customers.update(customer['id']),
                post_data,
                options,
            );
        else Inertia.post(customers.store(), post_data, options);
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
                description={customer ? customer.name : 'Register a new customer account'}
                buttons={<BackButton href={customers.index()} label="Customers" />}
            />
            <PageContent>
                <ErrorPanel errors={serverErrors} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:user-id-bold-duotone"
                                title="Customer details"
                                description="Name in English and Urdu as printed on invoices, plus contact details."
                            >
                                <Row className="g-3">
                                    <Col md={6}>
                                        <FormField label="Customer name" htmlFor="name" required>
                                            <Form.Control
                                                id="name"
                                                {...register('name', { required: true })}
                                                isInvalid={errors.name}
                                                placeholder={'Full name'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Name in Urdu" htmlFor="name_urdu" required>
                                            <Form.Control
                                                id="name_urdu"
                                                className={'urdu'}
                                                dir="rtl"
                                                lang="ur"
                                                {...register('name_urdu', { required: true })}
                                                isInvalid={errors.name_urdu}
                                                placeholder={'اردو نام'}
                                            />
                                        </FormField>
                                    </Col>
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
                                description="Address and region in English and Urdu. City is used to group customers."
                            >
                                <Row className="g-3">
                                    <Col md={6}>
                                        <FormField label="Street address" htmlFor="address">
                                            <Form.Control
                                                id="address"
                                                {...register('address.address')}
                                                placeholder={'Shop / street / market'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Address in Urdu" htmlFor="address_urdu">
                                            <Form.Control
                                                id="address_urdu"
                                                className={'urdu'}
                                                dir="rtl"
                                                lang="ur"
                                                {...register('address.address_urdu')}
                                                placeholder={'دکان / گلی / بازار'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Region" htmlFor="region">
                                            <Form.Control
                                                id="region"
                                                {...register('address.region')}
                                                placeholder={'Area / region'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Region in Urdu" htmlFor="region_urdu">
                                            <Form.Control
                                                id="region_urdu"
                                                className={'urdu'}
                                                dir="rtl"
                                                lang="ur"
                                                {...register('address.region_urdu')}
                                                placeholder={'علاقہ'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="City" htmlFor="city" required>
                                            <Form.Select
                                                id="city"
                                                {...register('address.city', { required: true })}
                                                isInvalid={errors.address?.city}
                                            >
                                                <option value="">Select city</option>
                                                {cities &&
                                                    cities.map((city, index) => <option key={index}>{city.name}</option>)}
                                            </Form.Select>
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:card-bold-duotone"
                                title="Credit & discount"
                                description="Control whether this customer can buy on credit and their standard discount."
                            >
                                <Row className="g-3">
                                    <Col md={12}>
                                        <label className="hf-toggle" htmlFor="credit">
                                            <Form.Check type="switch" id="credit" className="m-0 p-0" {...register('credit')} />
                                            <span>
                                                <span className="hf-toggle__title">Allow credit</span>
                                                <span className="hf-toggle__desc">
                                                    {credit
                                                        ? 'Customer can buy on credit up to the limit below.'
                                                        : 'Cash only — invoices must be paid in full.'}
                                                </span>
                                            </span>
                                        </label>
                                    </Col>
                                    <Col md={6}>
                                        <FormField
                                            label="Credit limit"
                                            htmlFor="limit"
                                            required
                                            hint="Enter 0 for an unlimited credit limit."
                                        >
                                            <InputGroup className="hf-amount">
                                                <InputGroup.Text>Rs</InputGroup.Text>
                                                <Form.Control
                                                    id="limit"
                                                    inputMode="decimal"
                                                    {...register('limit', { required: true })}
                                                    isInvalid={errors.limit}
                                                    placeholder={'0'}
                                                />
                                            </InputGroup>
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Discount" htmlFor="discount" required>
                                            <InputGroup>
                                                <Form.Control
                                                    id="discount"
                                                    inputMode="decimal"
                                                    {...register('discount', { required: true })}
                                                    isInvalid={errors.discount}
                                                    placeholder={'0'}
                                                />
                                                <Form.Select
                                                    aria-label="per meter or on total"
                                                    className="flex-grow-0 w-auto"
                                                    {...register('discount_type', { required: true })}
                                                    isInvalid={errors.discount_type}
                                                >
                                                    {discountTypes &&
                                                        discountTypes.map((type, index) => (
                                                            <option key={index} value={type.value}>
                                                                {type.label}
                                                            </option>
                                                        ))}
                                                </Form.Select>
                                            </InputGroup>
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:users-group-rounded-bold-duotone"
                                title="Agent & commission"
                                description="Leave empty for direct customers. Selecting an agent asks for a commission rate per brand."
                            >
                                <FormField label="Agent">
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                defaultValue={customer_agent}
                                                options={agents}
                                                getOptionValue={(option) => option['id']}
                                                getOptionLabel={(option) => option['name']}
                                                placeholder="Direct customer (no agent)"
                                                isClearable
                                            />
                                        )}
                                        control={control}
                                        name={'agent'}
                                    />
                                </FormField>
                                {agent && agent.id && brands.length > 0 && (
                                    <div className="hf-rate-grid">
                                        {brands.map(({ id, name }) => {
                                            const field_name = 'commission_rate.brand_' + id;
                                            return (
                                                <FormField key={id} label={name} htmlFor={field_name} required>
                                                    <Form.Control
                                                        id={field_name}
                                                        inputMode="decimal"
                                                        {...register(field_name, { required: true })}
                                                        isInvalid={errors.commission_rate?.['brand_' + id]}
                                                        placeholder={'Rate'}
                                                    />
                                                </FormField>
                                            );
                                        })}
                                    </div>
                                )}
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={customers.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing}>
                                Save customer
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default CustomerForm;

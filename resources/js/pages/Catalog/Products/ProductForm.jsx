import React, { useEffect } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, InputGroup, Row } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import BackButton from '@/components/button/back';
import { FormSubmitButton } from '@/components/ui/button.tsx';
import { FormActions, FormField, FormSection } from '@/components/form/FormSection';
import products from '@/routes/catalog/products';

const ProductForm = () => {
    const { product, brands, finishes, vendors, errors: serverErrors } = usePage().props;
    const title = product ? 'Edit Product' : 'Add Product';
    const brand = product ? product.brand : null;
    const vendor = product ? product.vendor : null;
    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        control,
        watch,
        formState: { errors },
    } = useForm({ defaultValues: product });
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);
    const isBox = watch('is_box');
    const sendRequest = async (data) => {
        const post_data = {
            ...data,
            brand_id: data.brand ? data.brand.id : null,
            vendor_id: data.vendor ? data.vendor.id : null,
        };
        await new Promise((resolve) => {
            const options = { onFinish: () => resolve() };
            if (product) Inertia.put(products.update(product['id']), post_data, options);
            else Inertia.post(products.store(), post_data, options);
        });
    };

    return (
        <>
            <Head title={title} />
            <PageHeader
                title={title}
                description={product ? product.name : 'Add a new fabric to the catalog'}
                buttons={<BackButton href={products.index()} label={'Products List'} size={'sm'} />}
            />
            <PageContent>
                <ErrorPanel errors={serverErrors} />
                <form id={'productForm'} onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:box-minimalistic-bold-duotone"
                                title="Product details"
                                description="Name, brand and finish shown on invoices and reports."
                            >
                                <Row className="g-3">
                                    <Col md={8}>
                                        <FormField label="Product name" htmlFor="name" required>
                                            <Form.Control
                                                id="name"
                                                {...register('name', { required: true })}
                                                isInvalid={errors.name}
                                                placeholder={'e.g. Platinum Plus Suit'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={4}>
                                        <FormField label="Finish" htmlFor="finish" required>
                                            <Form.Select
                                                id="finish"
                                                {...register('finish', { required: true })}
                                                isInvalid={errors.finish}
                                            >
                                                <option value={''}>Select finish</option>
                                                {finishes &&
                                                    finishes.map(({ name }, index) => <option key={index}>{name}</option>)}
                                            </Form.Select>
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Brand">
                                            <Controller
                                                render={({ field }) => (
                                                    <StyledSelect
                                                        {...field}
                                                        onChange={(option) => {
                                                            clearErrors(['brand', 'brand_id']);
                                                            field.onChange(option);
                                                        }}
                                                        options={brands}
                                                        defaultValue={brand}
                                                        getOptionValue={(option) => option['id']}
                                                        getOptionLabel={(option) => option['name']}
                                                        placeholder="Select brand..."
                                                        isClearable
                                                    />
                                                )}
                                                control={control}
                                                name={'brand'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={6}>
                                        <FormField label="Vendor" hint="Optional — supplier this fabric is bought from.">
                                            <Controller
                                                render={({ field }) => (
                                                    <StyledSelect
                                                        {...field}
                                                        onChange={(option) => {
                                                            clearErrors(['vendor', 'vendor_id']);
                                                            field.onChange(option);
                                                        }}
                                                        options={vendors}
                                                        defaultValue={vendor}
                                                        getOptionValue={(option) => option['id']}
                                                        getOptionLabel={(option) => option['name']}
                                                        placeholder="Select vendor..."
                                                        isClearable
                                                    />
                                                )}
                                                control={control}
                                                name={'vendor'}
                                            />
                                        </FormField>
                                    </Col>
                                    <Col md={12}>
                                        <FormField label="Description" htmlFor="description">
                                            <Form.Control
                                                id="description"
                                                as="textarea"
                                                rows={2}
                                                {...register('description')}
                                                placeholder={'Notes about this product (optional)'}
                                            />
                                        </FormField>
                                    </Col>
                                </Row>
                            </FormSection>

                            <FormSection
                                icon="solar:tag-price-bold-duotone"
                                title="Packaging & pricing"
                                description="Box products are sold by size; others are sold per meter and per suit."
                            >
                                <Row className="g-3">
                                    <Col md={12}>
                                        <label className="hf-toggle" htmlFor="is_box">
                                            <Form.Check type="switch" id="is_box" className="m-0 p-0" {...register('is_box')} />
                                            <span>
                                                <span className="hf-toggle__title">Sold as a box</span>
                                                <span className="hf-toggle__desc">
                                                    Turn on for boxed products with a fixed size instead of a suit price.
                                                </span>
                                            </span>
                                        </label>
                                    </Col>
                                    <Col sm={6} lg={3}>
                                        <FormField label="Purchase price" htmlFor="cost" required>
                                            <InputGroup className="hf-amount">
                                                <InputGroup.Text>Rs</InputGroup.Text>
                                                <Form.Control
                                                    id="cost"
                                                    inputMode="decimal"
                                                    {...register('cost', { required: true })}
                                                    isInvalid={errors.cost}
                                                    placeholder={'0'}
                                                />
                                            </InputGroup>
                                        </FormField>
                                    </Col>
                                    <Col sm={6} lg={3}>
                                        <FormField label="Unit price" htmlFor="unit_price" required>
                                            <InputGroup className="hf-amount">
                                                <InputGroup.Text>Rs</InputGroup.Text>
                                                <Form.Control
                                                    id="unit_price"
                                                    inputMode="decimal"
                                                    {...register('unit_price', { required: true })}
                                                    isInvalid={errors.unit_price}
                                                    placeholder={'0'}
                                                />
                                            </InputGroup>
                                        </FormField>
                                    </Col>
                                    {isBox != 1 && (
                                        <Col sm={6} lg={3}>
                                            <FormField label="Suit price" htmlFor="suit_price" required>
                                                <InputGroup className="hf-amount">
                                                    <InputGroup.Text>Rs</InputGroup.Text>
                                                    <Form.Control
                                                        id="suit_price"
                                                        inputMode="decimal"
                                                        {...register('suit_price', { required: true })}
                                                        isInvalid={errors.suit_price}
                                                        placeholder={'0'}
                                                    />
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                    )}
                                    {isBox == 1 && (
                                        <Col sm={6} lg={3}>
                                            <FormField label="Size" htmlFor="size" required>
                                                <Form.Control
                                                    id="size"
                                                    inputMode="decimal"
                                                    {...register('size', { required: true })}
                                                    isInvalid={errors.size}
                                                    placeholder={'e.g. 5.5'}
                                                />
                                            </FormField>
                                        </Col>
                                    )}
                                </Row>
                            </FormSection>
                        </PanelBody>
                        <FormActions hint={<><span className="hf-required">*</span> Required fields</>}>
                            <BackButton href={products.index()} label="Cancel" />
                            <FormSubmitButton form={'productForm'} type={'submit'} value={'Save product'} control={control} />
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default ProductForm;

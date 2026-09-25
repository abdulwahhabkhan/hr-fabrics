import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelFooter } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import BackButton from '@/components/button/back';
import { FormSubmitButton } from '@/components/ui/button.tsx';
import products from '@/routes/catalog/products';

const ProductForm = () => {
    const { product, brands, finishes, vendors, errors: serverErrors } = usePage().props;
    const title = product ? "Edit Product" : "Add Product";
    const [processing, setProcessing] = useState(false);
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
    const isBox = watch("is_box");
    const sendRequest = async (data) => {
        const post_data = {
            ...data,
            brand_id: data.brand ? data.brand.id : null,
            vendor_id: data.vendor ? data.vendor.id : null,
        };
        setProcessing(true);
        await new Promise((resolve) => {
            const options = {
                onError: () => {
                    setProcessing(false);
                },
                onFinish: () => {
                    setProcessing(false);
                    resolve();
                },
            };
            if (product) Inertia.put(products.update(product["id"]), post_data, options);
            else Inertia.post(products.store(), post_data, options);
        });
    };

    return (
        <>
            <Head title={title} />
            <PageHeader
                title={title}
                buttons={
                    <>
                        <BackButton href={products.index()} label={"Products List"} size={"sm"} />
                    </>
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form action="" id={"productForm"} className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>
                                <Col sm={"6"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Brand:</Form.Label>
                                        <Controller
                                            render={({ field }) => (
                                                <StyledSelect
                                                    {...field}
                                                    onChange={(option) => {
                                                        clearErrors(["brand", "brand_id"]);
                                                        field.onChange(option);
                                                    }}
                                                    options={brands}
                                                    defaultValue={brand}
                                                    getOptionValue={(option) => option["id"]}
                                                    getOptionLabel={(option) => option["name"]}
                                                    isClearable
                                                />
                                            )}
                                            control={control}
                                            name={"brand"}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={"6"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Vendor:</Form.Label>
                                        <Controller
                                            render={({ field }) => (
                                                <StyledSelect
                                                    {...field}
                                                    onChange={(option) => {
                                                        clearErrors(["vendor", "vendor_id"]);
                                                        field.onChange(option);
                                                    }}
                                                    options={vendors}
                                                    defaultValue={vendor}
                                                    getOptionValue={(option) => option["id"]}
                                                    getOptionLabel={(option) => option["name"]}
                                                    isClearable
                                                />
                                            )}
                                            control={control}
                                            name={"vendor"}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={4}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Name:</Form.Label>
                                        <Form.Control
                                            {...register("name", { required: true })}
                                            isInvalid={errors.name}
                                            placeholder={"product name"}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={1}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Finish:</Form.Label>
                                        <Form.Select
                                            {...register("finish", { required: true })}
                                            isInvalid={errors.finish}
                                        >
                                            <option value={""}>Select Finish</option>
                                            {finishes &&
                                                finishes.map(function ({ name }, index) {
                                                    return <option key={index}>{name}</option>;
                                                })}
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                                <Col md={1}>
                                    <Form.Group className="mb-3">
                                        <Form.Check // prettier-ignore
                                            type="switch"
                                            id="custom-switch"
                                            label="Is Box?"
                                            {...register("is_box")}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Purchase Price:</Form.Label>
                                        <Form.Control
                                            {...register("cost", { required: true })}
                                            isInvalid={errors.cost}
                                            placeholder={"purchase price"}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Unit Price:</Form.Label>
                                        <Form.Control
                                            {...register("unit_price", { required: true })}
                                            isInvalid={errors.unit_price}
                                            placeholder={"unit price"}
                                        />
                                    </Form.Group>
                                </Col>
                                {isBox != 1 && (
                                    <>
                                        <Col md={2}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Suit Price:</Form.Label>
                                                <Form.Control
                                                    {...register("suit_price", { required: true })}
                                                    isInvalid={errors.suit_price}
                                                    placeholder={"suit price"}
                                                />
                                            </Form.Group>
                                        </Col>
                                    </>
                                )}
                                {isBox == 1 && (
                                    <>
                                        <Col md={2}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Size:</Form.Label>
                                                <Form.Control
                                                    {...register("size", { required: true })}
                                                    isInvalid={errors.size}
                                                    placeholder={"size"}
                                                />
                                            </Form.Group>
                                        </Col>
                                    </>
                                )}

                                <Col md={"12"}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Description:</Form.Label>
                                        <Form.Control {...register("description")} placeholder={"description"} />
                                    </Form.Group>
                                </Col>
                            </Row>
                        </form>
                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <BackButton href={products.index()} />
                        <FormSubmitButton form={"productForm"}  type={"submit"} value={"Save Changes"} control={control} />

                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default ProductForm;

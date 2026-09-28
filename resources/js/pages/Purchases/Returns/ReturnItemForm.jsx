import * as React from 'react';
import { useMemo, useRef, useState } from 'react';
import { Icon } from '@iconify/react';
import { Button, Col, Form, InputGroup, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { FormField, SegmentedControl } from '@/components/form/FormSection';
import { NumberFormat } from '@/util/NumberFormat';
import { notifyMessage, serverSideError, UNIT_BOX, UNIT_SUIT, UNIT_THAAN, usePackingUnits } from '@/util/util';
import returnAjax from '@/routes/ajax/return';

const UNIT_ICONS = {
    [UNIT_BOX]: 'solar:box-bold-duotone',
    [UNIT_SUIT]: 'solar:t-shirt-bold-duotone',
    [UNIT_THAAN]: 'solar:layers-bold-duotone',
};

const toNumber = (value) => parseFloat(value) || 0;

function Money({ value }) {
    return <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />;
}

export const ReturnItemForm = ({ returnId, onClose, setItems, products }) => {
    const packingUnits = usePackingUnits();
    const [product, setProduct] = useState(null);
    const [processing, setProcessing] = useState(false);
    const productRef = useRef(null);

    const {
        register,
        handleSubmit,
        setValue,
        setError,
        setFocus,
        watch,
        reset,
        control,
        formState: { errors },
    } = useForm({
        defaultValues: { product: null, unit: '', qty: '', size: '', rate: '' },
    });

    const { unit, qty, size, rate } = watch();

    const selectProduct = (selected) => {
        setProduct(selected);
        if (!selected) {
            return;
        }

        setValue('unit', selected.is_box ? UNIT_BOX : UNIT_THAAN, { shouldDirty: true });
        setValue('size', selected.size ?? '', { shouldDirty: true });
        setValue('rate', selected.purchased_price ?? '', { shouldDirty: true });
        setTimeout(() => setFocus('qty'), 0);
    };

    const resetForm = () => {
        setProduct(null);
        reset({ product: null, unit: '', qty: '', size: '', rate: '' });
        setTimeout(() => productRef.current?.focus(), 0);
    };

    const sendRequest = async (data) => {
        setProcessing(true);
        axios
            .post(returnAjax.item.save(returnId).url, { ...data, product_id: data?.product?.product_id })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({ title: 'Success', type: 'success', message: 'Item added' });
                resetForm();
            })
            .catch((error) => {
                const fieldErrors = error.response?.data?.errors ?? {};
                Object.entries(fieldErrors).forEach(([field, messages]) => {
                    setError(field, { type: 'server', message: messages[0] });
                });
                serverSideError(error);
            })
            .finally(() => {
                setProcessing(false);
            });
    };

    const units = packingUnits.filter((val) => (product?.is_box ? val === UNIT_BOX : val !== UNIT_BOX));

    const line = useMemo(() => {
        const meters = Math.trunc(toNumber(qty) * toNumber(size));
        const amount = unit === UNIT_BOX ? toNumber(qty) * toNumber(rate) : meters * toNumber(rate);

        return { meters, amount };
    }, [unit, qty, size, rate]);

    const stockError = errors.product?.message || errors.qty?.message;

    return (
        <Modal show={true} backdrop="static" size="lg" keyboard={true} onHide={onClose}>
            <form onSubmit={handleSubmit(sendRequest)}>
                <Modal.Header closeButton>
                    <Modal.Title>Add Item</Modal.Title>
                </Modal.Header>
                <Modal.Body className="hf-item-form">
                    <FormField label="Product" required>
                        <Controller
                            render={({ field }) => (
                                <StyledSelect
                                    {...field}
                                    options={products}
                                    onChange={(selected) => {
                                        field.onChange(selected);
                                        selectProduct(selected);
                                    }}
                                    getOptionValue={(option) => option['product_id']}
                                    getOptionLabel={(option) => option['product_info']}
                                    placeholder="Search product by name or finish..."
                                    isClearable
                                    autoFocus
                                    ref={productRef}
                                />
                            )}
                            control={control}
                            name="product"
                            rules={{ required: true }}
                        />
                        {errors.product?.type === 'required' && (
                            <div className="invalid-feedback d-block">Please select a product.</div>
                        )}
                    </FormField>

                    {product && (
                        <div className="hf-item-product">
                            <div className="d-flex flex-wrap align-items-center gap-2">
                                <span className="hf-cell-title">{product.name}</span>
                                {product.finish && <span className="hf-chip">{product.finish}</span>}
                                {product.purchased_price && (
                                    <span className="ms-auto hf-item-product__prices">
                                        Purchased <strong>Rs <Money value={product.purchased_price} /></strong>
                                    </span>
                                )}
                            </div>
                        </div>
                    )}

                    <Row className="g-3 mt-1">
                        <Col md={12}>
                            <FormField label="Unit" required>
                                <div>
                                    <SegmentedControl
                                        name="unit"
                                        register={(name) => register(name, { required: true })}
                                        options={units.map((value) => ({ value, label: value, icon: UNIT_ICONS[value] }))}
                                    />
                                </div>
                            </FormField>
                        </Col>
                        <Col sm={4} xs={6}>
                            <FormField label="Qty" htmlFor="qty" required>
                                <Form.Control
                                    id="qty"
                                    type="number"
                                    step="1"
                                    min={1}
                                    inputMode="numeric"
                                    {...register('qty', { required: true, min: 1 })}
                                    isInvalid={errors.qty}
                                    placeholder="0"
                                />
                            </FormField>
                        </Col>
                        <Col sm={4} xs={6}>
                            <FormField label={unit === UNIT_THAAN ? 'Meters per thaan' : 'Size'} htmlFor="size" required>
                                <InputGroup className="hf-amount">
                                    <Form.Control
                                        id="size"
                                        type="number"
                                        step="any"
                                        min={0}
                                        inputMode="decimal"
                                        {...register('size', { required: true })}
                                        isInvalid={errors.size}
                                        placeholder="0"
                                    />
                                    <InputGroup.Text>m</InputGroup.Text>
                                </InputGroup>
                            </FormField>
                        </Col>
                        <Col sm={4} xs={6}>
                            <FormField label={unit === UNIT_BOX ? 'Rate / box' : 'Rate / meter'} htmlFor="rate" required>
                                <InputGroup className="hf-amount">
                                    <InputGroup.Text>Rs</InputGroup.Text>
                                    <Form.Control
                                        id="rate"
                                        type="number"
                                        step="any"
                                        min={0}
                                        inputMode="decimal"
                                        {...register('rate', { required: true })}
                                        isInvalid={errors.rate}
                                        placeholder="0"
                                    />
                                </InputGroup>
                            </FormField>
                        </Col>
                    </Row>

                    {stockError && (
                        <div className="hf-item-warning">
                            <Icon icon="solar:danger-triangle-bold-duotone" />
                            {stockError}
                        </div>
                    )}

                    <div className="hf-item-preview">
                        <div>
                            <span>Meters</span>
                            <strong><NumberFormat displayType="text" value={line.meters} thousandSeparator /> m</strong>
                        </div>
                        <div className="is-total">
                            <span>Line total</span>
                            <strong>Rs <Money value={line.amount} /></strong>
                        </div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <span className="me-auto hf-field-hint mt-0">Enter saves and starts the next item.</span>
                    <Button variant="link" className="text-muted" onClick={resetForm}>
                        Clear
                    </Button>
                    <Button variant="white" onClick={onClose}>
                        Done
                    </Button>
                    <LoadingButton type="submit" processing={processing}>
                        Save & add next
                    </LoadingButton>
                </Modal.Footer>
            </form>
        </Modal>
    );
};

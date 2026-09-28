import * as React from 'react';
import { useMemo, useRef } from 'react';
import { Icon } from '@iconify/react';
import { Button, Col, Form, InputGroup, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { FormField, SegmentedControl } from '@/components/form/FormSection';
import { NumberFormat } from '@/util/NumberFormat';
import { UNIT_BOX, UNIT_SUIT, UNIT_THAAN, usePackingUnits } from '@/util/util';

const UNIT_ICONS = {
    [UNIT_BOX]: 'solar:box-bold-duotone',
    [UNIT_SUIT]: 'solar:t-shirt-bold-duotone',
    [UNIT_THAAN]: 'solar:layers-bold-duotone',
};

const EMPTY_ITEM = { product: null, unit: '', size: '', qty: '', rate: '', commission: '' };

const toNumber = (value) => parseFloat(value) || 0;

/**
 * Returned item editor. Items are kept on the page and saved together with the return,
 * so this only hands the entered values back through `onSave`.
 */
export const ReturnItemForm = ({ item, onClose, onSave, products, commission }) => {
    const packingUnits = usePackingUnits();
    const isEdit = Boolean(item);
    const productRef = useRef(null);

    const {
        register,
        handleSubmit,
        setValue,
        setFocus,
        watch,
        reset,
        control,
        formState: { errors },
    } = useForm({ defaultValues: item ? { ...EMPTY_ITEM, ...item } : EMPTY_ITEM });

    const { product, unit, size, qty, rate } = watch();

    const getCommission = (brand_id) => _.find(commission, (value, key) => 'brand_' + brand_id === key);

    const selectProduct = (selected) => {
        if (!selected) {
            return;
        }

        setValue('unit', selected.is_box ? UNIT_BOX : UNIT_THAAN, { shouldDirty: true });
        setValue('size', selected.size ?? '', { shouldDirty: true });
        setValue('commission', getCommission(selected.brand_id) ?? 0, { shouldDirty: true });
        setTimeout(() => setFocus('qty'), 0);
    };

    const sendRequest = (data) => {
        onSave({ ...data, name: data.product?.name ?? data.name });

        if (isEdit) {
            onClose();
            return;
        }

        reset(EMPTY_ITEM);
        setTimeout(() => productRef.current?.focus(), 0);
    };

    const units = packingUnits.filter((val) => (product?.is_box ? val === UNIT_BOX : val !== UNIT_BOX));
    const line = useMemo(() => {
        const meters = toNumber(qty) * toNumber(size);

        return { meters, amount: meters * toNumber(rate) };
    }, [qty, size, rate]);

    return (
        <Modal show={true} backdrop="static" size="lg" keyboard={true} onHide={onClose}>
            <form onSubmit={handleSubmit(sendRequest)}>
                <Modal.Header closeButton>
                    <Modal.Title>{isEdit ? 'Edit Returned Item' : 'Add Returned Item'}</Modal.Title>
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
                                    placeholder="Search product by name, finish or brand..."
                                    autoFocus={!isEdit}
                                    ref={productRef}
                                />
                            )}
                            control={control}
                            name="product"
                            rules={{ required: true }}
                        />
                        {errors.product && <div className="invalid-feedback d-block">Please select a product.</div>}
                    </FormField>

                    {product && (
                        <div className="hf-item-product">
                            <div className="d-flex flex-wrap align-items-center gap-2">
                                <span className="hf-cell-title">{product.name}</span>
                                {product.finish && <span className="hf-chip">{product.finish}</span>}
                                {product.unit_price !== undefined && (
                                    <span className="ms-auto hf-item-product__prices">
                                        {product.is_box ? 'Box' : 'Thaan'} <strong>Rs <NumberFormat displayType="text" value={product.unit_price} thousandSeparator /></strong>
                                        {!product.is_box && product.suit_price ? (
                                            <span className="hf-dot-sep ms-2">
                                                Suit <strong>Rs <NumberFormat displayType="text" value={product.suit_price} thousandSeparator /></strong>
                                            </span>
                                        ) : null}
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
                                {errors.unit && <div className="invalid-feedback d-block">Please pick a unit.</div>}
                            </FormField>
                        </Col>
                        <Col sm={commission ? 3 : 4} xs={6}>
                            <FormField label="Qty" htmlFor="qty" required>
                                <Form.Control
                                    id="qty"
                                    type="number"
                                    step="any"
                                    min={1}
                                    inputMode="decimal"
                                    {...register('qty', { required: true, min: 1 })}
                                    isInvalid={errors.qty}
                                    placeholder="0"
                                />
                            </FormField>
                        </Col>
                        <Col sm={commission ? 3 : 4} xs={6}>
                            <FormField label={unit === UNIT_THAAN ? 'Meters per thaan' : 'Size'} htmlFor="size" required>
                                <InputGroup className="hf-amount">
                                    <Form.Control
                                        id="size"
                                        type="number"
                                        step="any"
                                        min={0}
                                        inputMode="decimal"
                                        {...register('size', { required: true, min: 0.01 })}
                                        isInvalid={errors.size}
                                        placeholder="0"
                                    />
                                    <InputGroup.Text>m</InputGroup.Text>
                                </InputGroup>
                            </FormField>
                        </Col>
                        <Col sm={commission ? 3 : 4} xs={6}>
                            <FormField label="Rate / meter" htmlFor="rate" required hint="As charged on the invoice">
                                <InputGroup className="hf-amount">
                                    <InputGroup.Text>Rs</InputGroup.Text>
                                    <Form.Control
                                        id="rate"
                                        type="number"
                                        step="any"
                                        min={0}
                                        inputMode="decimal"
                                        {...register('rate', { required: true, min: 0.01 })}
                                        isInvalid={errors.rate}
                                        placeholder="0"
                                    />
                                </InputGroup>
                            </FormField>
                        </Col>
                        {commission && (
                            <Col sm={3} xs={6}>
                                <FormField label="Commission" htmlFor="commission" hint="Per meter, or % of amount">
                                    <Form.Control id="commission" {...register('commission')} placeholder="0" />
                                </FormField>
                            </Col>
                        )}
                    </Row>

                    <div className="hf-item-preview hf-item-preview--2">
                        <div>
                            <span>Meters</span>
                            <strong><NumberFormat displayType="text" value={line.meters} thousandSeparator decimalScale={2} /> m</strong>
                        </div>
                        <div className="is-total">
                            <span>Line total</span>
                            <strong>Rs <NumberFormat displayType="text" value={line.amount} thousandSeparator decimalScale={2} /></strong>
                        </div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    {!isEdit && (
                        <span className="me-auto hf-field-hint mt-0">
                            <Icon icon="solar:keyboard-bold-duotone" className="me-1" />
                            Enter adds the item and starts the next one.
                        </span>
                    )}
                    <Button variant="white" onClick={onClose}>
                        {isEdit ? 'Cancel' : 'Done'}
                    </Button>
                    <LoadingButton type="submit">
                        {isEdit ? 'Update item' : 'Add & next'}
                    </LoadingButton>
                </Modal.Footer>
            </form>
        </Modal>
    );
};

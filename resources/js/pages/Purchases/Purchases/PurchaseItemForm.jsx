import * as React from 'react';
import { useMemo, useRef, useState } from 'react';
import { Button, Col, Form, InputGroup, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { FormField, SegmentedControl } from '@/components/form/FormSection';
import { NumberFormat } from '@/util/NumberFormat';
import { notifyMessage, serverSideError, UNIT_BOX, UNIT_THAAN } from '@/util/util.jsx';
import poAjax from '@/routes/ajax/po';

const UNIT_ICONS = {
    [UNIT_BOX]: 'solar:box-bold-duotone',
    [UNIT_THAAN]: 'solar:layers-bold-duotone',
};

const toNumber = (value) => parseFloat(value) || 0;

function Money({ value }) {
    return <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />;
}

export const PurchaseItemForm = ({ item, receiptId, onClose, setItems, products }) => {
    const isEdit = item.id > 0;
    const itemDetail = _.find(products, { product_id: item.product_id ?? 0 }) ?? null;
    const [product, setProduct] = useState(itemDetail);
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
        defaultValues: {
            voucher_no: item.voucher_no ?? '',
            product: itemDetail,
            unit: item.unit ?? '',
            qty: item.qty ?? '',
            size: item.size ?? '',
            total_qty: item.total_qty ?? '',
            price: item.price ?? '',
        },
    });

    const { qty, size, total_qty, price } = watch();
    const isBox = parseInt(product?.is_box) === 1;

    const selectProduct = (selected) => {
        setProduct(selected);
        if (!selected) {
            return;
        }

        setValue('unit', selected.is_box ? UNIT_BOX : UNIT_THAAN, { shouldDirty: true });
        setTimeout(() => setFocus('qty'), 0);
    };

    const resetForm = () => {
        setProduct(null);
        reset({ voucher_no: '', product: null, unit: '', qty: '', size: '', total_qty: '', price: '' });
        setTimeout(() => productRef.current?.focus(), 0);
    };

    const sendRequest = async (data) => {
        setProcessing(true);
        axios({
            method: 'post',
            url: poAjax.items(receiptId).url,
            data: { ...data, order_id: item.order_id, item_id: item.id },
        })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({ title: 'Success', type: 'success', message: isEdit ? 'Item updated' : 'Item added' });

                if (isEdit) {
                    onClose();
                } else {
                    resetForm();
                }
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

    const line = useMemo(() => {
        const meters = isBox ? toNumber(qty) * toNumber(size) : toNumber(total_qty);
        const amount = isBox ? toNumber(qty) * toNumber(price) : meters * toNumber(price);

        return { meters, amount };
    }, [isBox, qty, size, total_qty, price]);

    const unitOptions = product ? [isBox ? UNIT_BOX : UNIT_THAAN] : [];

    return (
        <Modal show={true} backdrop="static" size="lg" keyboard={true} onHide={onClose}>
            <form onSubmit={handleSubmit(sendRequest)}>
                <Modal.Header closeButton>
                    <Modal.Title>{isEdit ? 'Edit Item' : 'Add Item'}</Modal.Title>
                </Modal.Header>
                <Modal.Body className="hf-item-form">
                    <Row className="g-3">
                        <Col sm={4}>
                            <FormField label="Voucher no" htmlFor="voucher_no" required>
                                <Form.Control
                                    id="voucher_no"
                                    {...register('voucher_no', { required: true })}
                                    isInvalid={errors.voucher_no}
                                    placeholder="Voucher no"
                                />
                            </FormField>
                        </Col>
                        <Col sm={8}>
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
                                            isClearable
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
                        </Col>
                    </Row>

                    {product && (
                        <div className="hf-item-product">
                            <div className="d-flex flex-wrap align-items-center gap-2">
                                <span className="hf-cell-title">{product.name}</span>
                                {product.finish && <span className="hf-chip">{product.finish}</span>}
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
                                        options={unitOptions.map((value) => ({ value, label: value, icon: UNIT_ICONS[value] }))}
                                    />
                                </div>
                            </FormField>
                        </Col>
                        <Col sm={4} xs={6}>
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
                        {isBox ? (
                            <Col sm={4} xs={6}>
                                <FormField label="Size" htmlFor="size" required>
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
                        ) : (
                            <Col sm={4} xs={6}>
                                <FormField label="Meters" htmlFor="total_qty" required>
                                    <InputGroup className="hf-amount">
                                        <Form.Control
                                            id="total_qty"
                                            type="number"
                                            step="any"
                                            min={0}
                                            inputMode="decimal"
                                            {...register('total_qty', { required: true })}
                                            isInvalid={errors.total_qty}
                                            placeholder="0"
                                        />
                                        <InputGroup.Text>m</InputGroup.Text>
                                    </InputGroup>
                                </FormField>
                            </Col>
                        )}
                        <Col sm={4} xs={6}>
                            <FormField label={isBox ? 'Price / box' : 'Price / meter'} htmlFor="price" required>
                                <InputGroup className="hf-amount">
                                    <InputGroup.Text>Rs</InputGroup.Text>
                                    <Form.Control
                                        id="price"
                                        type="number"
                                        step="any"
                                        min={0}
                                        inputMode="decimal"
                                        {...register('price', { required: true })}
                                        isInvalid={errors.price}
                                        placeholder="0"
                                    />
                                </InputGroup>
                            </FormField>
                        </Col>
                    </Row>

                    <div className="hf-item-preview">
                        <div>
                            <span>Meters</span>
                            <strong><NumberFormat displayType="text" value={line.meters} thousandSeparator decimalScale={2} /> m</strong>
                        </div>
                        <div className="is-total">
                            <span>Line total</span>
                            <strong>Rs <Money value={line.amount} /></strong>
                        </div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    {!isEdit && <span className="me-auto hf-field-hint mt-0">Enter saves and starts the next item.</span>}
                    {!isEdit && (
                        <Button variant="link" className="text-muted" onClick={resetForm}>
                            Clear
                        </Button>
                    )}
                    <Button variant="white" onClick={onClose}>
                        {isEdit ? 'Cancel' : 'Done'}
                    </Button>
                    <LoadingButton type="submit" processing={processing}>
                        {isEdit ? 'Update item' : 'Save & add next'}
                    </LoadingButton>
                </Modal.Footer>
            </form>
        </Modal>
    );
};

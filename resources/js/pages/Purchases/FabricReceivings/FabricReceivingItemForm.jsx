import * as React from 'react';
import { useRef, useState } from 'react';
import { Button, Col, Form, InputGroup, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { FormField, SegmentedControl } from '@/components/form/FormSection';
import { NumberFormat } from '@/util/NumberFormat';
import { notifyMessage, serverSideError, UNIT_BOX, UNIT_SUIT, UNIT_THAAN, usePackingUnits } from '@/util/util';
import fabricReceivingAjax from '@/routes/ajax/fabric-receiving';

const UNIT_ICONS = {
    [UNIT_BOX]: 'solar:box-bold-duotone',
    [UNIT_SUIT]: 'solar:t-shirt-bold-duotone',
    [UNIT_THAAN]: 'solar:layers-bold-duotone',
};

const toNumber = (value) => parseFloat(value) || 0;

export const FabricReceivingItemForm = ({ item, orderId, onClose, setItems, products }) => {
    const packingUnits = usePackingUnits();
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
        },
    });

    const { unit, qty, size, total_qty } = watch();
    const isThaan = unit === UNIT_THAAN;

    const selectProduct = (selected) => {
        setProduct(selected);
        if (!selected) {
            return;
        }

        setValue('unit', selected.is_box ? UNIT_BOX : UNIT_THAAN, { shouldDirty: true });
        setTimeout(() => setFocus('qty'), 0);
    };

    const resetForm = (data = {}) => {
        setProduct(null);
        reset({
            voucher_no: data.voucher_no ?? '',
            product: null,
            unit: '',
            qty: '',
            size: '',
            total_qty: '',
        });
        setTimeout(() => productRef.current?.focus(), 0);
    };

    const sendRequest = async (data) => {
        setProcessing(true);
        axios({
            method: 'post',
            url: fabricReceivingAjax.item(orderId).url,
            data: { ...data, order_id: item.order_id, item_id: item.id },
        })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({ title: 'Success', type: 'success', message: isEdit ? 'Item updated' : 'Item added' });

                if (isEdit) {
                    onClose();
                } else {
                    resetForm(data);
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

    const units = packingUnits.filter((val) => (product?.is_box ? val === UNIT_BOX : val !== UNIT_BOX));
    const meters = isThaan ? toNumber(total_qty) : toNumber(qty) * toNumber(size);

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
                                        options={units.map((value) => ({ value, label: value, icon: UNIT_ICONS[value] }))}
                                    />
                                </div>
                            </FormField>
                        </Col>
                        <Col sm={6} xs={6}>
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
                        {isThaan ? (
                            <Col sm={6} xs={6}>
                                <FormField label="Total meters" htmlFor="total_qty" required>
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
                        ) : (
                            <Col sm={6} xs={6}>
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
                        )}
                    </Row>

                    {errors.qty?.message && (
                        <div className="hf-item-warning">{errors.qty.message}</div>
                    )}

                    <div className="hf-item-preview">
                        <div className="is-total">
                            <span>Meters</span>
                            <strong><NumberFormat displayType="text" value={meters} thousandSeparator decimalScale={2} /> m</strong>
                        </div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    {!isEdit && <span className="me-auto hf-field-hint mt-0">Enter saves and starts the next item.</span>}
                    {!isEdit && (
                        <Button variant="link" className="text-muted" onClick={() => resetForm()}>
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

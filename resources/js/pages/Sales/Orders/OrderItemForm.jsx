import * as React from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { Button, Col, Form, InputGroup, Modal, Row, Spinner } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { FormField, SegmentedControl } from '@/components/form/FormSection';
import { NumberFormat } from '@/util/NumberFormat';
import { notifyMessage, serverSideError, UNIT_BOX, UNIT_SUIT, UNIT_THAAN, usePackingUnits } from '@/util/util';
import soAjax from '@/routes/ajax/so';

const UNIT_ICONS = {
    [UNIT_BOX]: 'solar:box-bold-duotone',
    [UNIT_SUIT]: 'solar:t-shirt-bold-duotone',
    [UNIT_THAAN]: 'solar:layers-bold-duotone',
};

const toNumber = (value) => parseFloat(value) || 0;

function Money({ value }) {
    return <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />;
}

/**
 * Stock available for the unit/size currently entered. Thaan stock is tracked
 * by meters only, so its size is ignored when matching.
 */
const matchStock = (stock, unit, size) =>
    stock.find((row) => row.unit === unit && (unit === UNIT_THAAN || row.size === toNumber(size)));

export const OrderItemForm = ({ orderId, item, onClose, setItems, products, commission, discountRate, discountPerMeter }) => {
    const packingUnits = usePackingUnits();
    const isEdit = item.id > 0;
    const itemDetail =
        _.find(products, { product_id: item.product_id ?? 0 }) ??
        (isEdit ? { product_id: item.product_id, name: item.product_name, product_info: item.product_name } : null);
    const [product, setProduct] = useState(itemDetail);
    const [stock, setStock] = useState([]);
    const [stockLoading, setStockLoading] = useState(false);
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
            product: itemDetail ?? null,
            unit: item.unit ?? '',
            qty: item.qty ?? '',
            size: item.size ?? '',
            price: item.price ?? '',
            commission: item.commission ?? '',
        },
    });

    const { unit, qty, size, price } = watch();

    const loadStock = (productId) => {
        setStock([]);
        if (!productId) {
            return;
        }

        setStockLoading(true);
        axios
            .get(soAjax.product.stock(productId).url)
            .then((res) => setStock(res.data.stock))
            .catch((error) => console.error(error))
            .finally(() => setStockLoading(false));
    };

    useEffect(() => {
        loadStock(itemDetail?.product_id);
    }, []);

    const getCommission = (brand_id) => _.find(commission, (rate, key) => 'brand_' + brand_id === key);

    const priceFor = (selected, selectedUnit) => {
        if (!selected) {
            return '';
        }

        return selectedUnit === UNIT_SUIT ? (selected.suit_price ?? '') : (selected.unit_price ?? '');
    };

    const selectProduct = (selected) => {
        setProduct(selected);
        loadStock(selected?.product_id);
        if (!selected) {
            return;
        }

        const defaultUnit = selected.is_box ? UNIT_BOX : UNIT_THAAN;
        setValue('unit', defaultUnit, { shouldDirty: true });
        setValue('size', selected.size ?? '', { shouldDirty: true });
        setValue('price', priceFor(selected, defaultUnit), { shouldDirty: true });
        setValue('commission', getCommission(selected.brand_id) ?? '', { shouldDirty: true });
        setTimeout(() => setFocus('qty'), 0);
    };

    const didMount = useRef(false);
    useEffect(() => {
        if (!didMount.current) {
            didMount.current = true;
            return;
        }
        if (unit && product) {
            setValue('price', priceFor(product, unit));
        }
    }, [unit]);

    const pickStock = (row) => {
        setValue('unit', row.unit, { shouldDirty: true });
        if (row.unit !== UNIT_THAAN) {
            setValue('size', row.size, { shouldDirty: true });
        }
    };

    const resetForm = () => {
        setProduct(null);
        setStock([]);
        reset({ product: null, unit: '', qty: '', size: '', price: '', commission: '' });
        setTimeout(() => productRef.current?.focus(), 0);
    };

    const sendRequest = async (data) => {
        setProcessing(true);
        axios({
            method: 'post',
            url: soAjax.item.add(orderId).url,
            data: {
                ...data,
                order_id: item.order_id,
                item_id: item.id,
                discount: discountRate,
            },
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

    const units = packingUnits.filter((val) => (product?.is_box ? val === UNIT_BOX : val !== UNIT_BOX));

    const line = useMemo(() => {
        const meters = toNumber(qty) * toNumber(size);
        const amount = unit === UNIT_THAAN ? meters * toNumber(price) : toNumber(qty) * toNumber(price);
        const discount = discountPerMeter ? meters * toNumber(discountRate) : amount * (toNumber(discountRate) / 100);

        return { meters, amount, discount, total: amount - discount };
    }, [unit, qty, size, price, discountPerMeter, discountRate]);

    const matchedStock = unit ? matchStock(stock, unit, size) : null;
    const overStock =
        product && unit && toNumber(qty) > 0 && !stockLoading &&
        (!matchedStock || toNumber(qty) > matchedStock.qty || line.meters > matchedStock.meters);

    return (
        <Modal show={true} backdrop="static" size="lg" keyboard={true} onHide={onClose}>
            <form onSubmit={handleSubmit(sendRequest)}>
                <Modal.Header closeButton>
                    <Modal.Title>{isEdit ? 'Edit Item' : 'Add Item'}</Modal.Title>
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

                    {product && (
                        <div className="hf-item-product">
                            <div className="d-flex flex-wrap align-items-center gap-2">
                                <span className="hf-cell-title">{product.name}</span>
                                {product.finish && <span className="hf-chip">{product.finish}</span>}
                                <span className="ms-auto hf-item-product__prices">
                                    {product.is_box ? (
                                        <>Box <strong>Rs <Money value={product.unit_price} /></strong></>
                                    ) : (
                                        <>
                                            Thaan <strong>Rs <Money value={product.unit_price} />/m</strong>
                                            <span className="hf-dot-sep ms-2">
                                                Suit <strong>Rs <Money value={product.suit_price} /></strong>
                                            </span>
                                        </>
                                    )}
                                </span>
                            </div>

                            <div className="hf-item-stock">
                                <span className="hf-item-stock__label">In stock</span>
                                {stockLoading && <Spinner animation="border" size="sm" />}
                                {!stockLoading && stock.length === 0 && (
                                    <span className="text-danger fw-semibold">No stock available</span>
                                )}
                                {!stockLoading &&
                                    stock.map((row) => (
                                        <button
                                            key={`${row.unit}-${row.size}`}
                                            type="button"
                                            className={cx('hf-stock-chip', { 'is-active': matchedStock === row })}
                                            onClick={() => pickStock(row)}
                                            title="Use this unit and size"
                                        >
                                            <strong>{row.unit}</strong>
                                            {row.unit !== UNIT_THAAN && <span>{row.size}m</span>}
                                            <span className="hf-stock-chip__qty">
                                                {row.qty} pcs · {row.meters}m
                                            </span>
                                        </button>
                                    ))}
                            </div>
                        </div>
                    )}

                    <Row className="g-3 mt-1">
                        <Col md={12}>
                            <FormField label="Unit" required>
                                <div>
                                    <SegmentedControl
                                        name="unit"
                                        register={register}
                                        options={units.map((value) => ({ value, label: value, icon: UNIT_ICONS[value] }))}
                                    />
                                </div>
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
                            <FormField label={unit === UNIT_THAAN ? 'Price / meter' : (unit ? `Price / ${unit}` : 'Price')} htmlFor="price" required>
                                <InputGroup className="hf-amount">
                                    <InputGroup.Text>Rs</InputGroup.Text>
                                    <Form.Control
                                        id="price"
                                        type="number"
                                        step="any"
                                        min={1}
                                        inputMode="decimal"
                                        {...register('price', { required: true, min: 1 })}
                                        isInvalid={errors.price}
                                        placeholder="0"
                                    />
                                </InputGroup>
                            </FormField>
                        </Col>
                        {commission && (
                            <Col sm={3} xs={6}>
                                <FormField label="Commission" htmlFor="commission" required>
                                    <Form.Control
                                        id="commission"
                                        type="number"
                                        step="any"
                                        min={0}
                                        {...register('commission', { required: true, min: 0 })}
                                        isInvalid={errors.commission}
                                        placeholder="0"
                                    />
                                </FormField>
                            </Col>
                        )}
                    </Row>

                    {(errors.qty?.message || overStock) && (
                        <div className="hf-item-warning">
                            <Icon icon="solar:danger-triangle-bold-duotone" />
                            {errors.qty?.message ||
                                (matchedStock
                                    ? `Only ${matchedStock.qty} pcs / ${matchedStock.meters}m available for this unit and size.`
                                    : 'No stock for this unit and size.')}
                        </div>
                    )}

                    <div className="hf-item-preview">
                        <div>
                            <span>Meters</span>
                            <strong><NumberFormat displayType="text" value={line.meters} thousandSeparator decimalScale={2} /> m</strong>
                        </div>
                        <div>
                            <span>Amount</span>
                            <strong>Rs <Money value={line.amount} /></strong>
                        </div>
                        <div>
                            <span>Discount {discountPerMeter ? `(${toNumber(discountRate)}/m)` : `(${toNumber(discountRate)}%)`}</span>
                            <strong>− Rs <Money value={line.discount} /></strong>
                        </div>
                        <div className="is-total">
                            <span>Line total</span>
                            <strong>Rs <Money value={line.total} /></strong>
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

import React, { useMemo, useState } from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Alert, Col, Form, InputGroup, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import { NumberFormat } from '@/util/NumberFormat';
import { DeleteAjax } from '@/components/Actions';
import { OrderItemForm } from '@/pages/Sales/Orders/OrderItemForm';
import { getSOCommission, getSOUnit, notifyMessage, ORDER_CLOSED } from '@/util/util';
import { confirmSwal } from '@/util/swal';
import ValidationErrors from '@/components/ValidationErrors';
import Back from '@/components/button/back';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import NoData from '@/components/NoData.jsx';
import { FormActions, FormField, OptionCards } from '@/components/form/FormSection';
import orders from '@/routes/sales/orders';
import soAjax from '@/routes/ajax/so';

const toNumber = (value) => parseFloat(value) || 0;

const PURCHASE_TYPE_OPTIONS = {
    'In Person': { icon: 'solar:shop-2-bold-duotone', description: 'Walk-in at the shop' },
    Online: { icon: 'solar:smartphone-2-bold-duotone', description: 'Phone / WhatsApp order' },
};

function Money({ value }) {
    return <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />;
}

function SummaryStat({ icon, label, children, tone }) {
    return (
        <div className={cx('hf-order-stat', tone && `is-${tone}`)}>
            <span className="hf-order-stat__icon"><Icon icon={icon} /></span>
            <div className="min-w-0">
                <div className="hf-order-stat__label">{label}</div>
                <div className="hf-order-stat__value">{children}</div>
            </div>
        </div>
    );
}

const OrderForm = () => {
    const {
        order,
        errors: serverErrors,
        products,
        items: orderItems,
        types,
        discountTypes,
    } = usePage().props;
    const customer = order.customer;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(orderItems.data);
    const [addItem, setAddItem] = useState(false);
    const [item, setItem] = useState({});
    const {
        register,
        handleSubmit,
        watch,
        formState: { errors, isDirty },
    } = useForm({ defaultValues: order });
    const { expenses, discount_on_total } = watch();
    const isLocked = order.status === ORDER_CLOSED;

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        Inertia.put(orders.update(order['id']), { ...data }, options);
    };
    const confirmRequest = async (data) => {
        if (items.length === 0) {
            notifyMessage({ title: 'No items', type: 'warning', message: 'Add at least one item before confirming.' });
            return;
        }

        const { isConfirmed } = await confirmSwal({
            title: 'Confirm this invoice?',
            text: 'Stock and the customer ledger will be updated and the invoice will be locked for editing.',
            confirmButtonText: 'Yes, confirm',
            confirmButtonStyle: 'success',
        });
        if (!isConfirmed) {
            return;
        }

        setProcessing(true);
        Inertia.put(orders.update(order['id']), { ...data, status: ORDER_CLOSED }, options);
    };

    const openItemForm = (row) => {
        setItem(row);
        setAddItem(true);
    };

    const addNewItem = () => {
        openItemForm({
            order_id: order.id,
            id: 0,
        });
    };

    const deleteItem = (id) => {
        axios({
            method: 'delete',
            url: soAjax.item.destroy(id).url,
        })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({
                    title: 'Success',
                    type: 'success',
                    message: 'Item removed',
                });
            })
            .catch((error) => {
                console.error(error);
            });
    };

    const totals = useMemo(() => {
        const sum = (key) => items.reduce((total, row) => total + toNumber(row[key]), 0);
        const subTotal = sum('total_amount');

        return {
            qty: sum('qty'),
            meters: sum('total_qty'),
            discount: sum('discount'),
            commission: sum('total_commission'),
            subTotal,
            net: subTotal - sum('discount') + toNumber(expenses) - toNumber(discount_on_total),
        };
    }, [items, expenses, discount_on_total]);

    const paymentOptions = [
        { value: 'Cash', label: 'Cash', icon: 'solar:wallet-money-bold-duotone', description: 'Marked as paid' },
        {
            value: 'Credit',
            label: 'Credit',
            icon: 'solar:card-bold-duotone',
            description: customer.credit ? 'Added to customer balance' : 'Not allowed for this customer',
            disabled: !customer.credit,
        },
    ];
    const purchaseTypeOptions = (types ?? []).map((type) => ({ value: type, label: type, ...PURCHASE_TYPE_OPTIONS[type] }));

    const hasLimit = customer.credit && customer.limit > 0;
    const availableBalance = customer.limit - (toNumber(customer.balance) + totals.net);

    return (
        <>
            <Head title={`Edit Invoice ${order.invoice_no ?? ''}`} />
            <PageHeader
                title={`Invoice ${order.invoice_no ?? '#' + order.id}`}
                description={
                    <>
                        {customer.name}
                        {customer.address?.city ? `, ${customer.address.city}` : ''}
                    </>
                }
                buttons={
                    <>
                        <Back href={orders.index()} label="Invoices" />
                        <PreviewButton href={orders.show(order.id)} size="sm" />
                    </>
                }
            />
            <PageContent>
                <ValidationErrors errors={serverErrors} />

                <div className="hf-order-summary mb-3">
                    <SummaryStat icon="solar:box-bold-duotone" label="Items">
                        {items.length}
                        <span className="hf-price-unit">
                            <NumberFormat displayType="text" value={totals.meters} thousandSeparator /> m
                        </span>
                    </SummaryStat>
                    <SummaryStat icon="solar:ticket-sale-bold-duotone" label="Item discount">
                        <Money value={totals.discount} />
                    </SummaryStat>
                    <SummaryStat icon="solar:cart-large-2-bold-duotone" label="Net total" tone="brand">
                        <span className="hf-currency">Rs</span>
                        <Money value={totals.net} />
                    </SummaryStat>
                    {customer.credit ? (
                        <SummaryStat
                            icon="solar:wallet-money-bold-duotone"
                            label={hasLimit ? 'Credit available' : 'Credit limit'}
                            tone={hasLimit && availableBalance < 0 ? 'danger' : undefined}
                        >
                            {hasLimit ? <Money value={availableBalance} /> : 'Unlimited'}
                        </SummaryStat>
                    ) : (
                        <SummaryStat icon="solar:wallet-money-bold-duotone" label="Credit" tone="danger">
                            Cash only
                        </SummaryStat>
                    )}
                </div>

                {hasLimit && availableBalance < 0 && (
                    <Alert variant="danger" className="note">
                        This invoice takes the customer over their credit limit by{' '}
                        <strong><Money value={Math.abs(availableBalance)} /></strong>.
                    </Alert>
                )}

                <Panel theme="default">
                    <PanelHeader
                        heading={
                            <>
                                Items <span className="hf-muted-value fw-normal">({items.length})</span>
                            </>
                        }
                        buttons={
                            !isLocked && (
                                <button type="button" className="btn btn-xs btn-theme" onClick={addNewItem}>
                                    <Icon icon="solar:add-bold-duotone" /> Add item
                                </button>
                            )
                        }
                    />
                    <PanelBody>
                        <div className="table-responsive">
                            <table className="table table-hover align-middle mb-0 hf-list-table hf-order-items">
                                <thead>
                                    <tr>
                                        <th className="w-1">#</th>
                                        <th>Product</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1 text-end">Qty</th>
                                        <th className="w-1 text-end">Meters</th>
                                        <th className="w-1 text-end">Price</th>
                                        <th className="w-1 text-end text-nowrap">{order.discount_label}</th>
                                        <th className="w-1 text-end">Total</th>
                                        <th className="w-1 text-end">Commission</th>
                                        {!isLocked && <th className="w-1 text-end">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((row, index) => {
                                        const { id, product_name, price, unit, qty, discount, size, total_qty, total_amount, commission, total_commission } = row;
                                        return (
                                            <tr key={id}>
                                                <td className="hf-muted-value">{index + 1}</td>
                                                <td>
                                                    {isLocked ? (
                                                        <span className="hf-cell-title">{product_name}</span>
                                                    ) : (
                                                        <button type="button" className="btn btn-link p-0 hf-cell-title hf-link text-start" onClick={() => openItemForm(row)}>
                                                            {product_name}
                                                        </button>
                                                    )}
                                                </td>
                                                <td className="text-nowrap">{getSOUnit(unit, size)}</td>
                                                <td className="num text-end hf-mono">{qty}</td>
                                                <td className="num text-end hf-mono">{total_qty}</td>
                                                <td className="num text-end hf-mono">{price}</td>
                                                <td className="num text-end hf-mono">
                                                    <NumberFormat displayType="text" value={discount} thousandSeparator />
                                                </td>
                                                <td className="num text-end hf-mono fw-semibold">
                                                    <NumberFormat displayType="text" value={total_amount} thousandSeparator />
                                                </td>
                                                <td className="num text-end hf-mono text-nowrap">
                                                    {getSOCommission(commission, total_commission)}
                                                </td>
                                                {!isLocked && (
                                                    <td className="text-end">
                                                        <div className="hf-row-actions">
                                                            <button
                                                                type="button"
                                                                className="hf-icon-btn hf-icon-btn--boxed"
                                                                title="Edit item"
                                                                aria-label={`Edit ${product_name}`}
                                                                onClick={() => openItemForm(row)}
                                                            >
                                                                <Icon icon="solar:pen-2-bold-duotone" />
                                                            </button>
                                                            <span className="hf-icon-btn hf-icon-btn--boxed is-danger" title="Remove item">
                                                                <DeleteAjax onDelete={deleteItem} id={id} />
                                                            </span>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                    })}
                                </tbody>
                                {items.length > 0 && (
                                    <tfoot>
                                        <tr>
                                            <th colSpan={3}>Total</th>
                                            <th className="num text-end hf-mono">{totals.qty}</th>
                                            <th className="num text-end hf-mono">
                                                <NumberFormat displayType="text" value={totals.meters} thousandSeparator />
                                            </th>
                                            <th />
                                            <th className="num text-end hf-mono"><Money value={totals.discount} /></th>
                                            <th className="num text-end hf-mono"><Money value={totals.subTotal} /></th>
                                            <th className="num text-end hf-mono">
                                                {totals.commission > 0 ? <Money value={totals.commission} /> : ''}
                                            </th>
                                            {!isLocked && <th />}
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>
                        {items.length === 0 && (
                            <div className="text-center">
                                <NoData label="No items added yet." />
                                {!isLocked && (
                                    <button type="button" className="btn btn-sm btn-theme mt-2" onClick={addNewItem}>
                                        <Icon icon="solar:add-bold-duotone" /> Add first item
                                    </button>
                                )}
                            </div>
                        )}
                    </PanelBody>
                </Panel>

                <form onSubmit={handleSubmit(sendRequest)}>
                    <Row className="g-3">
                        <Col lg={6}>
                            <Panel className="hf-order-card h-100 mb-0">
                                <PanelBody>
                                    <div className="hf-order-card__head">
                                        <span className="hf-form-section__icon"><Icon icon="solar:document-text-bold-duotone" /></span>
                                        <div>
                                            <h2 className="hf-form-section__title">Invoice details</h2>
                                            <p className="hf-form-section__desc">
                                                Created <Moment format={settings.FULL_DATE_FORMAT} date={order.created_at} />
                                            </p>
                                        </div>
                                    </div>
                                    <Row className="g-3">
                                        <Col sm={12}>
                                            <FormField label="Payment mode" required>
                                                <OptionCards
                                                    name="payment_mode"
                                                    register={(name) => register(name, { required: true })}
                                                    options={paymentOptions}
                                                    invalid={Boolean(errors.payment_mode)}
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={12}>
                                            <FormField label="Purchase type" required>
                                                <OptionCards
                                                    name="purchase_type"
                                                    register={(name) => register(name, { required: true })}
                                                    options={purchaseTypeOptions}
                                                    invalid={Boolean(errors.purchase_type)}
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={12}>
                                            <FormField label="Notes" htmlFor="expenses_detail" hint="Printed on the invoice, e.g. expense details.">
                                                <Form.Control
                                                    id="expenses_detail"
                                                    as="textarea"
                                                    rows={2}
                                                    {...register('expenses_detail')}
                                                    placeholder="Order notes"
                                                />
                                            </FormField>
                                        </Col>
                                    </Row>
                                </PanelBody>
                            </Panel>
                        </Col>
                        <Col lg={6}>
                            <Panel className="hf-order-card h-100 mb-0">
                                <PanelBody>
                                    <div className="hf-order-card__head">
                                        <span className="hf-form-section__icon"><Icon icon="solar:ticket-sale-bold-duotone" /></span>
                                        <div>
                                            <h2 className="hf-form-section__title">Discount & charges</h2>
                                            <p className="hf-form-section__desc">A new discount rate applies to items added after saving.</p>
                                        </div>
                                    </div>
                                    <Row className="g-3">
                                        <Col sm={12}>
                                            <FormField label="Discount rate" htmlFor="discount_rate">
                                                <InputGroup>
                                                    <Form.Control
                                                        id="discount_rate"
                                                        type="number"
                                                        step="any"
                                                        min={0}
                                                        {...register('discount_rate')}
                                                        placeholder="0"
                                                    />
                                                    <Form.Select
                                                        aria-label="Discount type"
                                                        className="flex-grow-0 w-auto"
                                                        {...register('discount_type', { required: true })}
                                                        isInvalid={errors.discount_type}
                                                    >
                                                        {discountTypes?.map((type) => (
                                                            <option key={type.value} value={type.value}>{type.label}</option>
                                                        ))}
                                                    </Form.Select>
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                        <Col sm={6}>
                                            <FormField label="Expenses" htmlFor="expenses">
                                                <InputGroup className="hf-amount">
                                                    <InputGroup.Text>Rs</InputGroup.Text>
                                                    <Form.Control id="expenses" type="number" step="any" min={0} {...register('expenses')} placeholder="0" />
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                        <Col sm={6}>
                                            <FormField label="Discount on total" htmlFor="discount_on_total">
                                                <InputGroup className="hf-amount">
                                                    <InputGroup.Text>Rs</InputGroup.Text>
                                                    <Form.Control id="discount_on_total" type="number" step="any" min={0} {...register('discount_on_total')} placeholder="0" />
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                    </Row>

                                    <dl className="hf-order-breakdown">
                                        <div><dt>Items subtotal</dt><dd><Money value={totals.subTotal} /></dd></div>
                                        <div><dt>Item discount</dt><dd>− <Money value={totals.discount} /></dd></div>
                                        <div><dt>Expenses</dt><dd>+ <Money value={toNumber(expenses)} /></dd></div>
                                        <div><dt>Discount on total</dt><dd>− <Money value={toNumber(discount_on_total)} /></dd></div>
                                        <div className="is-total"><dt>Net total</dt><dd>Rs <Money value={totals.net} /></dd></div>
                                    </dl>
                                </PanelBody>
                            </Panel>
                        </Col>
                    </Row>

                    <FormActions
                        className="hf-order-actions"
                        hint={
                            isDirty
                                ? <span className="text-warning fw-semibold"><Icon icon="solar:danger-circle-bold-duotone" /> Unsaved changes</span>
                                : <>{items.length} items · Net total <strong>Rs <Money value={totals.net} /></strong></>
                        }
                    >
                        <LoadingButton type="submit" variant="white" processing={processing}>
                            Save draft
                        </LoadingButton>
                        <LoadingButton processing={processing} onClick={handleSubmit(confirmRequest)}>
                            Confirm & close
                        </LoadingButton>
                    </FormActions>
                </form>

                {addItem && (
                    <OrderItemForm
                        orderId={order.id}
                        item={item}
                        onClose={() => setAddItem(false)}
                        setItems={setItems}
                        commission={order.agent_rate}
                        discountRate={order.discount_rate}
                        discountPerMeter={order.discount_type === 'fixed_per_meter'}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default OrderForm;

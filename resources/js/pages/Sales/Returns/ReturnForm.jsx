import React, { useMemo, useState } from 'react';
import { Icon } from '@iconify/react';
import round from 'lodash/round';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, InputGroup, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { ErrorPanel } from '@/components/panel/ErrorPanel';
import { settings } from '@/config/page-settings';
import { useForm } from 'react-hook-form';
import Moment from '@/components/Moment';
import { DeleteAjax } from '@/components/Actions';
import { ReturnItemForm } from './ReturnItemForm';
import { getTotalQty, getUnit, notifyMessage } from '@/util/util';
import { confirmSwal } from '@/util/swal';
import { NumberFormat } from '@/util/NumberFormat';
import { AttachFiles } from '@/components/File';
import BackButton from '@/components/button/back';
import NoData from '@/components/NoData.jsx';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import {
    FormActions,
    FormField,
    OptionCards,
} from '@/components/form/FormSection';
import returns from '@/routes/sales/returns';

const STATUS_OPEN = 0;
const STATUS_CLOSED = 1;

const toNumber = (value) => parseFloat(value) || 0;

function Money({ value }) {
    return (
        <NumberFormat
            displayType="text"
            value={value}
            thousandSeparator
            decimalScale={2}
        />
    );
}

function SummaryStat({ icon, label, children, tone }) {
    return (
        <div className={'hf-order-stat' + (tone ? ` is-${tone}` : '')}>
            <span className="hf-order-stat__icon">
                <Icon icon={icon} />
            </span>
            <div className="min-w-0">
                <div className="hf-order-stat__label">{label}</div>
                <div className="hf-order-stat__value">{children}</div>
            </div>
        </div>
    );
}

/**
 * Agent commission for a returned line: a "%" rate applies to the amount, otherwise it is per meter.
 */
const getAgentCommission = (meters, amount, commissionRate) => {
    const rate = String(commissionRate ?? '');
    if (!parseFloat(rate)) {
        return 0;
    }
    if (rate.includes('%')) {
        return round((amount * parseFloat(rate.replace('%', ''))) / 100, 2);
    }

    return round(meters * parseFloat(rate), 2);
};

const REFUND_OPTIONS = [
    {
        value: 'Cash',
        label: 'Cash',
        icon: 'solar:wallet-money-bold-duotone',
        description: 'Refunded from cash',
    },
    {
        value: 'Credit',
        label: 'Credit',
        icon: 'solar:card-bold-duotone',
        description: 'Adjusted in customer balance',
    },
];

const ReturnForm = () => {
    const {
        return: order,
        items: savedItems,
        errors: serverErrors,
        products,
        files,
        directory,
        morph_class,
    } = usePage().props;
    const customer = order.customer;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(savedItems ?? []);
    const [editing, setEditing] = useState(null);
    const [attachments, setAttachments] = useState(files ?? []);
    const {
        register,
        handleSubmit,
        watch,
        formState: { errors, isDirty },
    } = useForm({ defaultValues: order });
    const { discount, expenses } = watch();

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const saveData = (data, status) => {
        setProcessing(true);
        Inertia.put(
            returns.update(order.id),
            { ...data, items, status },
            options,
        );
    };

    const sendRequest = (data) => {
        if (items.length === 0) {
            notifyMessage({
                title: 'No items',
                type: 'warning',
                message: 'Add at least one returned item before saving.',
            });
            return;
        }
        saveData(data, STATUS_OPEN);
    };

    const confirmRequest = async (data) => {
        if (items.length === 0) {
            notifyMessage({
                title: 'No items',
                type: 'warning',
                message: 'Add at least one returned item before confirming.',
            });
            return;
        }

        const { isConfirmed } = await confirmSwal({
            title: 'Confirm this return?',
            text: 'Stock and the customer ledger will be updated and the return will be locked for editing.',
            confirmButtonText: 'Yes, confirm',
            confirmButtonStyle: 'success',
        });
        if (isConfirmed) {
            saveData(data, STATUS_CLOSED);
        }
    };

    const saveItem = (item) => {
        const totalQty = getTotalQty(item.unit, item.size, item.qty);
        const totalAmount = round(totalQty * toNumber(item.rate), 2);
        const row = {
            ...item,
            total_qty: totalQty,
            total_amount: totalAmount,
            total_commission: getAgentCommission(
                totalQty,
                totalAmount,
                item.commission,
            ),
        };

        setItems((current) => {
            if (editing?.index === undefined) {
                return [...current, row];
            }
            const next = [...current];
            next[editing.index] = row;

            return next;
        });
    };

    const deleteItem = (index) => {
        setItems((current) => current.filter((_row, i) => i !== index));
    };

    const totals = useMemo(() => {
        const sum = (key) =>
            items.reduce((total, row) => total + toNumber(row[key]), 0);
        const subTotal = sum('total_amount');

        return {
            meters: sum('total_qty'),
            commission: sum('total_commission'),
            subTotal,
            net: subTotal - toNumber(discount) + toNumber(expenses),
        };
    }, [items, discount, expenses]);

    const itemsChanged =
        JSON.stringify(items) !== JSON.stringify(savedItems ?? []);
    const hasUnsavedChanges = isDirty || itemsChanged;

    return (
        <>
            <Head title={`Edit Return ${order.invoice_no}`} />
            <PageHeader
                title={`Return ${order.invoice_no}`}
                description={
                    <>
                        {customer.name}
                        {customer.address?.city
                            ? `, ${customer.address.city}`
                            : ''}
                    </>
                }
                buttons={
                    <>
                        <BackButton href={returns.index()} label="Returns" />
                        <PreviewButton href={returns.show(order.id)} />
                    </>
                }
            />
            <PageContent>
                <ErrorPanel errors={serverErrors} />

                <div className="hf-order-summary mb-3">
                    <SummaryStat icon="solar:box-bold-duotone" label="Items">
                        {items.length}
                        <span className="hf-price-unit">
                            <NumberFormat
                                displayType="text"
                                value={totals.meters}
                                thousandSeparator
                                decimalScale={2}
                            />{' '}
                            m
                        </span>
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:hand-money-bold-duotone"
                        label="Agent commission"
                    >
                        <Money value={totals.commission} />
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:undo-left-round-bold-duotone"
                        label="Net refund"
                        tone="brand"
                    >
                        <span className="hf-currency">Rs</span>
                        <Money value={totals.net} />
                    </SummaryStat>
                </div>

                <Panel theme="default" className="hf-table-panel">
                    <PanelHeader
                        heading={
                            <>
                                Returned items{' '}
                                <span className="hf-muted-value fw-normal">
                                    ({items.length})
                                </span>
                            </>
                        }
                        buttons={
                            <button
                                type="button"
                                className="btn btn-xs btn-theme"
                                onClick={() => setEditing({})}
                            >
                                <Icon icon="solar:add-bold-duotone" /> Add item
                            </button>
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
                                        <th className="w-1 text-end">Meters</th>
                                        <th className="w-1 text-end">Rate</th>
                                        <th className="w-1 text-end">
                                            Commission
                                        </th>
                                        <th className="w-1 text-end">Total</th>
                                        <th className="w-1 text-end">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((row, index) => (
                                        <tr key={index}>
                                            <td className="hf-muted-value">
                                                {index + 1}
                                            </td>
                                            <td>
                                                <button
                                                    type="button"
                                                    className="btn btn-link p-0 hf-cell-title hf-link text-start"
                                                    onClick={() =>
                                                        setEditing({
                                                            index,
                                                            item: row,
                                                        })
                                                    }
                                                >
                                                    {row.name}
                                                </button>
                                            </td>
                                            <td className="text-nowrap">
                                                {getUnit(
                                                    row.unit,
                                                    row.size,
                                                    row.qty,
                                                )}
                                            </td>
                                            <td className="num text-end hf-mono">
                                                <NumberFormat
                                                    displayType="text"
                                                    value={row.total_qty}
                                                    thousandSeparator
                                                    decimalScale={2}
                                                />
                                            </td>
                                            <td className="num text-end hf-mono">
                                                <Money value={row.rate} />
                                            </td>
                                            <td className="num text-end hf-mono">
                                                <Money
                                                    value={row.total_commission}
                                                />
                                            </td>
                                            <td className="num text-end hf-mono fw-semibold">
                                                <Money
                                                    value={row.total_amount}
                                                />
                                            </td>
                                            <td className="text-end">
                                                <div className="hf-row-actions">
                                                    <button
                                                        type="button"
                                                        className="hf-icon-btn hf-icon-btn--boxed"
                                                        title="Edit item"
                                                        aria-label={`Edit ${row.name}`}
                                                        onClick={() =>
                                                            setEditing({
                                                                index,
                                                                item: row,
                                                            })
                                                        }
                                                    >
                                                        <Icon icon="solar:pen-2-bold-duotone" />
                                                    </button>
                                                    <span
                                                        className="hf-icon-btn hf-icon-btn--boxed is-danger"
                                                        title="Remove item"
                                                    >
                                                        <DeleteAjax
                                                            onDelete={
                                                                deleteItem
                                                            }
                                                            id={index}
                                                        />
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                {items.length > 0 && (
                                    <tfoot>
                                        <tr>
                                            <th colSpan={3}>Total</th>
                                            <th className="num text-end hf-mono">
                                                <NumberFormat
                                                    displayType="text"
                                                    value={totals.meters}
                                                    thousandSeparator
                                                    decimalScale={2}
                                                />
                                            </th>
                                            <th />
                                            <th className="num text-end hf-mono">
                                                <Money
                                                    value={totals.commission}
                                                />
                                            </th>
                                            <th className="num text-end hf-mono">
                                                <Money
                                                    value={totals.subTotal}
                                                />
                                            </th>
                                            <th />
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>
                        {items.length === 0 && (
                            <div className="text-center">
                                <NoData label="No returned items added yet." />
                                <button
                                    type="button"
                                    className="btn btn-sm btn-theme mt-2"
                                    onClick={() => setEditing({})}
                                >
                                    <Icon icon="solar:add-bold-duotone" /> Add
                                    first item
                                </button>
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
                                        <span className="hf-form-section__icon">
                                            <Icon icon="solar:document-text-bold-duotone" />
                                        </span>
                                        <div>
                                            <h2 className="hf-form-section__title">
                                                Return details
                                            </h2>
                                            <p className="hf-form-section__desc">
                                                Created{' '}
                                                <Moment
                                                    format={
                                                        settings.FULL_DATE_FORMAT
                                                    }
                                                    date={order.created_at}
                                                />
                                            </p>
                                        </div>
                                    </div>
                                    <Row className="g-3">
                                        <Col sm={12}>
                                            <FormField
                                                label="Order no"
                                                htmlFor="order_no"
                                                required
                                                hint="Invoice the goods were sold on."
                                            >
                                                <Form.Control
                                                    id="order_no"
                                                    {...register('order_no', {
                                                        required: true,
                                                        maxLength: 20,
                                                    })}
                                                    isInvalid={errors.order_no}
                                                    placeholder="e.g. 2609021"
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={12}>
                                            <FormField
                                                label="Refund mode"
                                                required
                                            >
                                                <OptionCards
                                                    name="payment_mode"
                                                    register={(name) =>
                                                        register(name, {
                                                            required: true,
                                                        })
                                                    }
                                                    options={REFUND_OPTIONS}
                                                    invalid={Boolean(
                                                        errors.payment_mode,
                                                    )}
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={6}>
                                            <FormField
                                                label="Discount"
                                                htmlFor="discount"
                                                hint="Subtracted from refund"
                                            >
                                                <InputGroup className="hf-amount">
                                                    <InputGroup.Text>
                                                        Rs
                                                    </InputGroup.Text>
                                                    <Form.Control
                                                        id="discount"
                                                        type="number"
                                                        step="any"
                                                        min={0}
                                                        {...register(
                                                            'discount',
                                                        )}
                                                        placeholder="0"
                                                    />
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                        <Col sm={6}>
                                            <FormField
                                                label="Expenses"
                                                htmlFor="expenses"
                                                hint="Added to refund"
                                            >
                                                <InputGroup className="hf-amount">
                                                    <InputGroup.Text>
                                                        Rs
                                                    </InputGroup.Text>
                                                    <Form.Control
                                                        id="expenses"
                                                        type="number"
                                                        step="any"
                                                        min={0}
                                                        {...register(
                                                            'expenses',
                                                        )}
                                                        placeholder="0"
                                                    />
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                        <Col sm={12}>
                                            <FormField
                                                label="Remarks"
                                                htmlFor="remarks"
                                                required
                                                hint="Reason for the return."
                                            >
                                                <Form.Control
                                                    id="remarks"
                                                    as="textarea"
                                                    rows={2}
                                                    {...register(
                                                        'info.remarks',
                                                        { required: true },
                                                    )}
                                                    isInvalid={
                                                        errors.info?.remarks
                                                    }
                                                    placeholder="e.g. Damaged thaan, colour mismatch"
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
                                        <span className="hf-form-section__icon">
                                            <Icon icon="solar:calculator-bold-duotone" />
                                        </span>
                                        <div>
                                            <h2 className="hf-form-section__title">
                                                Refund & attachments
                                            </h2>
                                            <p className="hf-form-section__desc">
                                                Net refund breakdown and
                                                supporting documents.
                                            </p>
                                        </div>
                                    </div>
                                    <dl className="hf-order-breakdown">
                                        <div>
                                            <dt>Items total</dt>
                                            <dd>
                                                <Money
                                                    value={totals.subTotal}
                                                />
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Discount</dt>
                                            <dd>
                                                −{' '}
                                                <Money
                                                    value={toNumber(discount)}
                                                />
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Expenses</dt>
                                            <dd>
                                                +{' '}
                                                <Money
                                                    value={toNumber(expenses)}
                                                />
                                            </dd>
                                        </div>
                                        <div className="is-total">
                                            <dt>Net refund</dt>
                                            <dd>
                                                Rs <Money value={totals.net} />
                                            </dd>
                                        </div>
                                    </dl>

                                    <div className="hf-field mt-3">
                                        <label className="form-label">
                                            Attachments
                                        </label>
                                        <AttachFiles
                                            directory={directory}
                                            morph_class={morph_class}
                                            morph_id={order.id}
                                            files={attachments}
                                            updateFile={(file) =>
                                                setAttachments((current) =>
                                                    current.concat(file),
                                                )
                                            }
                                            progress={setProcessing}
                                        />
                                    </div>
                                </PanelBody>
                            </Panel>
                        </Col>
                    </Row>

                    <FormActions
                        className="hf-order-actions"
                        hint={
                            hasUnsavedChanges ? (
                                <span className="text-warning fw-semibold">
                                    <Icon icon="solar:danger-circle-bold-duotone" />{' '}
                                    Unsaved changes
                                </span>
                            ) : (
                                <>
                                    {items.length} items · Net refund{' '}
                                    <strong>
                                        Rs <Money value={totals.net} />
                                    </strong>
                                </>
                            )
                        }
                    >
                        <LoadingButton
                            type="submit"
                            variant="white"
                            processing={processing}
                        >
                            Save draft
                        </LoadingButton>
                        <LoadingButton
                            processing={processing}
                            onClick={handleSubmit(confirmRequest)}
                        >
                            Confirm & close
                        </LoadingButton>
                    </FormActions>
                </form>

                {editing && (
                    <ReturnItemForm
                        item={editing.item}
                        onClose={() => setEditing(null)}
                        onSave={saveItem}
                        commission={order.agent_rate}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default ReturnForm;

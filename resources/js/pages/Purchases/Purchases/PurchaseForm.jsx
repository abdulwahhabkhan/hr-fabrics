import React, { useMemo, useState } from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import { NumberFormat } from '@/util/NumberFormat';
import { DeleteAjax } from '@/components/Actions';
import { PurchaseItemForm } from './PurchaseItemForm';
import { getPOUnit, notifyMessage, STATUS_OPEN } from '@/util/util';
import { confirmSwal } from '@/util/swal';
import ValidationErrors from '@/components/ValidationErrors';
import Back from '@/components/button/back';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import NoData from '@/components/NoData.jsx';
import SummaryStat from '@/components/SummaryStat.jsx';
import { FormActions, FormField } from '@/components/form/FormSection';
import pos from '@/routes/purchases/pos';
import poAjax from '@/routes/ajax/po';

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

const PurchaseForm = () => {
    const {
        receipt: order,
        errors: serverErrors,
        products,
        items: orderItems,
        status_open,
        status_close,
    } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(orderItems.data);
    const [addItem, setAddItem] = useState(false);
    const [item, setItem] = useState({});
    const {
        register,
        handleSubmit,
        formState: { errors, isDirty },
    } = useForm({ defaultValues: order });
    const isLocked = order.status !== STATUS_OPEN;

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        Inertia.put(pos.update(order['id']), { ...data, status: status_open }, options);
    };
    const confirmRequest = async (data) => {
        if (items.length === 0) {
            notifyMessage({
                title: 'No items',
                type: 'warning',
                message: 'Add at least one item before confirming.',
            });
            return;
        }

        const { isConfirmed } = await confirmSwal({
            title: 'Confirm this purchase?',
            text: 'Stock and the supplier ledger will be updated and the purchase will be locked for editing.',
            confirmButtonText: 'Yes, confirm',
            confirmButtonStyle: 'success',
        });
        if (!isConfirmed) {
            return;
        }

        setProcessing(true);
        Inertia.put(pos.update(order['id']), { ...data, status: status_close }, options);
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
            url: poAjax.item.destroy(id).url,
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
        const sum = (key) =>
            items.reduce((total, row) => total + toNumber(row[key]), 0);

        return {
            qty: sum('qty'),
            meters: sum('total_qty'),
            total: sum('total'),
        };
    }, [items]);

    return (
        <>
            <Head title={`Edit Purchase ${order.invoice_no ?? ''}`} />
            <PageHeader
                title={`Purchase ${order.invoice_no ?? '#' + order.id}`}
                description={order.supplier?.name}
                buttons={
                    <>
                        <Back href={pos.index()} label="Purchases" />
                        <PreviewButton href={pos.show(order.id)} size="sm" />
                    </>
                }
            />
            <PageContent>
                <ValidationErrors errors={serverErrors} />

                <div className="hf-order-summary mb-3">
                    <SummaryStat icon="solar:box-bold-duotone" label="Items">
                        {items.length}
                        <span className="hf-price-unit">
                            <NumberFormat
                                displayType="text"
                                value={totals.meters}
                                thousandSeparator
                            />{' '}
                            m
                        </span>
                    </SummaryStat>
                    <SummaryStat icon="solar:layers-bold-duotone" label="Qty">
                        <NumberFormat
                            displayType="text"
                            value={totals.qty}
                            thousandSeparator
                        />
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:cart-large-2-bold-duotone"
                        label="Total"
                        tone="brand"
                    >
                        <span className="hf-currency">Rs</span>
                        <Money value={totals.total} />
                    </SummaryStat>
                </div>

                <Panel theme="default" className="hf-table-panel">
                    <PanelHeader
                        heading={
                            <>
                                Items{' '}
                                <span className="hf-muted-value fw-normal">
                                    ({items.length})
                                </span>
                            </>
                        }
                        buttons={
                            !isLocked && (
                                <button
                                    type="button"
                                    className="btn btn-xs btn-theme"
                                    onClick={addNewItem}
                                >
                                    <Icon icon="solar:add-bold-duotone" /> Add
                                    item
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
                                        <th className="w-1">Voucher No</th>
                                        <th>Product</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1 text-end">Qty</th>
                                        <th className="w-1 text-end">Meters</th>
                                        <th className="w-1 text-end">Price</th>
                                        <th className="w-1 text-end">Total</th>
                                        {!isLocked && (
                                            <th className="w-1 text-end">
                                                Actions
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((row, index) => {
                                        const {
                                            id,
                                            product_name,
                                            price,
                                            voucher_no,
                                            unit,
                                            size,
                                            qty,
                                            total,
                                            total_qty,
                                        } = row;
                                        return (
                                            <tr key={id}>
                                                <td className="hf-muted-value">
                                                    {index + 1}
                                                </td>
                                                <td className="text-nowrap hf-mono">
                                                    {voucher_no}
                                                </td>
                                                <td>
                                                    {isLocked ? (
                                                        <span className="hf-cell-title">
                                                            {product_name}
                                                        </span>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            className="btn btn-link p-0 hf-cell-title hf-link text-start"
                                                            onClick={() =>
                                                                openItemForm(row)
                                                            }
                                                        >
                                                            {product_name}
                                                        </button>
                                                    )}
                                                </td>
                                                <td className="text-nowrap">
                                                    {getPOUnit(unit, size, qty)}
                                                </td>
                                                <td className="num text-end hf-mono">
                                                    {qty}
                                                </td>
                                                <td className="num text-end hf-mono">
                                                    {total_qty}
                                                </td>
                                                <td className="num text-end hf-mono">
                                                    {price}
                                                </td>
                                                <td className="num text-end hf-mono fw-semibold">
                                                    <NumberFormat
                                                        displayType="text"
                                                        value={total}
                                                        thousandSeparator
                                                    />
                                                </td>
                                                {!isLocked && (
                                                    <td className="text-end">
                                                        <div className="hf-row-actions">
                                                            <button
                                                                type="button"
                                                                className="hf-icon-btn hf-icon-btn--boxed"
                                                                title="Edit item"
                                                                aria-label={`Edit ${product_name}`}
                                                                onClick={() =>
                                                                    openItemForm(row)
                                                                }
                                                            >
                                                                <Icon icon="solar:pen-2-bold-duotone" />
                                                            </button>
                                                            <span
                                                                className="hf-icon-btn hf-icon-btn--boxed is-danger"
                                                                title="Remove item"
                                                            >
                                                                <DeleteAjax
                                                                    onDelete={deleteItem}
                                                                    id={id}
                                                                />
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
                                            <th colSpan={4}>Total</th>
                                            <th className="num text-end hf-mono">
                                                {totals.qty}
                                            </th>
                                            <th className="num text-end hf-mono">
                                                <NumberFormat
                                                    displayType="text"
                                                    value={totals.meters}
                                                    thousandSeparator
                                                />
                                            </th>
                                            <th />
                                            <th className="num text-end hf-mono">
                                                <Money value={totals.total} />
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
                                    <button
                                        type="button"
                                        className="btn btn-sm btn-theme mt-2"
                                        onClick={addNewItem}
                                    >
                                        <Icon icon="solar:add-bold-duotone" />{' '}
                                        Add first item
                                    </button>
                                )}
                            </div>
                        )}
                    </PanelBody>
                </Panel>

                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-order-card mb-0">
                        <PanelBody>
                            <div className="hf-order-card__head">
                                <span className="hf-form-section__icon">
                                    <Icon icon="solar:document-text-bold-duotone" />
                                </span>
                                <div>
                                    <h2 className="hf-form-section__title">
                                        Purchase details
                                    </h2>
                                    <p className="hf-form-section__desc">
                                        Created{' '}
                                        <Moment
                                            format={settings.FULL_DATE_FORMAT}
                                            date={order.created_at}
                                        />
                                    </p>
                                </div>
                            </div>
                            <Row className="g-3">
                                <Col sm={4}>
                                    <FormField label="Bill no" htmlFor="bill_no" required>
                                        <Form.Control
                                            id="bill_no"
                                            isInvalid={errors.bill_no}
                                            {...register('bill_no', { required: true })}
                                            placeholder="Bill no"
                                        />
                                    </FormField>
                                </Col>
                                <Col sm={4}>
                                    <FormField label="Lot no" htmlFor="lot_no" required>
                                        <Form.Control
                                            id="lot_no"
                                            isInvalid={errors.lot_no}
                                            {...register('lot_no', { required: true })}
                                            placeholder="Lot no"
                                        />
                                    </FormField>
                                </Col>
                                <Col sm={4}>
                                    <FormField label="Bilti no" htmlFor="bilti_no" hint="Taken from the fabric receiving.">
                                        <Form.Control
                                            id="bilti_no"
                                            readOnly
                                            {...register('bilti_no')}
                                        />
                                    </FormField>
                                </Col>
                                <Col sm={12}>
                                    <FormField label="Remarks" htmlFor="remarks">
                                        <Form.Control
                                            id="remarks"
                                            as="textarea"
                                            rows={2}
                                            isInvalid={errors.remarks}
                                            {...register('remarks')}
                                            placeholder="Purchase notes"
                                        />
                                    </FormField>
                                </Col>
                            </Row>
                        </PanelBody>
                    </Panel>

                    <FormActions
                        className="hf-order-actions"
                        hint={
                            isDirty ? (
                                <span className="text-warning fw-semibold">
                                    <Icon icon="solar:danger-circle-bold-duotone" />{' '}
                                    Unsaved changes
                                </span>
                            ) : (
                                <>
                                    {items.length} items · Total{' '}
                                    <strong>
                                        Rs <Money value={totals.total} />
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

                {addItem && (
                    <PurchaseItemForm
                        item={item}
                        receiptId={order.id}
                        onClose={() => setAddItem(false)}
                        setItems={setItems}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default PurchaseForm;

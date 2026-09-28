import React, { useMemo, useState } from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Col, Form, InputGroup, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import { NumberFormat } from '@/util/NumberFormat';
import { DeleteAjax } from '@/components/Actions';
import { ReturnItemForm } from './ReturnItemForm';
import { notifyMessage } from '@/util/util.jsx';
import { confirmSwal } from '@/util/swal';
import ValidationErrors from '@/components/ValidationErrors';
import Back from '@/components/button/back';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import NoData from '@/components/NoData.jsx';
import SummaryStat from '@/components/SummaryStat.jsx';
import { FormActions, FormField } from '@/components/form/FormSection';
import por from '@/routes/purchases/por';
import porAjax from '@/routes/ajax/por';

const RETURN_CLOSED = 1;

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

const ReturnForm = () => {
    const { por: porData, errors: serverErrors, products } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(porData.items_with_product ?? []);
    const [addItem, setAddItem] = useState(false);
    const {
        register,
        handleSubmit,
        watch,
        formState: { errors, isDirty },
    } = useForm({ defaultValues: porData });
    const { expenses, discount } = watch();
    const isLocked = porData.status === RETURN_CLOSED;

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        Inertia.put(por.update(porData.id), { ...data, info: { ...data.info } }, options);
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
            title: 'Confirm this return?',
            text: 'Stock and the supplier ledger will be updated and the return will be locked for editing.',
            confirmButtonText: 'Yes, confirm',
            confirmButtonStyle: 'success',
        });
        if (!isConfirmed) {
            return;
        }

        setProcessing(true);
        Inertia.put(
            por.update(porData.id),
            { ...data, info: { ...data.info }, status: RETURN_CLOSED },
            options,
        );
    };

    const deleteItem = (itemId) => {
        axios({
            method: 'delete',
            url: porAjax.item.destroy({ return: porData.id, item: itemId }).url,
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
        const subTotal = sum('total_amount');

        return {
            qty: sum('qty'),
            meters: sum('total_qty'),
            subTotal,
            net: subTotal - toNumber(discount) + toNumber(expenses),
        };
    }, [items, expenses, discount]);

    return (
        <>
            <Head title={`Edit Return ${porData.invoice_no ?? ''}`} />
            <PageHeader
                title={`Return ${porData.invoice_no ?? '#' + porData.id}`}
                description={porData.supplier?.name}
                buttons={
                    <>
                        <Back href={por.index()} label="Returns" />
                        <PreviewButton href={por.show(porData.id)} size="sm" />
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
                        label="Net total"
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
                                    onClick={() => setAddItem(true)}
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
                                        <th>Product</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1 text-end">Qty</th>
                                        <th className="w-1 text-end">Meters</th>
                                        <th className="w-1 text-end">Rate</th>
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
                                            rate,
                                            unit,
                                            qty,
                                            total_qty,
                                            total_amount,
                                        } = row;
                                        return (
                                            <tr key={id}>
                                                <td className="hf-muted-value">
                                                    {index + 1}
                                                </td>
                                                <td>
                                                    <span className="hf-cell-title">
                                                        {product_name}
                                                    </span>
                                                </td>
                                                <td className="text-nowrap">
                                                    {unit}
                                                </td>
                                                <td className="num text-end hf-mono">
                                                    {qty}
                                                </td>
                                                <td className="num text-end hf-mono">
                                                    {total_qty}
                                                </td>
                                                <td className="num text-end hf-mono">
                                                    <NumberFormat
                                                        displayType="text"
                                                        value={rate}
                                                        thousandSeparator
                                                    />
                                                </td>
                                                <td className="num text-end hf-mono fw-semibold">
                                                    <Money value={total_amount} />
                                                </td>
                                                {!isLocked && (
                                                    <td className="text-end">
                                                        <div className="hf-row-actions">
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
                                            <th colSpan={3}>Total</th>
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
                                                <Money value={totals.subTotal} />
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
                                        onClick={() => setAddItem(true)}
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
                                                    format={settings.FULL_DATE_FORMAT}
                                                    date={porData.created_at}
                                                />
                                            </p>
                                        </div>
                                    </div>
                                    <Row className="g-3">
                                        <Col sm={6}>
                                            <FormField label="Bilti no" htmlFor="bilti_no" required>
                                                <Form.Control
                                                    id="bilti_no"
                                                    {...register('bilti_no', { required: true })}
                                                    isInvalid={errors.bilti_no}
                                                    placeholder="Bilti no"
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={6}>
                                            <FormField label="Bill no" htmlFor="bill_no" required>
                                                <Form.Control
                                                    id="bill_no"
                                                    {...register('bill_no', { required: true })}
                                                    isInvalid={errors.bill_no}
                                                    placeholder="Bill no"
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={12}>
                                            <FormField label="Remarks" htmlFor="remarks" required>
                                                <Form.Control
                                                    id="remarks"
                                                    as="textarea"
                                                    rows={2}
                                                    {...register('info.remarks', { required: true })}
                                                    isInvalid={errors.info?.remarks}
                                                    placeholder="Return notes"
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
                                            <Icon icon="solar:ticket-sale-bold-duotone" />
                                        </span>
                                        <div>
                                            <h2 className="hf-form-section__title">
                                                Discount & charges
                                            </h2>
                                            <p className="hf-form-section__desc">
                                                Applied to the return total.
                                            </p>
                                        </div>
                                    </div>
                                    <Row className="g-3">
                                        <Col sm={6}>
                                            <FormField label="Expenses" htmlFor="expenses" required>
                                                <InputGroup className="hf-amount">
                                                    <InputGroup.Text>Rs</InputGroup.Text>
                                                    <Form.Control
                                                        id="expenses"
                                                        type="number"
                                                        step="any"
                                                        min={0}
                                                        {...register('expenses', { required: true })}
                                                        isInvalid={errors.expenses}
                                                        placeholder="0"
                                                    />
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                        <Col sm={6}>
                                            <FormField label="Discount" htmlFor="discount" required>
                                                <InputGroup className="hf-amount">
                                                    <InputGroup.Text>Rs</InputGroup.Text>
                                                    <Form.Control
                                                        id="discount"
                                                        type="number"
                                                        step="any"
                                                        min={0}
                                                        {...register('discount', { required: true })}
                                                        isInvalid={errors.discount}
                                                        placeholder="0"
                                                    />
                                                </InputGroup>
                                            </FormField>
                                        </Col>
                                    </Row>

                                    <dl className="hf-order-breakdown">
                                        <div>
                                            <dt>Items subtotal</dt>
                                            <dd>
                                                <Money value={totals.subTotal} />
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Discount</dt>
                                            <dd>
                                                − <Money value={toNumber(discount)} />
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Expenses</dt>
                                            <dd>
                                                + <Money value={toNumber(expenses)} />
                                            </dd>
                                        </div>
                                        <div className="is-total">
                                            <dt>Net total</dt>
                                            <dd>
                                                Rs <Money value={totals.net} />
                                            </dd>
                                        </div>
                                    </dl>
                                </PanelBody>
                            </Panel>
                        </Col>
                    </Row>

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
                                    {items.length} items · Net total{' '}
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

                {addItem && (
                    <ReturnItemForm
                        returnId={porData.id}
                        onClose={() => setAddItem(false)}
                        setItems={setItems}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default ReturnForm;

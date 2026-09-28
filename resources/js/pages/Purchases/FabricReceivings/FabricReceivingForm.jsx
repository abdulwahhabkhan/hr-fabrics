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
import { FabricReceivingItemForm } from './FabricReceivingItemForm';
import { getPOUnit, notifyMessage } from '@/util/util';
import { AttachFiles } from '@/components/File';
import ValidationErrors from '@/components/ValidationErrors';
import Back from '@/components/button/back';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import NoData from '@/components/NoData.jsx';
import SummaryStat from '@/components/SummaryStat.jsx';
import { FormActions, FormField } from '@/components/form/FormSection';
import fabricReceivings from '@/routes/purchases/fabric-receivings';
import fabricReceivingAjax from '@/routes/ajax/fabric-receiving';

const toNumber = (value) => parseFloat(value) || 0;

const FabricReceivingForm = () => {
    const {
        stock: order,
        directory,
        morph_class,
        errors: serverErrors,
        files,
        products,
        items: orderItems,
        status_list,
    } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(orderItems.data);
    const [addItem, setAddItem] = useState(false);
    const [item, setItem] = useState({});
    const [voucherFiles, setVoucherFiles] = useState(files);

    const {
        register,
        handleSubmit,
        formState: { errors, isDirty },
    } = useForm({ defaultValues: order });

    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        Inertia.put(
            fabricReceivings.update(order['id']),
            { ...data, info: { ...data.info }, files: voucherFiles },
            options,
        );
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

    const addVoucherFile = (file) => {
        setVoucherFiles(voucherFiles.concat(file));
    };

    const deleteItem = (id) => {
        axios({
            method: 'delete',
            url: fabricReceivingAjax.item.destroy(id).url,
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
        };
    }, [items]);

    return (
        <>
            <Head title={`Edit Receiving ${order.invoice_no ?? ''}`} />
            <PageHeader
                title={`Receiving ${order.invoice_no ?? '#' + order.id}`}
                description={order.supplier?.name}
                buttons={
                    <>
                        <Back href={fabricReceivings.index()} label="Receivings" />
                        <PreviewButton href={fabricReceivings.show(order.id)} size="sm" />
                    </>
                }
            />
            <PageContent>
                <ValidationErrors errors={serverErrors} />

                <div className="hf-order-summary mb-3">
                    <SummaryStat icon="solar:box-bold-duotone" label="Items">
                        {items.length}
                    </SummaryStat>
                    <SummaryStat icon="solar:layers-bold-duotone" label="Qty">
                        <NumberFormat
                            displayType="text"
                            value={totals.qty}
                            thousandSeparator
                        />
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:ruler-bold-duotone"
                        label="Meters"
                        tone="brand"
                    >
                        <NumberFormat
                            displayType="text"
                            value={totals.meters}
                            thousandSeparator
                        />
                        <span className="hf-price-unit">m</span>
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
                            <button
                                type="button"
                                className="btn btn-xs btn-theme"
                                onClick={addNewItem}
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
                                        <th className="w-1">Voucher No</th>
                                        <th>Product</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1 text-end">Qty</th>
                                        <th className="w-1 text-end">Meters</th>
                                        <th className="w-1 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((row, index) => {
                                        const {
                                            id,
                                            product_name,
                                            voucher_no,
                                            unit,
                                            size,
                                            qty,
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
                                                    <button
                                                        type="button"
                                                        className="btn btn-link p-0 hf-cell-title hf-link text-start"
                                                        onClick={() => openItemForm(row)}
                                                    >
                                                        {product_name}
                                                    </button>
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
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>
                        {items.length === 0 && (
                            <div className="text-center">
                                <NoData label="No items added yet." />
                                <button
                                    type="button"
                                    className="btn btn-sm btn-theme mt-2"
                                    onClick={addNewItem}
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
                                                Receiving details
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
                                        <Col sm={6}>
                                            <FormField label="Bilti no" htmlFor="bilti_no">
                                                <Form.Control
                                                    id="bilti_no"
                                                    isInvalid={errors.bilti_no}
                                                    {...register('bilti_no')}
                                                    placeholder="Bilti no"
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={6}>
                                            <FormField label="Lot no" htmlFor="lot_no">
                                                <Form.Control
                                                    id="lot_no"
                                                    isInvalid={errors.lot_no}
                                                    {...register('lot_no')}
                                                    placeholder="Lot no"
                                                />
                                            </FormField>
                                        </Col>
                                        <Col sm={12}>
                                            <FormField
                                                label="Status"
                                                htmlFor="status"
                                                required
                                                hint="Bilti and lot no are required to close the receiving."
                                            >
                                                <Form.Select
                                                    id="status"
                                                    {...register('status', { required: true })}
                                                    isInvalid={errors.status}
                                                >
                                                    <option value="">Select status</option>
                                                    {status_list?.map((value) => (
                                                        <option key={value} value={value}>
                                                            {value}
                                                        </option>
                                                    ))}
                                                </Form.Select>
                                            </FormField>
                                        </Col>
                                        <Col sm={12}>
                                            <FormField label="Comments" htmlFor="comments">
                                                <Form.Control
                                                    id="comments"
                                                    as="textarea"
                                                    rows={2}
                                                    {...register('info.comments')}
                                                    placeholder="Receiving notes"
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
                                            <Icon icon="solar:paperclip-bold-duotone" />
                                        </span>
                                        <div>
                                            <h2 className="hf-form-section__title">
                                                Attachments
                                            </h2>
                                            <p className="hf-form-section__desc">
                                                Bilti, vouchers and other documents.
                                            </p>
                                        </div>
                                    </div>
                                    <AttachFiles
                                        morph_class={morph_class}
                                        morph_id={order.id}
                                        directory={directory}
                                        files={voucherFiles}
                                        updateFile={addVoucherFile}
                                        progress={setProcessing}
                                    />
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
                                    {items.length} items · Meters{' '}
                                    <strong>
                                        <NumberFormat
                                            displayType="text"
                                            value={totals.meters}
                                            thousandSeparator
                                        />
                                    </strong>
                                </>
                            )
                        }
                    >
                        <LoadingButton type="submit" processing={processing}>
                            Save changes
                        </LoadingButton>
                    </FormActions>
                </form>

                {addItem && (
                    <FabricReceivingItemForm
                        item={item}
                        orderId={order.id}
                        onClose={() => setAddItem(false)}
                        setItems={setItems}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default FabricReceivingForm;

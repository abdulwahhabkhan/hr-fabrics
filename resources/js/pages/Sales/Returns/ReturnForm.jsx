import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { ErrorPanel } from '@/components/panel/ErrorPanel';
import { settings } from '@/config/page-settings';
import { useForm } from 'react-hook-form';
import Moment from '@/components/Moment';
import { DeleteAjax } from '@/components/Actions';
import { ReturnItemForm } from './ReturnItemForm';
import { getTotalQty, getUnit } from '@/util/util';
import { NumberFormat } from '@/util/NumberFormat';
import { FileUpload } from '@/components/File';
import BackButton from '@/components/button/back';
import round from 'lodash';
import NoData from '@/components/NoData.jsx';
import PreviewButton from '@/components/button/PreviewButton.jsx';

const ReturnForm = () => {
    const {
        return: order,
        errors: serverErrors,
        products,
        customers,
        file_info,
        customer: return_customer,
    } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(order.items ?? []);
    const [itemsLoading, setItemsLoading] = useState(false);
    const [addItem, setAddItem] = useState(false);
    const [file, setFile] = useState(file_info);
    const {
        register,
        handleSubmit,
        setError,
        control,
        formState: { errors },
    } = useForm({ defaultValues: order });
    const options = {
        onError: () => {
            setProcessing(false);
        },
    };
    const saveData = (data, status) => {
        data.info = { ...data.info, file: file };
        const post_data = { ...data, items: items, status: status };
        setProcessing(true);
        Inertia.put(
            route('sales.returns.update', order['id']),
            post_data,
            options,
        );
    };

    const sendRequest = async (data) => {
        saveData(data, 0);
    };

    const confirmRequest = async (data) => {
        saveData(data, 1);
    };
    const getAgentCommission = (qty, amount, commission_rate) => {
        let commission = parseFloat(commission_rate);
        if (!commission) {
            return 0;
        }
        if (commission_rate.search('%') != -1) {
            commission_rate = commission_rate.replace('%', '');
            return round(
                (parseFloat(amount) * parseFloat(commission_rate)) / 100,
                2,
            );
        } else {
            return round(parseFloat(qty) * parseFloat(commission_rate), 2);
        }
    };

    const updateItem = () => {
        setAddItem(true);
    };

    const saveItem = (item) => {
        setItemsLoading(true);
        let newItems = [...items];
        let totalQty = getTotalQty(item.unit, item.size, item.qty);
        let totalAmount = totalQty * item.rate;
        let totalCommission = getAgentCommission(
            totalQty,
            totalAmount,
            item.commission,
        );
        newItems.push({
            ...item,
            total_qty: totalQty,
            total_amount: totalAmount,
            total_commission: totalCommission,
        });
        setItems(newItems);
        setItemsLoading(false);
    };

    const handleClose = () => {
        setAddItem(false);
    };

    const deleteItem = (index) => {
        let orderItems = [...items];
        let item = orderItems[index];
        if (item) {
            orderItems.splice(index, 1);
            setItems(orderItems);
        }
    };

    useEffect(() => {}, [serverErrors]);

    const canModify = order.status == 'open';

    return (
        <>
            <Head title="Sales Return Update" />
            <PageHeader
                title="Sales Return Update"
                buttons={
                    <>
                        <PreviewButton
                            href={route('sales.returns.show', order.id)}
                        />
                        <BackButton
                            label={'Returns'}
                            href={route('sales.returns.index')}
                        />
                    </>
                }
            />
            <PageContent>
                <Panel theme={'default'}>
                    <PanelHeader
                        heading={
                            <>
                                Order Information : {order.invoice_no} &nbsp;
                                &nbsp;
                                <Moment
                                    format={settings.FULL_DATE_FORMAT}
                                    date={order.created_at}
                                />
                            </>
                        }
                        buttons={
                            <>
                                {order.id && (
                                    <LoadingButton
                                        variant="primary"
                                        className={'btn-xs'}
                                        processing={processing}
                                        onClick={handleSubmit(confirmRequest)}
                                    >
                                        Confirm & close
                                    </LoadingButton>
                                )}

                                <LoadingButton
                                    variant="primary"
                                    className={'btn-xs'}
                                    processing={processing}
                                    onClick={handleSubmit(sendRequest)}
                                >
                                    Save Changes
                                </LoadingButton>
                            </>
                        }
                    />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <Row>
                            <Col lg={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Customer:</Form.Label>
                                    <Form.Control
                                        defaultValue={
                                            order.customer.name +
                                            ' ' +
                                            order.customer.address.city
                                        }
                                        readOnly={true}
                                        size={'sm'}
                                        placeholder={'customer name'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Order No:</Form.Label>
                                    <Form.Control
                                        size={'sm'}
                                        control={control}
                                        {...register('order_no', {
                                            required: true,
                                        })}
                                        isInvalid={errors.order_no}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Payment Mode:</Form.Label>
                                    <Form.Select
                                        className={''}

                                        {...register('payment_mode', {
                                            required: true,
                                        })}
                                        size={'sm'}
                                    >
                                        <option value={''}>Select</option>
                                        <option>Cash</option>
                                        <option>Credit</option>
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col lg={8}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Remarks:</Form.Label>
                                    <br />
                                    <Form.Control
                                        isInvalid={
                                            errors.info ? errors.info : ''
                                        }
                                        {...register('info.remarks', {
                                            required: true,
                                        })}
                                        size={'sm'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Discount:</Form.Label>
                                    <Form.Control
                                        size={'sm'}
                                        control={control}
                                        {...register('discount', {
                                            required: true,
                                        })}
                                        isInvalid={errors.discount}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Expenses:</Form.Label>
                                    <Form.Control
                                        size={'sm'}
                                        control={control}
                                        {...register('expenses', {
                                            required: true,
                                        })}
                                        isInvalid={errors.expenses}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={file ? 8 : 12}>
                                <FileUpload
                                    directory={'so-return'}
                                    progress={setProcessing}
                                    updated={setFile}
                                />
                            </Col>
                            {file && (
                                <Col md={4}>
                                    <img
                                        src={
                                            file.file_thumbnail_url ??
                                            file.thumbnail_url ??
                                            file.file_thumbnail
                                        }
                                        className={'height-150'}
                                        alt={file.file_name}
                                    />
                                </Col>
                            )}
                        </Row>
                    </PanelBody>
                </Panel>
                <Panel theme={'default'}>
                    <PanelHeader
                        heading={'Order Items'}
                        buttons={
                            <>
                                <button
                                    className="btn btn-xs  btn-primary"
                                    onClick={() => updateItem({})}
                                >
                                    <Icon icon={'solar:add-bold-duotone'} /> Add
                                    Item
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <table className={'table table-bordered table-hover'}>
                            <thead>
                                <tr>
                                    <th width={'40'}>#</th>
                                    <th>Product</th>
                                    <th width={'120px'}>Unit</th>
                                    <th width={'70px'} className={'num'}>
                                        Qty
                                    </th>
                                    <th width={'70px'} className={'num'}>
                                        Rate
                                    </th>
                                    <th width={'100px'} className={'num'}>
                                        Commission
                                    </th>
                                    <th width={'100px'} className={'num'}>
                                        Total
                                    </th>
                                    <th width={'100'} className={'actions'}>
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {items &&
                                    items.map((row, index) => {
                                        const {
                                            id,
                                            name,
                                            rate,
                                            unit,
                                            size,
                                            qty,
                                            total_qty,
                                            total_commission,
                                            total_amount,
                                        } = row;
                                        return (
                                            <tr key={index}>
                                                <td>{index + 1}</td>
                                                <td>{name}</td>
                                                <td>
                                                    {getUnit(unit, size, qty)}
                                                </td>
                                                <td className={'num'}>
                                                    {total_qty}
                                                </td>
                                                <td className={'num'}>
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={rate}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={'num'}>
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={total_commission}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={'num'}>
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={total_amount}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={'actions'}>
                                                    <DeleteAjax
                                                        onDelete={deleteItem}
                                                        id={index}
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                        {items && items.length === 0 && (
                            <NoData label="No return items added." />
                        )}
                    </PanelBody>
                </Panel>
                {addItem && (
                    <ReturnItemForm
                        show={addItem}
                        onClose={handleClose}
                        onSave={saveItem}
                        loading={itemsLoading}
                        commission={order.agent_rate}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default ReturnForm;

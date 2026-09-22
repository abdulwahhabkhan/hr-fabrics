import React, { useEffect, useState } from 'react';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import { NumberFormat } from '@/util/NumberFormat';
import { DeleteAjax } from '@/components/Actions';
import { StoreTransferLoadStockForm } from '@/pages/Stock/StoreTransfers/StoreTransferLoadStockForm';
import {
    getSOUnit,
    notifyMessage,
    ORDER_CLOSED,
    serverSideError,
    UNIT_BOX,
} from '@/util/util';
import ValidationErrors from '@/components/ValidationErrors';
import NoData from '@/components/NoData.jsx';
import BackButton from '@/components/button/back.tsx';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import { Icon } from '@iconify/react';

const STORE_TRANSFER_TYPE_STORE = 'store';

const StoreTransferForm = () => {
    const {
        storeTransfer,
        errors: serverErrors,
        products,
        items: transferItems,
        types,
        paymentModes,
    } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(transferItems.data);
    const [basketTotal, setBasketTotal] = useState(0);
    const [itemsLoading, setItemsLoading] = useState(false);
    const [addItem, setAddItem] = useState(false);
    const [item, setItem] = useState({});
    const [warning, setWarning] = useState(false);
    const {
        handleSubmit,
        register,
        watch,
        formState: { errors },
    } = useForm({ defaultValues: storeTransfer });
    const { expenses, discount_on_total } = watch();
    const netTotal =
        basketTotal +
        (parseFloat(expenses) || 0) -
        (parseFloat(discount_on_total) || 0);
    const options = {
        onError: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        Inertia.put(
            route('stocks.store-transfers.update', storeTransfer['id']),
            data,
            options,
        );
    };
    const confirmRequest = async (data) => {
        const post_data = { ...data, status: ORDER_CLOSED };
        setProcessing(true);
        Inertia.put(
            route('stocks.store-transfers.update', storeTransfer['id']),
            post_data,
            options,
        );
    };

    const updateItem = (item) => {
        setItem(item);
        setAddItem(true);
    };
    const saveItem = (item) => {
        setItemsLoading(true);

        axios({
            method: 'post',
            url: route('stocks.store-transfers.item', storeTransfer.id),
            data: item,
        })
            .then((res) => {
                setWarning(false);
                setItems(res.data.items);
                notifyMessage({
                    title: 'Success',
                    type: 'success',
                    message: 'Item saved successfully',
                });
                setAddItem(false);
            })
            .finally(() => {
                setItemsLoading(false);
            })
            .catch((error) => {
                setItemsLoading(false);
                const { status } = error.response;
                if (status === 422) {
                    setWarning(true);
                } else {
                    serverSideError(error);
                }
            });
    };

    const saveLoadedStock = (payload) => {
        setItemsLoading(true);

        axios({
            method: 'post',
            url: route('stocks.store-transfers.items.bulk', storeTransfer.id),
            data: payload,
        })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({
                    title: 'Success',
                    type: 'success',
                    message: 'Stock loaded successfully',
                });
                setAddItem(false);
            })
            .finally(() => {
                setItemsLoading(false);
            })
            .catch((error) => {
                serverSideError(error);
            });
    };

    const handleClose = () => {
        setAddItem(false);
    };

    const deleteItem = (id) => {
        axios({
            method: 'delete',
            url: route('stocks.store-transfers.item.destroy', id),
        })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({
                    title: 'Success',
                    type: 'success',
                    message: 'Item deleted successfully',
                });
            })
            .finally(() => {
                setItemsLoading(false);
            })
            .catch((error) => {
                console.error(error);
            });
    };

    useEffect(() => {
        getTotal();
    }, [items]);

    const getTotal = () => {
        setBasketTotal(items.reduce((s, item) => s + item.total_amount, 0));
    };

    return (
        <>
            <Head title="Store Transfer Update" />
            <PageHeader
                title="Store Transfer Update"
                buttons={
                    <>
                        <PreviewButton
                            href={route(
                                'stocks.store-transfers.show',
                                storeTransfer.id,
                            )}
                        />
                        <BackButton
                            href={route('stocks.store-transfers.index')}
                        />
                    </>
                }
            />
            <PageContent>
                <Panel theme={'default'}>
                    <PanelHeader
                        heading={
                            <>
                                Store Transfer : &nbsp;
                                <Moment
                                    format={settings.FULL_DATE_FORMAT}
                                    date={storeTransfer.created_at}
                                />
                            </>
                        }
                        buttons={
                            <>
                                {storeTransfer.is_opened && (
                                    <>
                                        <LoadingButton
                                            variant="theme"
                                            className={'btn-xs'}
                                            processing={processing}
                                            onClick={handleSubmit(
                                                confirmRequest,
                                            )}
                                        >
                                            Confirm & close
                                        </LoadingButton>
                                        <LoadingButton
                                            variant="theme"
                                            className={'btn-xs'}
                                            processing={processing}
                                            onClick={handleSubmit(sendRequest)}
                                        >
                                            Save Changes
                                        </LoadingButton>
                                    </>
                                )}
                            </>
                        }
                    />
                    <PanelBody>
                        <ValidationErrors errors={serverErrors} />
                        <Row>
                            <Col lg={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>To Store:</Form.Label>
                                    <Form.Control
                                        defaultValue={
                                            storeTransfer.account.name +
                                            ' ' +
                                            (storeTransfer.account.address
                                                ?.city ?? '')
                                        }
                                        readOnly={true}
                                        size={'sm'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Transfer No:</Form.Label>
                                    <Form.Control
                                        defaultValue={storeTransfer.transfer_no}
                                        readOnly={true}
                                        size={'sm'}
                                    />
                                </Form.Group>
                            </Col>

                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Payment Mode:</Form.Label>
                                    <Form.Select
                                        size={'sm'}
                                        {...register('payment_mode')}
                                    >
                                        <option value="">Payment Mode</option>
                                        {paymentModes.map((mode) => (
                                            <option
                                                key={mode.id}
                                                value={mode.id}
                                            >
                                                {mode.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Expenses:</Form.Label>
                                    <Form.Control
                                        type={'number'}
                                        size={'sm'}
                                        {...register('expenses')}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Discount on Total:</Form.Label>
                                    <Form.Control
                                        type={'number'}
                                        size={'sm'}
                                        {...register('discount_on_total')}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col lg={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Notes:</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={1}
                                        size={'sm'}
                                        {...register('notes')}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>
                <Panel theme={'default'}>
                    <PanelHeader
                        heading={'Transfer Items'}
                        buttons={
                            <>
                                <button
                                    className="btn btn-xs  btn-outline-theme"
                                    title={'Total'}
                                >
                                    <Icon
                                        icon={'solar:cart-large-2-bold-duotone'}
                                    />{' '}
                                    Total: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={basketTotal}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>
                                <button
                                    className="btn btn-xs  btn-outline-theme"
                                    title={'Net Total'}
                                >
                                    <Icon
                                        icon={'solar:cart-large-2-bold-duotone'}
                                    />{' '}
                                    Net Total: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={netTotal}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>
                                {storeTransfer.is_opened && (
                                    <>
                                        <button
                                            className="btn btn-xs  btn-primary"
                                            onClick={() =>
                                                updateItem({
                                                    store_transfer_id:
                                                        storeTransfer.id,
                                                    id: 0,
                                                })
                                            }
                                        >
                                            <Icon
                                                icon={'solar:add-bold-duotone'}
                                            />{' '}
                                            Add Item Update
                                        </button>
                                    </>
                                )}
                            </>
                        }
                    />
                    <PanelBody>
                        <table className={'table table-bordered table-hover'}>
                            <thead>
                                <tr>
                                    <th className="w-1">#</th>
                                    <th>Product</th>
                                    <th className="w-1">Unit</th>
                                    <th className={'num'}>Qty</th>
                                    <th className={'num'}>Meters</th>
                                    <th className={'num'}>Expense</th>
                                    <th className={'num'}>Price</th>
                                    <th className={'num'}>Total</th>
                                    <th className={'w-1 actions'}></th>
                                </tr>
                            </thead>
                            <tbody>
                                {items &&
                                    items.map((row, index) => {
                                        const {
                                            id,
                                            product_name,
                                            price,
                                            expense,
                                            unit,
                                            qty,
                                            size,
                                            total_qty,
                                            total_amount,
                                        } = row;
                                        return (
                                            <tr key={index}>
                                                <td className="w-1">
                                                    {index + 1}
                                                </td>
                                                <td>
                                                    <span
                                                        className={
                                                            'text-inverse'
                                                        }
                                                    >
                                                        {product_name}
                                                    </span>
                                                </td>
                                                <td className="w-1">
                                                    {getSOUnit(unit, size)}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {qty}
                                                </td>
                                                <td className={'num'}>
                                                    {total_qty}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {expense}/
                                                    {unit === UNIT_BOX
                                                        ? unit
                                                        : 'm'}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {price}
                                                </td>
                                                <td className={'num w-1'}>
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={total_amount}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={'w-1 actions'}>
                                                    {storeTransfer.is_opened && (
                                                        <>
                                                            <DeleteAjax
                                                                onDelete={
                                                                    deleteItem
                                                                }
                                                                id={id}
                                                            />
                                                        </>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                        {items.length === 0 && (
                            <NoData label="No store transfer items added." />
                        )}
                    </PanelBody>
                </Panel>

                <StoreTransferLoadStockForm
                    show={addItem}
                    onClose={handleClose}
                    onSave={saveLoadedStock}
                    loading={itemsLoading}
                    products={products.data}
                    storeTransferId={storeTransfer.id}
                />
            </PageContent>
        </>
    );
};

export default StoreTransferForm;

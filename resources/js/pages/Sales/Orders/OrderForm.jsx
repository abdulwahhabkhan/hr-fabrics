import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import {
    Alert,
    Col,
    Form,
    InputGroup,
    Row,
    Tooltip,
} from 'react-bootstrap';
import OverlayTrigger from '@/components/ui/OverlayTrigger';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import { NumberFormat } from '@/util/NumberFormat';
import { DeleteAjax, Edit } from '@/components/Actions';
import { OrderItemForm } from '@/pages/Sales/Orders/OrderItemForm';
import {
    getSOCommission,
    getSOUnit,
    notifyMessage,
    ORDER_CLOSED,
} from '@/util/util';
import ValidationErrors from '@/components/ValidationErrors';
import Back from '@/components/button/back';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import NoData from '@/components/NoData.jsx';

const OrderForm = () => {
    const {
        order,
        errors: serverErrors,
        products,
        items: orderItems,
        types,
        customers,
        discountTypes,
    } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(orderItems.data);
    const [basketTotal, setBasketTotal] = useState(0);
    const [customer, setCustomer] = useState(order.customer);
    const [itemsLoading, setItemsLoading] = useState(false);
    const [addItem, setAddItem] = useState(false);
    const [item, setItem] = useState({});
    const [warning, setWarning] = useState(false);
    const {
        register,
        handleSubmit,
        control,
        watch,
        setError,
        formState: { errors },
    } = useForm({ defaultValues: order });
    const { expenses, discount_on_total } = watch();
    const options = {
        onError: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);

        Inertia.put(
            route('sales.orders.update', order['id']),
            post_data,
            options,
        );
    };
    const confirmRequest = async (data) => {
        const post_data = { ...data, status: ORDER_CLOSED };
        setProcessing(true);
        Inertia.put(
            route('sales.orders.update', order['id']),
            post_data,
            options,
        );
    };

    const updateItem = (item) => {
        setItem(item);
        setAddItem(true);
    };

    const handleClose = () => {
        setAddItem(false);
    };

    const getItems = () => {
        axios({
            method: 'get',
            url: route('ajax.so.items', order.id),
        })
            .then((res) => {
                setItems(res.data.items);
            })
            .finally((res) => {
                setItemsLoading(false);
            })
            .catch((error) => {
                console.error(error);
            });
    };

    const deleteItem = (id) => {
        axios({
            method: 'delete',
            url: route('ajax.so.item.destroy', id),
        })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({
                    title: 'Success',
                    type: 'success',
                    message: 'Items deleted successfully',
                });
                setAddItem(false);
            })
            .finally((res) => {
                setItemsLoading(false);
            })
            .catch((error) => {
                console.error(error);
            });
    };

    useEffect(() => {
        getTotal();
    }, [addItem, expenses, discount_on_total]);

    const getTotal = () => {
        let total = items.reduce((s, item) => {
            return s + item.total_amount;
        }, 0);
        setBasketTotal(
            parseFloat(total) +
                parseFloat(expenses) -
                parseFloat(discount_on_total),
        );
    };

    const getTotalDiscount = () => {
        return items.reduce((s, item) => {
            return s + item.discount;
        }, 0);
    };

    const getBalance = () => {
        if (!customer.balance && basketTotal < 1) {
            return 0;
        }
        return customer.limit - (customer.balance + basketTotal);
    };

    const checkOverLimit = () => {
        if (customer.limit > 0)
            return customer.limit - (customer.balance + basketTotal);
        else if (customer.credit) return 1; //unlimited
    };

    return (
        <>
            <Head title="Sales Invoice Update" />
            <PageHeader
                title="Sales Invoice Update"
                buttons={
                    <>
                        <PreviewButton
                            href={route('sales.orders.show', order.id)}
                            size="sm"
                        />

                        <Back
                            href={route('sales.orders.index')}
                            size="sm"
                            label={'Orders List'}
                        />
                    </>
                }
            />
            <PageContent>
                <Panel theme={'default'}>
                    <PanelHeader
                        heading={
                            <>
                                Order Information : &nbsp;
                                <Moment
                                    format={settings.FULL_DATE_FORMAT}
                                    date={order.created_at}
                                />
                            </>
                        }
                        buttons={
                            <>
                                <LoadingButton
                                    className={'btn-xs'}
                                    processing={processing}
                                    onClick={handleSubmit(confirmRequest)}
                                >
                                    Confirm & close
                                </LoadingButton>
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
                    ></PanelHeader>
                    <PanelBody>
                        <ValidationErrors errors={serverErrors} />
                        {order.customer.credit ? null : (
                            <Alert className={'note'} variant={'danger'}>
                                <span className="fw-semibold">
                                    Credit not allowed for this customer.
                                </span>
                            </Alert>
                        )}
                        <Row>
                            <Col lg={4}>
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
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Invoice No:</Form.Label>
                                    <Form.Control
                                        defaultValue={order.invoice_no}
                                        readOnly={true}
                                        size={'sm'}
                                        placeholder={'invoice no'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Discount Rate:</Form.Label>
                                    <InputGroup>
                                        <Form.Control
                                            defaultValue={order.discount_rate}
                                            {...register('discount_rate')}
                                            size={'sm'}
                                            placeholder={'discount rate'}
                                        />
                                        <Form.Select
                                            aria-label="per meter or on total"
                                            size={'sm'}
                                            {...register('discount_type', {
                                                required: true,
                                            })}
                                            isInvalid={errors.discount_type}
                                        >
                                            {discountTypes &&
                                                discountTypes.map(
                                                    (type, index) => {
                                                        return (
                                                            <option
                                                                key={index}
                                                                value={
                                                                    type.value
                                                                }
                                                            >
                                                                {type.label}
                                                            </option>
                                                        );
                                                    },
                                                )}
                                        </Form.Select>
                                    </InputGroup>
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Payment Mode:</Form.Label>
                                    <Form.Select
                                        {...register('payment_mode', {
                                            required: true,
                                        })}
                                        size={'sm'}
                                    >
                                        <option value={''}>Select</option>
                                        <option>Cash</option>
                                        {order.customer.credit ? (
                                            <option>Credit</option>
                                        ) : null}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Purchase Type:</Form.Label>
                                    <Form.Select
                                        {...register('purchase_type', {
                                            required: true,
                                        })}
                                        size={'sm'}
                                    >
                                        <option value={''}>Select</option>
                                        {types &&
                                            types.map(function (val, index) {
                                                return (
                                                    <option key={index}>
                                                        {val}
                                                    </option>
                                                );
                                            })}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col lg={8}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Order Notes:</Form.Label>
                                    <Form.Control
                                        defaultValue={order.expenses_detail}
                                        {...register('expenses_detail')}
                                        size={'sm'}
                                        placeholder={'order notes'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Expenses:</Form.Label>
                                    <Form.Control
                                        defaultValue={order.expenses}
                                        {...register('expenses')}
                                        size={'sm'}
                                        placeholder={'expenses'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Discount On Total:</Form.Label>
                                    <Form.Control
                                        defaultValue={order.discount_on_total}
                                        {...register('discount_on_total')}
                                        size={'sm'}
                                        placeholder={'discount on total'}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>
                <Panel theme={'default'}>
                    <PanelHeader
                        heading={'Order Items'}
                        buttons={
                            <>
                                {customer.credit && (
                                    <button
                                        className={
                                            'btn btn-xs ' +
                                            (checkOverLimit() > 0
                                                ? 'btn-outline-info'
                                                : 'btn-outline-danger')
                                        }
                                        title={'Limit'}
                                    >
                                        <OverlayTrigger
                                            placement={'bottom'}
                                            overlay={
                                                <Tooltip>
                                                    Available Balance
                                                </Tooltip>
                                            }
                                        >
                                            <span>
                                                <Icon
                                                    icon={
                                                        'solar:hourglass-line-line-duotone'
                                                    }
                                                />{' '}
                                                Balance: &nbsp;
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={getBalance()}
                                                    thousandSeparator={true}
                                                />{' '}
                                                &nbsp;
                                            </span>
                                        </OverlayTrigger>
                                    </button>
                                )}

                                <button
                                    className="btn btn-xs  btn-outline-theme"
                                    title={'Discount'}
                                >
                                    <Icon
                                        icon={'solar:ticket-sale-line-duotone'}
                                    />{' '}
                                    Discount: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={getTotalDiscount()}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>
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
                                    className="btn btn-xs  btn-primary"
                                    onClick={() =>
                                        updateItem({
                                            order_id: order.id,
                                            id: 0,
                                            discount: order.discount_rate,
                                        })
                                    }
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
                                    <th className="w-1">#</th>
                                    <th>Product</th>
                                    <th className={'w-1'}>Unit</th>
                                    <th className={'w-1 num'}>Qty</th>
                                    <th className={'w-1 num'}>Meters</th>
                                    <th className={'w-1 num'}>Price</th>
                                    <th className={'w-1 num'}>
                                        {order.discount_label}
                                    </th>
                                    <th className={'w-1 num'}>Total</th>
                                    <th className={'w-1 num'}>Commission</th>
                                    <th className={'w-1 actions'}>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items &&
                                    items.map((row, index) => {
                                        const {
                                            id,
                                            product_name,
                                            price,
                                            unit,
                                            qty,
                                            discount,
                                            size,
                                            total_qty,
                                            total_amount,
                                            commission,
                                            total_commission,
                                        } = row;
                                        return (
                                            <tr key={index}>
                                                <td className={'w-1'}>
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
                                                <td className={'w-1'}>
                                                    {getSOUnit(unit, size)}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {qty}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {total_qty}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {price}
                                                </td>
                                                <td className={'num w-1'}>
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={discount}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={'num w-1'}>
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={total_amount}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={'num w-1'}>
                                                    {getSOCommission(
                                                        commission,
                                                        total_commission,
                                                    )}
                                                </td>
                                                <td className={'actions w-1'}>
                                                    {order.status != 1 && (
                                                        <>
                                                            <Edit
                                                                onClick={() =>
                                                                    updateItem(
                                                                        row,
                                                                    )
                                                                }
                                                            />
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
                            <NoData label="No order items added." />
                        )}
                    </PanelBody>
                </Panel>
                {addItem && (
                    <OrderItemForm
                        orderId={order.id}
                        item={item}
                        onClose={handleClose}
                        setItems={setItems}
                        commission={order.agent_rate}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default OrderForm;

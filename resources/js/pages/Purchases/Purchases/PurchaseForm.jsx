import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { updateErrors } from '@/components/panel/ErrorPanel';
import { settings } from '@/config/page-settings';
import { useForm } from 'react-hook-form';
import Moment from '@/components/Moment';
import { DeleteAjax, Edit } from '@/components/Actions';
import { PurchaseItemForm } from './PurchaseItemForm';
import { getPOUnit, notifyMessage, STATUS_OPEN } from '@/util/util';
import { NumberFormat } from '@/util/NumberFormat';
import BackButton from '@/components/button/back';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import NoData from '@/components/NoData.jsx';

const PurchaseForm = () => {
    const {
        receipt: order,
        errors: serverErrors,
        products,
        items: orderItems,
        status_open,
        status_close
    } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(orderItems.data);
    const [itemsLoading, setItemsLoading] = useState(false);
    const [addItem, setAddItem] = useState(false);
    const [item, setItem] = useState({});
    const [reset, setReset] = useState(false);
    const [voucherFiles, setVoucherFiles] = useState([
        {
            "directory": "po-voucher",
            "file_path": "po-voucher/lnVWKo_bg-1.png",
            "file_name": "bg-1.png",
            "file_thumbnail": "/storage/po-voucher/thumbnail/lnVWKo_bg-1.png"
        },
        {
            "directory": "po-voucher",
            "file_path": "po-voucher/rcAarQ_bg-2.png",
            "file_name": "bg-1.png",
            "file_thumbnail": "/storage/po-voucher/thumbnail/rcAarQ_bg-2.png"
        }
    ]);
    const [biltiFile, setBiltiFile] = useState(null);

    const { register, handleSubmit, setError, formState: { errors, isDirty } } = useForm({ defaultValues: order });
    const options = {
        onError: () => {
            setProcessing(false);
        },
        onSuccess: () => {
            setProcessing(false);
        }
    };
    const sendRequest = async (data) => {
        const post_data = { ...data, status: status_open };
        setProcessing(true);

        Inertia.put(route("purchases.pos.update", order["id"]), post_data, options);
    };
    const confirmRequest = async (data) => {
        const post_data = { ...data, status: status_close };
        setProcessing(true);

        Inertia.put(route("purchases.pos.update", order["id"]), post_data, options);
    };
    const updateItem = (item) => {
        setItem(item);
        setAddItem(true);
    };

    const addVoucherFile = (file) => {
        //setVoucherFiles({...voucherFiles, file})
        setVoucherFiles(voucherFiles.concat(file));
    };


    const handleClose = () => {
        setAddItem(false);
    };

    const deleteItem = (id) => {
        axios({
            method: "delete",
            url: route("ajax.po.item.destroy", id)
        }).then(res => {
            setItems(res.data.items);
            notifyMessage({ title: "Success", type: "success", message: "Items deleted successfully" });
            setAddItem(false);
        }).finally((res) => {
            setItemsLoading(false);
        }).catch((error) => {
            console.error(error);
        });
    };

    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);


    const canModify = order.status === STATUS_OPEN;
    const getTotalQTY = () => {
        return items.reduce((s, item) => {
            return s + item.total_qty;
        }, 0);
    };
    return (
        <>
            <Head title="Purchase Update" />
            <PageHeader title="Purchase Update" buttons={(<>
                <PreviewButton href={route("purchases.pos.show", order.id)} />
                <BackButton href={route("purchases.pos.index")} />
            </>)} />
            <PageContent>
                <Panel theme={"default"}>
                    <PanelHeader heading={(
                        <>
                            Order Information : {order.invoice_no} &nbsp; &nbsp;
                            <Moment
                                format={settings.FULL_DATE_FORMAT}
                                date={order.created_at} />
                        </>
                    )} buttons={(
                        <>
                            <LoadingButton variant="danger" className={"btn-xs"}
                                           processing={processing} onClick={handleSubmit(confirmRequest)}>
                                Confirm & Close
                            </LoadingButton>
                            <LoadingButton variant="primary" className={"btn-xs"}
                                           processing={processing} onClick={handleSubmit(sendRequest)}>
                                Save Changes
                            </LoadingButton>
                        </>
                    )} />
                    <PanelBody>
                        <Row>
                            <Col lg={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Supplier:</Form.Label>
                                    <Form.Control
                                        value={order.supplier.name}
                                        readOnly={true}
                                        size={"sm"}
                                        placeholder={"supplier"} />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Bilti No:</Form.Label>
                                    <Form.Control
                                        size={"sm"}
                                        readOnly={true}
                                        isInvalid={errors.bilti_no}
                                        {...register("bilti_no")}
                                        placeholder={"bilti no"} />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Bill No:</Form.Label>
                                    <Form.Control
                                        size={"sm"}

                                        isInvalid={errors.bill_no}
                                        {...register("bill_no", { required: true })}
                                        placeholder={"bill no"} />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Lot No:</Form.Label>
                                    <Form.Control
                                        size={"sm"}

                                        isInvalid={errors.lot_no}
                                        {...register("lot_no", { required: true })}
                                        placeholder={"lot no"} />
                                </Form.Group>
                            </Col>
                            <Col lg={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Remarks:</Form.Label><br />
                                    <Form.Control
                                        className={""}
                                        isInvalid={errors.remarks}
                                        {...register("remarks")}
                                        size={"sm"}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>
                <Panel theme={"default"}>
                    <PanelHeader heading={"Voucher Items"} buttons={(
                        <>
                            {
                                canModify && (
                                    <>
                                        <button className="btn btn-xs  btn-primary"
                                                onClick={() => updateItem({ order_id: order.id, item_id: 0 })}>
                                            <Icon icon={"solar:add-bold-duotone"} /> Add Item
                                        </button>
                                    </>
                                )
                            }

                        </>
                    )} />
                    <PanelBody>
                        <table className={"table table-bordered table-hover"}>
                            <thead>
                            <tr>
                                <th className="w-1">#</th>
                                <th className="w-1">Voucher No</th>
                                <th>Product</th>
                                <th className="w-1">Unit</th>
                                <th className={"num w-1"}>Qty</th>
                                <th className={"num w-1"}>Price</th>
                                <th className={"num w-1"}>Total</th>
                                <th className={"actions w-1"}>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            {
                                items && items.map((row, index) => {
                                    const {
                                        id,
                                        product_name,
                                        price,
                                        voucher_no,
                                        unit,
                                        size,
                                        qty,
                                        total,
                                        total_qty
                                    } = row;
                                    return (
                                        <tr key={index}>
                                            <td className="w-1">{index + 1}</td>
                                            <td className="w-1">{voucher_no}</td>
                                            <td>{product_name}</td>
                                            <td className="w-1">{getPOUnit(unit, size, qty)}</td>
                                            <td className={"num w-1"}>{total_qty}</td>
                                            <td className={"num w-1"}>{price}</td>
                                            <td className={"num w-1"}>
                                                <NumberFormat
                                                    displayType={"text"}
                                                    value={total} thousandSeparator={true} />

                                            </td>
                                            <td className={"actions w-1"}>
                                                {
                                                    canModify && (
                                                        <>
                                                            <Edit onClick={() => updateItem(row)} />
                                                            <DeleteAjax onDelete={deleteItem} id={id} />
                                                        </>

                                                    )
                                                }

                                            </td>
                                        </tr>
                                    );
                                })
                            }
                            </tbody>
                        </table>
                        {items && items.length === 0 && (<NoData label="No purchase items added." />)}
                    </PanelBody>
                </Panel>
                {
                    addItem && (
                        <PurchaseItemForm
                            item={item}
                            receiptId={order.id}
                            onClose={handleClose}
                            setItems={setItems}
                            products={products.data} />
                    )
                }
            </PageContent>

        </>
    );
};

export default PurchaseForm;

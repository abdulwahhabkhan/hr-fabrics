import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { DeleteAjax } from '@/components/Actions';
import { ReturnItemForm } from './ReturnItemForm';
import { NumberFormat } from '@/util/NumberFormat';
import { ErrorPanel } from '@/components/panel/ErrorPanel';
import FormLabel from '@/components/FormLabel.jsx';
import BackButton from '@/components/button/back';
import { notifyMessage } from '@/util/util.jsx';
import NoData from '@/components/NoData.jsx';
import por from '@/routes/purchases/por';
import porAjax from '@/routes/ajax/por';


const ReturnForm = () => {
    const {
        por: porData,
        errors: serverErrors,
        products
    } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [items, setItems] = useState(porData.items_with_product ?? []);
    const [addItem, setAddItem] = useState(false);

    const { register, handleSubmit, control, formState: { errors } } = useForm({ defaultValues: porData });
    const options = {
        onError: () => {
            setProcessing(false);
        },
        onFinish: () => {
            setProcessing(false);
        }
    };
    const sendRequest = async (data) => {
        data.info = { ...data.info };
        const post_data = { ...data, items: items };
        postData(post_data);
        //
    };
    const postData = (post_data) => {
        setProcessing(true);
        if (porData.id)
            Inertia.put(por.update(porData.id), post_data, options);
        else
            Inertia.post(por.store(), post_data, options);
    };

    const confirmRequest = async (data) => {
        data.info = { ...data.info };
        const post_data = { ...data, items: items, status: 1 };
        postData(post_data);
    };

    const updateItem = () => {
        setAddItem(true);
    };

    const handleClose = () => {
        setAddItem(false);
    };

    const deleteItem = (itemId) => {
        axios({
            method: "delete",
            url: porAjax.item.destroy({ return: porData.id, item: itemId }).url
        })
            .then(res => {
                setItems(res.data.items);
                notifyMessage({ title: "Success", type: "success", message: "Items deleted successfully" });
            });
    };


    return (
        <>
            <Head title="Fabric Return Update" />
            <PageHeader title="Fabric Return Update" buttons={
                <>

                    <BackButton href={por.index()} />
                </>
                                                     } />
            <PageContent>
                <Panel theme={"default"}>
                    <PanelHeader heading={(
                        <>
                            Order Information : {porData.invoice_no} &nbsp; &nbsp;

                        </>
                    )} buttons={(
                        <>
                            <LoadingButton variant="primary" className={"btn-xs"}
                                           processing={processing} onClick={handleSubmit(confirmRequest)}>
                                Confirm & Close
                            </LoadingButton>
                            <LoadingButton variant="white" className={"btn-xs"}
                                           processing={processing} onClick={handleSubmit(sendRequest)}>
                                Save Changes
                            </LoadingButton>
                        </>
                    )} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <Row>
                            <Col lg={6}>
                                <FormLabel label="Supplier" value={porData.supplier?.name} />

                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Bilti No:</Form.Label>
                                    <Form.Control
                                        {...register("bilti_no", { required: true })}
                                        isInvalid={errors.bilti_no}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Bill No:</Form.Label>
                                    <Form.Control
                                        {...register("bill_no", { required: true })}
                                        isInvalid={errors.bill_no}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={1}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Expenses:</Form.Label>
                                    <Form.Control
                                        {...register("expenses", { required: true })}
                                        isInvalid={errors.expenses}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={1}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Discount:</Form.Label>
                                    <Form.Control
                                        {...register("discount", { required: true })}
                                        isInvalid={errors.discount}
                                    />
                                </Form.Group>
                            </Col>

                            <Col lg={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Remarks:</Form.Label><br />
                                    <Form.Control
                                        className={""}
                                        {...register("info.remarks", { required: true })}
                                        size={"sm"}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>

                        </Row>
                    </PanelBody>
                </Panel>
                <Panel theme={"default"}>
                    <PanelHeader heading={"Order Items"} buttons={(
                        <>
                            <button className="btn btn-xs  btn-primary"
                                    onClick={() => updateItem({})}>
                                <Icon icon={"solar:add-bold-duotone"} /> Add Item
                            </button>

                        </>
                    )} />
                    <PanelBody>
                        <table className={"table table-bordered table-hover"}>
                            <thead>
                            <tr>
                                <th className={"w-1"}>#</th>
                                <th>Product</th>
                                <th className={"w-1"}>Unit</th>
                                <th className={"num w-1"}>Qty</th>
                                <th className={"num w-1"}>Meters</th>
                                <th className={"num w-1"}>Rate</th>
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
                                        rate,
                                        unit,
                                        size,
                                        qty,
                                        total_qty,
                                        total_amount
                                    } = row;
                                    return (
                                        <tr key={id}>
                                            <td>{index + 1}</td>
                                            <td>{product_name}</td>
                                            <td>{unit}</td>
                                            <td className={"num"}>{qty}</td>
                                            <td className={"num"}>{total_qty}</td>
                                            <td className={"num"}>
                                                <NumberFormat
                                                    displayType={"text"}
                                                    value={rate} thousandSeparator={true} />
                                            </td>
                                            <td className={"num"}>
                                                <NumberFormat
                                                    displayType={"text"}
                                                    decimalScale={2}
                                                    value={total_amount} thousandSeparator={true} />
                                            </td>
                                            <td className={"actions"}>
                                                <DeleteAjax onDelete={deleteItem} id={id} />
                                            </td>
                                        </tr>
                                    );
                                })
                            }
                            </tbody>
                        </table>
                        {items && items.length === 0 && (<NoData label="No return items added." />)}
                    </PanelBody>
                </Panel>
                {
                    addItem && (
                        <ReturnItemForm
                            onClose={handleClose}
                            returnId={por.id}
                            setItems={setItems}
                            products={products.data} />
                    )
                }
            </PageContent>

        </>
    );
};

export default ReturnForm;

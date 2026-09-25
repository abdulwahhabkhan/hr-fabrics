import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Figure, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { updateErrors } from '@/components/panel/ErrorPanel';
import { useForm } from 'react-hook-form';
import Moment from '@/components/Moment';
import { DeleteAjax, Edit } from '@/components/Actions';
import { FabricReceivingItemForm } from './FabricReceivingItemForm';
import { getPOUnit, notifyMessage } from '@/util/util';
import { AttachFiles, FileUpload } from '@/components/File';
import BackButton from '@/components/button/back';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import NoData from '@/components/NoData.jsx';
import fabricReceivings from '@/routes/purchases/fabric-receivings';
import fabricReceivingAjax from '@/routes/ajax/fabric-receiving';

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
        setError,
        formState: { errors, isDirty },
    } = useForm({ defaultValues: order });
    const options = {
        onError: () => {
            setProcessing(false);
        },
        onSuccess: () => {
            //setProcessing(false)
        },
    };
    const sendRequest = async (data) => {
        data.info = { ...data.info };
        const post_data = { ...data, files: voucherFiles };
        setProcessing(true);

        Inertia.put(
            fabricReceivings.update(order['id']),
            post_data,
            options,
        );
    };
    const confirmRequest = async (data) => {
        data.info = { ...data.info, files: voucherFiles };
        const post_data = { ...data, status: 'closed' };
        setProcessing(true);

        Inertia.put(
            fabricReceivings.update(order['id']),
            post_data,
            options,
        );
    };
    const updateItem = (item) => {
        setItem(item);
        setAddItem(true);
    };

    const addVoucherFile = (file) => {
        setVoucherFiles(voucherFiles.concat(file));
    };

    const handleClose = () => {
        setAddItem(false);
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
                    message: 'Items deleted successfully',
                });
                setAddItem(false);
            })
            .finally((res) => {
                //
            })
            .catch((error) => {
                console.error(error);
            });
    };

    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);

    const getTotalQTY = () => {
        return items.reduce((s, item) => {
            return s + item.total_qty;
        }, 0);
    };
    return (
        <>
            <Head title="Fabric Receiving Update" />
            <PageHeader
                title="Fabric Receiving Update"
                buttons={
                    <>
                        <PreviewButton
                            href={fabricReceivings.show(order.id)}
                        />
                        <BackButton
                            href={fabricReceivings.index()}
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
                                <Moment date={order.created_at} />
                            </>
                        }
                        buttons={
                            <>
                                {/*<LoadingButton variant="primary" className={'btn-xs'}
                                           processing={processing} onClick={handleSubmit(confirmRequest)}>
                                Confirm & close
                            </LoadingButton>*/}
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
                        <Row>
                            <Col lg={3}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Supplier:</Form.Label>
                                    <Form.Control
                                        value={order.supplier.name}
                                        readOnly={true}
                                        size={'sm'}
                                        placeholder={'supplier'}
                                    />
                                </Form.Group>
                            </Col>

                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Bilti No:</Form.Label>
                                    <Form.Control
                                        size={'sm'}
                                        isInvalid={errors.bilti_no}
                                        {...register('bilti_no')}
                                        placeholder={'bilti no'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Lot No:</Form.Label>
                                    <Form.Control
                                        size={'sm'}
                                        isInvalid={errors.lot_no}
                                        {...register('lot_no')}
                                        placeholder={'lot no'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={3}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Comments:</Form.Label>
                                    <br />
                                    <Form.Control
                                        className={''}
                                        placeholder={'comments'}
                                        isInvalid={errors.remarks}
                                        {...register('info.comments')}
                                        size={'sm'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Status</Form.Label>
                                    <Form.Select
                                        size={'sm'}
                                        {...register('status', {
                                            required: true,
                                        })}
                                        isInvalid={errors.status}
                                    >
                                        <option value={''}>
                                            Select Status
                                        </option>
                                        {status_list &&
                                            status_list.map(
                                                function (val, index) {
                                                    return (
                                                        <option key={index}>
                                                            {val}
                                                        </option>
                                                    );
                                                },
                                            )}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                        <hr className={'sm'} />
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
                <Panel theme={'default'} className={'d-none'}>
                    <PanelBody>
                        <Row>
                            <Col md={12}>
                                <Row>
                                    <Col md={12}>
                                        <FileUpload
                                            directory={'po-voucher'}
                                            msg={'Voucher File'}
                                            progress={setProcessing}
                                            updated={addVoucherFile}
                                        />
                                    </Col>
                                    {voucherFiles.length > 0 && (
                                        <Col md={12}>
                                            {voucherFiles.map((file, index) => {
                                                return (
                                                    <span
                                                        className={
                                                            'image-thumbnails'
                                                        }
                                                        key={index}
                                                    >
                                                        <Figure>
                                                            <Figure.Image
                                                                className={
                                                                    'height-150 img-thumbnail'
                                                                }
                                                                alt={
                                                                    file.file_name
                                                                }
                                                                src={
                                                                    file.file_thumbnail
                                                                }
                                                            />
                                                            <Figure.Caption>
                                                                {file.file_name}
                                                            </Figure.Caption>
                                                        </Figure>
                                                    </span>
                                                );
                                            })}
                                        </Col>
                                    )}
                                </Row>
                            </Col>
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
                                    onClick={() =>
                                        updateItem({
                                            order_id: order.id,
                                            item_id: 0,
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
                                    <th className="w-1">Voucher No</th>
                                    <th>Product</th>
                                    <th className="w-1">Unit</th>
                                    <th className={'num w-1'}>Qty</th>
                                    <th className={'num w-1'}>Meters</th>
                                    <th className={'actions w-1'}>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items &&
                                    items.map((row, index) => {
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
                                            <tr key={index}>
                                                <td className="w-1">
                                                    {index + 1}
                                                </td>
                                                <td className="w-1">
                                                    {voucher_no}
                                                </td>
                                                <td>{product_name}</td>
                                                <td className="w-1">
                                                    {getPOUnit(unit, size, qty)}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {qty}
                                                </td>
                                                <td className={'num w-1'}>
                                                    {total_qty}
                                                </td>
                                                <td className={'actions w-1'}>
                                                    <Edit
                                                        onClick={() =>
                                                            updateItem(row)
                                                        }
                                                    />
                                                    <DeleteAjax
                                                        onDelete={deleteItem}
                                                        id={id}
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                        {items && items.length === 0 && (
                            <NoData label="No fabric receiving items added." />
                        )}
                    </PanelBody>
                </Panel>
                {addItem && (
                    <FabricReceivingItemForm
                        item={item}
                        orderId={order.id}
                        onClose={handleClose}
                        setItems={setItems}
                        products={products.data}
                    />
                )}
            </PageContent>
        </>
    );
};

export default FabricReceivingForm;

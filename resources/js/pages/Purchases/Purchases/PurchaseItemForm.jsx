import * as React from 'react';
import { useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { notifyMessage, serverSideError } from '@/util/util.jsx';
import poAjax from '@/routes/ajax/po';

export const PurchaseItemForm = ({
    item,
    receiptId,
    onClose,
    setItems,
    products,
}) => {
    const itemDetail =
        _.find(products, { product_id: item.product_id ?? 0 }) ?? null;
    const [product, setProduct] = useState(
        itemDetail
            ? {
                  ...itemDetail,
                  qty: item.qty ?? '',
                  voucher_no: item.voucher_no ?? '',
                  price: item.price ?? 0,
                  unit: item.unit ?? '',
                  size: item.size ?? '',
                  total_qty: item.total_qty ?? '',
              }
            : '',
    );
    const {
        register,
        handleSubmit,
        setValue,
        control,
        formState: { errors },
        reset,
    } = useForm({ defaultValues: product });
    const [processing, setProcessing] = useState(false);
    const updateValue = (item) => {
        setProduct(item);
        const { is_box, unit_price, suit_price, name, finish } = { ...item };

        setValue('unit', is_box ? 'Box' : 'Thaan', { shouldDirty: true });
        setValue('name', name ?? '', { shouldDirty: true });
        setValue('finish', finish ?? '', { shouldDirty: true });
    };

    const handleClose = () => {
        onClose();
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        axios({
            method: 'post',
            url: poAjax.items(receiptId).url,
            data: { ...data, order_id: item.order_id, item_id: item.id },
        })
            .then((res) => {
                setItems(res.data.items);
                notifyMessage({
                    title: 'Success',
                    type: 'success',
                    message: 'Items saved successfully',
                });
                if (item.id > 0) {
                    onClose();
                } else {
                    resetForm(data);
                }
            })
            .finally((res) => {
                setProcessing(false);
            })
            .catch((error) => {
                serverSideError(error);
            });
    };

    const resetForm = (data) => {
        reset({
            ...data,
            qty: '',
            size: '',
            voucher_no: '',
            total_qty: '',
            price: '',
        });
    };

    const isBox = parseInt(product.is_box) === 1;

    const isLoading = () => {
        return processing;
    };
    return (
        <Modal show={true} backdrop="static" size={'lg'} keyboard={true}>
            <Modal.Header>
                <Modal.Title>Product</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <form
                    action=""
                    className=""
                    onSubmit={handleSubmit(sendRequest)}
                >
                    <Row>
                        <Col md={12}>
                            <Form.Group className="mb-3">
                                <Form.Label>Voucher No:</Form.Label>
                                <Form.Control
                                    defaultValue={product.voucher_no ?? ''}
                                    {...register('voucher_no', {
                                        required: true,
                                    })}
                                    isInvalid={errors.voucher_no}
                                />
                            </Form.Group>
                        </Col>
                    </Row>
                    <Row>
                        <Col md={12}>
                            <Form.Group className="mb-3">
                                <Form.Label>Product SKU:</Form.Label>
                                <Controller
                                    render={({ field }) => (
                                        <StyledSelect
                                            {...field}
                                            options={products}
                                            onChange={(e) => {
                                                field.onChange(e);
                                                updateValue(e);
                                            }}
                                            defaultValue={product}
                                            getOptionValue={(option) =>
                                                option['product_id']
                                            }
                                            getOptionLabel={(option) =>
                                                option['product_info']
                                            }
                                        />
                                    )}
                                    control={control}
                                    name={'product'}
                                />
                            </Form.Group>
                        </Col>

                        <Col md={8}>
                            <Form.Group className="mb-3">
                                <Form.Label>Name:</Form.Label>
                                <Form.Control
                                    defaultValue={product.name ?? ''}
                                    {...register('name', { required: true })}
                                    isInvalid={errors.name}
                                    readOnly={true}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>Finish:</Form.Label>
                                <Form.Control
                                    {...register('finish', { required: true })}
                                    readOnly={true}
                                />
                            </Form.Group>
                        </Col>
                    </Row>

                    <Row>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Unit:</Form.Label>
                                <Form.Control
                                    {...register('unit', { required: true })}
                                    isInvalid={errors.unit}
                                    readOnly={true}
                                    placeholder={'unit'}
                                />
                            </Form.Group>
                        </Col>
                        {isBox && (
                            <Col md={3}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Size:</Form.Label>
                                    <Form.Control
                                        {...register('size', {
                                            required: true,
                                        })}
                                        isInvalid={errors.size}
                                        placeholder={'size'}
                                    />
                                </Form.Group>
                            </Col>
                        )}

                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Qty:</Form.Label>
                                <Form.Control
                                    {...register('qty', {
                                        required: true,
                                        min: 1,
                                    })}
                                    isInvalid={errors.qty}
                                    defaultValue={product.qty ?? ''}
                                    placeholder={'Qty'}
                                />
                            </Form.Group>
                        </Col>
                        {!isBox && (
                            <Col md={3}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Meters:</Form.Label>
                                    <Form.Control
                                        {...register('total_qty', {
                                            required: true,
                                        })}
                                        isInvalid={errors.total_qty}
                                        defaultValue={product.total_qty ?? ''}
                                        placeholder={'total meters'}
                                    />
                                </Form.Group>
                            </Col>
                        )}

                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Price:</Form.Label>
                                <Form.Control
                                    {...register('price', { required: true })}
                                    isInvalid={errors.price}
                                    defaultValue={product.price ?? ''}
                                    placeholder={'Cost'}
                                />
                            </Form.Group>
                        </Col>
                    </Row>
                </form>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="white" onClick={handleClose}>
                    Close
                </Button>
                <LoadingButton
                    processing={processing}
                    onClick={handleSubmit(sendRequest)}
                >
                    Save
                </LoadingButton>
            </Modal.Footer>
        </Modal>
    );
};

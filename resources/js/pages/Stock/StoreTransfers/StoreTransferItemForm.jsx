import * as React from 'react';
import { useEffect, useState } from 'react';
import { Button, Col, Form, InputGroup, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { UNIT_BOX, usePackingUnits } from '@/util/util';
import { confirmSwal } from '@/util/swal';

export const StoreTransferItemForm = ({
    item,
    onClose,
    onSave,
    products,
    show,
    loading,
    confirmation,
    onConfirm,
}) => {
    const itemDetail =
        _.find(products, { product_id: item.product_id ?? 0 }) ?? null;
    const [product, setProduct] = useState(
        itemDetail
            ? {
                  ...itemDetail,
                  product: { ...itemDetail },
                  qty: item.qty ?? '',
                  unit: item.unit ?? '',
                  price: item.price ?? '',
                  expense: item.expense ?? '',
                  size: item.size ?? '',
              }
            : '',
    );

    const {
        register,
        handleSubmit,
        setValue,
        setFocus,
        watch,
        control,
        formState: { errors },
    } = useForm({ defaultValues: product });

    const selectedUnit = watch('unit');

    const [processing, setProcessing] = useState(false);
    const updateValue = (item) => {
        setProduct(item);
        const { is_box, max_purchase_price, name, finish, size } = { ...item };
        setValue('unit', is_box ? UNIT_BOX : '', { shouldDirty: true });
        setValue('name', name ?? '', { shouldDirty: true });
        setValue('finish', finish ?? '', { shouldDirty: true });
        setValue('size', size ?? '', { shouldDirty: true });
        setValue('price', max_purchase_price ?? '', { shouldDirty: true });
        setFocus('unit');
    };

    const handleClose = () => {
        onClose();
    };
    const sendRequest = async (data) => {
        onSave({
            ...data,
            store_transfer_id: item.store_transfer_id,
            item_id: item.id,
            oversold: false,
        });
    };

    const sendOverSoldRequest = async (data) => {
        onConfirm(false);
        onSave({
            ...data,
            store_transfer_id: item.store_transfer_id,
            item_id: item.id,
            oversold: true,
        });
    };

    const isLoading = () => {
        return !!loading;
    };

    useEffect(() => {
        if (!confirmation) {
            return;
        }
        confirmSwal({
            text: 'you want to do the over sale!',
            confirmButtonText: 'Yes, Please',
            confirmButtonStyle: 'danger',
        }).then(({ isConfirmed }) => {
            if (isConfirmed) {
                handleSubmit(sendOverSoldRequest)();
            } else {
                onConfirm(false);
            }
        });
    }, [confirmation]);

    const packingUnits = usePackingUnits();
    const units = packingUnits.filter((val) => {
        if (product.is_box) return val === 'Box';
        else return val !== 'Box';
    });

    return (
        <>
            <Modal show={show} backdrop="static" size={'lg'} keyboard={true}>
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
                                                isClearable
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
                                        {...register('name', {
                                            required: true,
                                        })}
                                        isInvalid={errors.name}
                                        readOnly={true}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Finish:</Form.Label>
                                    <Form.Control
                                        defaultValue={product.finish ?? ''}
                                        readOnly={true}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Row>
                            <Col md={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Unit:</Form.Label>
                                    <Form.Select
                                        {...register('unit', {
                                            required: true,
                                        })}
                                    >
                                        {units &&
                                            units.map((item, index) => {
                                                return (
                                                    <option key={index}>
                                                        {item}
                                                    </option>
                                                );
                                            })}
                                    </Form.Select>
                                </Form.Group>
                            </Col>

                            <Col md={2}>
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
                            <Col md={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Size:</Form.Label>
                                    <Form.Control
                                        {...register('size', {
                                            required: true,
                                            min: 1,
                                        })}
                                        isInvalid={errors.size}
                                        defaultValue={product.size ?? ''}
                                        placeholder={'Size in meters'}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={3}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Expense:</Form.Label>
                                    <InputGroup className="mb-3">
                                        <Form.Control
                                            {...register('expense', {
                                                required: true,
                                                min: 0,
                                            })}
                                            isInvalid={errors.expense}
                                            defaultValue={product.expense ?? ''}
                                            placeholder={'expense'}
                                        />
                                        <InputGroup.Text id="basic-addon1">
                                            Per{' '}
                                            {selectedUnit === UNIT_BOX
                                                ? UNIT_BOX
                                                : 'M'}
                                        </InputGroup.Text>
                                    </InputGroup>
                                </Form.Group>
                            </Col>
                            <Col md={3}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Cost Price:</Form.Label>
                                    <Form.Control
                                        {...register('price', {
                                            required: true,
                                            min: 1,
                                        })}
                                        isInvalid={errors.price}
                                        defaultValue={product.price ?? ''}
                                        placeholder={'Price'}
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
                        processing={loading}
                        disabled={isLoading()}
                        onClick={handleSubmit(sendRequest)}
                    >
                        Save
                    </LoadingButton>
                </Modal.Footer>
            </Modal>
        </>
    );
};

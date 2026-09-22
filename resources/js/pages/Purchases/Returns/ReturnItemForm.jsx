import * as React from 'react';
import { useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { notifyMessage, serverSideError, usePackingUnits } from '@/util/util';

export const ReturnItemForm = ({returnId, onClose, setItems, products}) => {
    const packingUnits = usePackingUnits();
    const [product] = useState({})
    const [validationErrors, setValidationErrors] = useState(undefined)
    const {
        register,
        handleSubmit,
        setValue,
        watch,
        control,
        formState: {errors},
        reset
    } = useForm();
    const [processing, setProcessing] = useState(false);

    const [isBox, setIsBox] = useState(false)
    const updateValue = (item) => {
        const {is_box, name, finish, purchased_price} = {...item}
        setIsBox(is_box ? true : false)
        setValue('unit', is_box ? 'Box' : 'Thaan', {shouldDirty: true})
        setValue('name', name, {shouldDirty: true})
        setValue('finish', finish, {shouldDirty: true})
        setValue('rate', purchased_price, {shouldDirty: true})
    }

    const handleClose = () => {
        onClose()
    }
    const sendRequest = async (data) => {
        setProcessing(true);
        axios.post(
            route('ajax.return.item.save', returnId),
            {...data, product_id: data?.product?.product_id}
        )
            .then(res => {
                setItems(res.data.items)
                notifyMessage({
                    title: "Success",
                    type: 'success',
                    message: "Items saved successfully"
                })
                resetForm(data)
            })
            .finally((res) => {
                setProcessing(false)
            })
            .catch((error) => {
                serverSideError(error)
                if (error.response.status === 422) {
                    setValidationErrors(error.response.data)
                }
            })
    }
    const resetForm = (data) => {
        reset({
            ...data,
            qty: '',
            size: ''
        })
    }
    const units = packingUnits

    return (
        <Modal show={true} backdrop="static" size={'lg'} keyboard={true}>
            <Modal.Header>
                <Modal.Title>Add Return Product</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                {!_.isEmpty(validationErrors) && (
                    <div className="note alert-danger mb-2">
                        <div className="note-content">
                            <h4><b>There is an error with your submission:</b></h4>
                            {Object.entries(validationErrors.errors).map(([key, error]) => {
                                return (
                                    <div key={key} className="d-flex gap-2">
                                        <div className="field fw-bold">{key} &#8594;</div>
                                        <div className="field">
                                            {error}
                                        </div>
                                    </div>
                                )
                            })}
                        </div>
                    </div>
                )}

                <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                    <Row>
                        <Col md={12}>
                            <Form.Group className="mb-3">
                                <Form.Label>Product SKU:</Form.Label>
                                <Controller
                                    render={({field}) => (
                                        <StyledSelect
                                            {...field}
                                            options={products}
                                            onChange={(e) => {
                                                field.onChange(e)
                                                updateValue(e)
                                            }}
                                            getOptionValue={option => option['product_id']}
                                            getOptionLabel={option => option['product_info']}
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
                                <Form.Control size={'sm'}
                                              defaultValue={product?.name ?? ''}
                                              {...register('name', {required: true})}
                                              isInvalid={errors.name}
                                              readOnly={true}/>
                            </Form.Group>
                        </Col>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>Finish:</Form.Label>
                                <Form.Control size={'sm'}
                                              {...register('finish', {required: true})}

                                              readOnly={true}/>
                            </Form.Group>
                        </Col>
                    </Row>


                    <Row>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Unit:</Form.Label>
                                <Form.Control
                                    className={'form-select form-select-sm'}
                                    as={'select'}
                                    {...register('unit', {required: true})}
                                    size={'sm'}
                                >
                                    {units && units.map((item, index) => {
                                        return (
                                            <option key={index}>{item}</option>
                                        )
                                    })}
                                </Form.Control>
                            </Form.Group>
                        </Col>

                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Qty:</Form.Label>
                                <Form.Control size={'sm'}
                                              {...register('qty', {required: true, min: 1})}
                                              isInvalid={errors.qty}
                                              defaultValue={product.qty ?? ''}
                                              placeholder={'Qty'}/>
                            </Form.Group>
                        </Col>

                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Size:</Form.Label>
                                <Form.Control size={'sm'}
                                              {...register('size', {required: true})}
                                              isInvalid={errors.size}
                                              placeholder={'size'}/>
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Rate:</Form.Label>
                                <Form.Control size={'sm'}
                                              {...register('rate', {required: true})}
                                              isInvalid={errors.rate}
                                              defaultValue={product.rate ?? ''}
                                              placeholder={'Rate'}/>
                            </Form.Group>
                        </Col>
                    </Row>
                </form>
            </Modal.Body>
            <Modal.Footer>

                <Button variant="white" onClick={handleClose}>
                    Close
                </Button>
                <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                    Save
                </LoadingButton>
            </Modal.Footer>
        </Modal>
    );
};

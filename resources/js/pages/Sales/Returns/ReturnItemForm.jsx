import * as React from 'react';
import { useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { usePackingUnits } from '@/util/util';

export const ReturnItemForm = ({onClose, onSave, products, show, loading, commission}) => {
    const packingUnits = usePackingUnits();
    const {register, handleSubmit, setValue, watch, control, formState: {errors}} = useForm();
    const [processing, setProcessing] = useState(false);
    const [product, setProduct] = useState({});
    const updateValue = (item) => {
        setProduct(item)
        const {is_box, name, finish, brand_id} = {...item}
        setValue('unit', is_box ? 'Box' : '', {shouldDirty: true})
        setValue('name', name, {shouldDirty: true})
        setValue('finish', finish, {shouldDirty: true})
        setValue('commission', getCommission(brand_id) ?? 0, {shouldDirty: true})
    }
    const {unit} = watch()
    const handleClose = () => {
        onClose()
    }
    const sendRequest = async (data) => {
        onSave({...data})
    }

    const isLoading = () => {
        return processing;
    }
    const getCommission = (brand_id) => {

        return _.find(commission, (id, index) => {
            return 'brand_' + brand_id === index
        })
    }
    const units = packingUnits.filter((val) => {
        if (product.is_box)
            return val === 'Box'
        else
            return val !== 'Box'
    })
    return (
        <Modal show={show} backdrop="static" size={'lg'} keyboard={true}>
            <Modal.Header>
                <Modal.Title>Product</Modal.Title>
            </Modal.Header>
            <Modal.Body>
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
                                <Form.Control
                                    defaultValue={product.name ?? ''}
                                    {...register('name', {required: true})}
                                    isInvalid={errors.name}
                                    readOnly={true}/>
                            </Form.Group>
                        </Col>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>Finish:</Form.Label>
                                <Form.Control
                                    defaultValue={product.finish ?? ''}
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
                        <Col md={2}>
                            <Form.Group className="mb-3">
                                <Form.Label>Size:</Form.Label>
                                <Form.Control
                                    {...register('size', {required: true})}
                                    isInvalid={errors.size}
                                    placeholder={'size'}/>
                            </Form.Group>
                        </Col>
                        <Col md={2}>
                            <Form.Group className="mb-3">
                                <Form.Label>Qty:</Form.Label>
                                <Form.Control
                                    {...register('qty', {required: true, min: 1})}
                                    isInvalid={errors.qty}
                                    defaultValue={product.qty ?? ''}
                                    placeholder={'Qty'}/>
                            </Form.Group>
                        </Col>
                        {
                            commission && (
                                <Col md={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Commission Rate:</Form.Label>
                                        <Form.Control
                                            {...register('commission', {required: true, min: 0})}
                                            isInvalid={errors.commission}
                                            defaultValue={product.commission ?? ''}
                                            placeholder={'Commission'}/>
                                    </Form.Group>
                                </Col>
                            )
                        }
                        <Col md={2}>
                            <Form.Group className="mb-3">
                                <Form.Label>Rate:</Form.Label>
                                <Form.Control
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
                <LoadingButton processing={loading} onClick={handleSubmit(sendRequest)}>
                    Save
                </LoadingButton>
                <Button variant="white" onClick={handleClose}>
                    Close
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

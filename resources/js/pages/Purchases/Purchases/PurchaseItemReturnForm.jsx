import * as React from 'react';
import { useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { Inertia } from '@/util/Inertia';
import por from '@/routes/purchases/por';

export const PurchaseItemReturnForm = ({item, onClose, show}) => {

    const [product, setProduct] = useState({...item, name: item.product.name, finish: item.product.finish});
    const {register, handleSubmit, formState: {errors}} = useForm({defaultValues: product});
    const [processing, setProcessing] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleClose = () => {
        onClose()
    }
    const sendRequest = async (data) => {
        setLoading(true)
        Inertia.post(
            por.store(),
            {...data, purchase_id: item.purchase_id, id: item.id},
            {
                onError: (error) => {
                    setLoading(false)
                    notifyMessage({title: "Error", type: 'danger', message: error})
                },
                onFinish: visit => {
                    setLoading(false)
                },
                onSuccess: page => {
                    onClose()
                }
            }
        );
    }

    const isLoading = () => {
        return processing;
    }
    return (
        <Modal show={show} backdrop="static" size={'lg'} keyboard={true}>
            <Modal.Header>
                <Modal.Title>Return Product</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                    <Row>
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
                                    {...register('unit', {required: true})}
                                    isInvalid={errors.unit}
                                    readOnly={true}
                                    placeholder={'unit'}/>
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Size:</Form.Label>
                                <Form.Control
                                    {...register('size', {required: true})}
                                    isInvalid={errors.size}
                                    readOnly={true}
                                    placeholder={'size'}/>
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Qty:</Form.Label>
                                <Form.Control
                                    {...register('qty', {required: true, min: 1, max: product.qty})}
                                    isInvalid={errors.qty}
                                    defaultValue={'1'}
                                    placeholder={product.qty}/>
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Cost:</Form.Label>
                                <Form.Control
                                    {...register('price', {required: true, min: 1})}
                                    isInvalid={errors.price}
                                    readOnly={true}
                                    defaultValue={product.price ?? ''}
                                    placeholder={'Cost'}/>
                            </Form.Group>
                        </Col>
                    </Row>
                    <Row>
                        <Col>
                            <Form.Group className="mb-3">
                                <Form.Label>Remarks:</Form.Label>
                                <Form.Control
                                    {...register('remarks', {required: true, min: 1})}
                                    isInvalid={errors.remarks}
                                    defaultValue={product.remarks ?? ''}
                                    placeholder={'remarks'}/>
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

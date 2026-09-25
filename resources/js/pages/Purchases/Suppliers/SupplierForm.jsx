import React, { useEffect, useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import { useForm } from 'react-hook-form';
import LoadingButton from '@/components/LoadingButton';
import { Inertia } from '@/util/Inertia';
import axios from 'axios';
import { formAlert, notifyMessage } from '@/util/util';
import suppliers from '@/routes/purchases/suppliers';

export default ({id, show, callback}) => {
    const title = id ? 'Update' : 'Create'
    const defaultValues = {name: "", phone: "", email: "", address: {city: "", address: '', region: ''}}
    const {register, handleSubmit, setValue, formState: {errors}} = useForm({defaultValues: defaultValues});
    const [loading, setLoading] = useState(false);
    const [processing, setProcessing] = useState(false);
    useEffect(() => {

        if (id) {
            setLoading(true)
            axios.get(suppliers.edit(id).url)
                .then(res => {
                    const {data: {detail}} = res
                    setLoading(false)
                    for (let key in detail) {
                        if (key == 'address') {
                            let address = detail[key];
                            setValue('address.address', address ? address['address'] : '')
                            setValue('address.city', address ? address['city'] : '')
                            setValue('address.region', address ? address['region'] : '')
                            setValue('address.country', address ? address['country'] : '')
                        } else {
                            setValue(key, detail[key])
                        }
                    }
                })
        }
    }, [id])
    const handleClose = () => {
        callback()
    }
    const sendRequest = async (data) => {
        setProcessing(true)
        const method = id ? "PUT" : 'POST'
        axios({
            method: method,
            url: id ? suppliers.update(id).url : suppliers.store().url,
            data: data
        }).then(res => {
            const {data: {message}} = res
            notifyMessage({title: "Success", type: 'success', message: message})
            Inertia.visit(suppliers.index().url)
        }).catch((res) => {
            const {response} = {...res}
            if (response) {
                formAlert(response)
            }
        }).finally(data => {
            setProcessing(false);
        })

    }
    return (
        <>
            <Modal show={show} backdrop="static" size={'lg'} keyboard={true}>
                <Modal.Header>
                    <Modal.Title>{title} Supplier</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                        <Form.Group className="mb-3">
                            <Form.Label>Name:</Form.Label>
                            <Form.Control
                                {...register('name', {required: true})}
                                isInvalid={errors.name}
                                placeholder={'name'}/>
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label> Address:</Form.Label>
                            <Form.Control
                                {...register('address.address')}
                                placeholder={'address'}/>
                        </Form.Group>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label> City:</Form.Label>
                                    <Form.Control
                                        {...register('address.city')}
                                        placeholder={'city'}/>
                                </Form.Group>
                            </Col>

                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label> Region:</Form.Label>
                                    <Form.Control
                                        {...register('address.region')}
                                        placeholder={'region'}/>
                                </Form.Group>
                            </Col>

                        </Row>
                    </form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="white" onClick={handleClose}>
                        Close
                    </Button>
                    <LoadingButton processing={processing} disabled={loading}
                                   onClick={handleSubmit(sendRequest)}>
                        Save Changes
                    </LoadingButton>
                </Modal.Footer>
            </Modal>
        </>
    )
}

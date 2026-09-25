import React, { useEffect, useState } from 'react';
import { Button, Form, Modal } from 'react-bootstrap';
import { useForm } from 'react-hook-form';
import LoadingButton from '@/components/LoadingButton';
import { Inertia } from '@/util/Inertia';
import axios from 'axios';
import { notifyMessage } from '@/util/util';
import brands from '@/routes/catalog/brands';


export default ({id, show, callback}) => {
    const title = id ? 'Update' : 'Create'
    const defaultValues = {name: null, description: null}
    const {register, handleSubmit, setValue, formState: {errors}} = useForm({defaultValues: defaultValues});
    const [loading, setLoading] = useState(false);
    const [processing, setProcessing] = useState(false);
    useEffect(() => {

        if (id) {
            setLoading(true)
            axios.get(brands.edit(id).url)
                .then(res => {
                    const {data: {brand}} = res
                    setLoading(false)
                    for (let key in brand) {
                        setValue(key, brand[key])
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
            url: id ? brands.update(id).url : brands.store().url,
            data: data
        }).then(res => {
            const {data: {message}} = res
            notifyMessage({title: "Success", type: 'success', message: message})
            Inertia.visit(brands.index().url)
        }).catch(res => {
            console.log('invalid request', res)
        }).finally(data => {
            setProcessing(false);
        })

    }
    return (
        <>
            <Modal show={show} backdrop="static" size={'lg'} keyboard={true}>
                <Modal.Header>
                    <Modal.Title>{title} Brand</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                        <Form.Group className="mb-3">
                            <Form.Label>Brand Name:</Form.Label>
                            <Form.Control
                                {...register('name', {required: true})}
                                isInvalid={errors.name}
                                placeholder={'name'}/>
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Brand Description:</Form.Label>
                            <Form.Control
                                {...register('description')}
                                as={'textarea'}
                                placeholder={'description'}/>
                        </Form.Group>
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

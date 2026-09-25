import { Inertia, usePage } from '@/util/Inertia.jsx';
import { Icon } from '@iconify/react';
import React, { useEffect, useState } from 'react';
import { Button, Form, Modal, Spinner } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton.jsx';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import axios from 'axios';
import { notifyMessage } from '@/util/util.jsx';
import { suppliers as suppliersAutocomplete } from '@/routes/autocomplete';
import por from '@/routes/purchases/por';

export default () => {
    const {canAdd} = usePage().props
    const [showForm, setShowForm] = useState(false)
    const [processing, setProcessing] = useState(false);
    const [loading, setLoading] = useState(true);

    const [suppliers, setSuppliers] = useState([])
    const {register, handleSubmit, control, formState: {errors}} = useForm();

    const fetchSuppliers = () => {
        setLoading(true)
        axios(suppliersAutocomplete().url)
            .then(res => {
                setSuppliers(res.data)
            })
            .finally(() => {
                setLoading(false)
            })

    }

    // Only load suppliers the first time the modal is shown
    useEffect(() => {
        if (showForm && suppliers.length === 0) {
            fetchSuppliers()
        }
    }, [showForm, suppliers])

    const sendRequest = async (data) => {
        setProcessing(true)
        axios.post(por.store().url, data)
            .then(res => {
                const {data: {message, redirect}} = res
                notifyMessage({title: "Success", type: 'success', message: message})
                Inertia.visit(redirect)
            })
            .catch(res => {
                console.error('invalid request', res)
            })
            .finally(data => {
                setProcessing(false);
            })
        setProcessing(false)
    }

    return (
        <>
            {canAdd && (
                <>
                    <Button size={'xs'} variant={'primary'} onClick={() => setShowForm(true)}>
                        <Icon icon={"solar:add-bold-duotone"}/> Add PO Return
                    </Button>
                </>

            )}

            {showForm && (
                <>
                    <Modal show={showForm} backdrop="static" size={'lg'} keyboard={true}>
                        <Modal.Header>
                            <Modal.Title>Add Purchase Return</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>

                            <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                                {
                                    loading && (
                                        <div className="d-flex justify-content-center align-items-center"
                                             style={{minHeight: 120}}>
                                            <Spinner animation="border" role="status"></Spinner>
                                        </div>
                                    )
                                }

                                {
                                    !loading && (
                                        <>
                                            <Form.Group className="mb-3">
                                                <Form.Label>Supplier:</Form.Label>
                                                <Controller
                                                    render={({field}) => (
                                                        <StyledSelect
                                                            {...field}
                                                            options={suppliers}
                                                            getOptionValue={option => option['supplier_id']}
                                                            getOptionLabel={option => option['supplier_name']}
                                                            isClearable
                                                        />
                                                    )}
                                                    control={control}
                                                    name={'supplier'}
                                                    rules={{required: true}}
                                                />
                                            </Form.Group>
                                        </>
                                    )
                                }
                            </form>
                        </Modal.Body>
                        <Modal.Footer>
                            <Button variant="white" onClick={() => setShowForm(false)}>
                                Close
                            </Button>
                            <LoadingButton processing={processing} disabled={loading}
                                           onClick={handleSubmit(sendRequest)}>
                                Create Return
                            </LoadingButton>
                        </Modal.Footer>
                    </Modal>
                </>
            )}
        </>
    )
}

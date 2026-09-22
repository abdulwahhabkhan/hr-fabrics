import React, { useState } from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';
import { useForm } from 'react-hook-form';
import { Inertia } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { UNIT_THAAN, UNITS } from '@/util/util';
import pickBy from 'lodash/pickBy';

export const InventoryFilter = ({ filters }) => {
    const [processing, setProcessing] = useState(false);
    const [units, setUnits] = useState(UNITS);
    const {
        register,
        formState: { errors },
        handleSubmit,
        watch,
    } = useForm({ defaultValues: filters });
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const values = watch();
    const unit = watch('unit');
    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        Inertia.get(route('stocks.inventories.index'), post_data, options);
    };

    return (
        <>
            <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                <Row>
                    <Col lg={4}>
                        <Form.Group className="mb-3">
                            <Form.Label>Product:</Form.Label>
                            <Form.Control
                                {...register('name')}
                                isInvalid={errors.name}
                            />
                        </Form.Group>
                    </Col>
                    <Col lg={3}>
                        <Form.Group className="mb-3">
                            <Form.Label>Unit:</Form.Label>
                            <Form.Control
                                className={'form-select'}
                                as={'select'}
                                {...register('unit')}
                                isInvalid={errors.unit}
                            >
                                <option key={'none'} value="">
                                    Select Size
                                </option>
                                {units &&
                                    units.map((value, index) => {
                                        return (
                                            <option key={value}>{value}</option>
                                        );
                                    })}
                            </Form.Control>
                        </Form.Group>
                    </Col>
                    {unit && unit !== UNIT_THAAN && (
                        <Col lg={2}>
                            <Form.Group className="mb-3">
                                <Form.Label>Size:</Form.Label>
                                <Form.Control
                                    {...register('size')}
                                    isInvalid={errors.size}
                                />
                            </Form.Group>
                        </Col>
                    )}

                    <Col lg={2}>
                        <Form.Group className="mb-3">
                            <Form.Label>&nbsp;</Form.Label>
                            <br />
                            <Button type={'submit'} variant={'white'}>
                                <Icon icon={'iconamoon:search-duotone'} />{' '}
                                &nbsp; Search
                            </Button>
                            &nbsp;
                            {Object.keys(pickBy(values)).length > 0 && (
                                <Button
                                    variant={'white'}
                                    onClick={() => sendRequest({})}
                                >
                                    <Icon icon={'solar:restart-bold-duotone'} />{' '}
                                    &nbsp; Reset
                                </Button>
                            )}
                        </Form.Group>
                    </Col>
                </Row>
            </form>
        </>
    );
};

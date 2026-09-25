import React, {useState} from 'react';
import {Button, Col, Form, Row} from "react-bootstrap";
import {useForm} from "react-hook-form";
import {Inertia} from "@/util/Inertia";
import { Icon } from "@iconify/react";
import {UNITS} from "@/util/util";
import {usePage} from "@/util/Inertia";
import stocks from '@/routes/stocks';

export const InventoryBrandFilter = ({filters}) => {
    const {brands} = usePage().props
    const [processing, setProcessing] = useState(false);
    const [units, setUnits] = useState(UNITS)
    const {register, formState: {errors}, handleSubmit, watch} = useForm({defaultValues: filters});
    const options = {
        onFinish: () => {
            setProcessing(false)
        }
    }
    const unit = watch('unit');
    const sendRequest = async (data) => {
        const post_data = {...data}
        setProcessing(true)
        Inertia.get(stocks.valueByBrand().url, post_data, options)
    }

    return (
        <>
            <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                <Row>
                    <Col lg={3}>
                        <Form.Group className="mb-3">
                            <Form.Label>Type:</Form.Label>
                            <Form.Control
                                className={'form-select'}
                                as={'select'}
                                {...register('type')}
                                isInvalid={errors.type}
                            >
                                <option value="">Select Option</option>
                                <option value="fresh">Fresh</option>
                            </Form.Control>
                        </Form.Group>
                    </Col>
                    <Col lg={3}>
                        <Form.Group className="mb-3">
                            <Form.Label>Brand:</Form.Label>
                            <Form.Control
                                className={'form-select'}
                                as={'select'}
                                {...register('brand_id')}
                                isInvalid={errors.brand_id}
                            >
                                <option value="">Select Brand</option>
                                {
                                    brands && brands.map(({id, name}) => {
                                        return (
                                            <option value={id} key={id}>{name}</option>
                                        )
                                    })
                                }
                            </Form.Control>
                        </Form.Group>
                    </Col>
                    {/*<Col lg={4}>
                        <Form.Group className="mb-3">
                            <Form.Label>Product:</Form.Label>
                            <Form.Control
                                {...register('product')}
                                isInvalid={errors.product}
                            />
                        </Form.Group>
                    </Col>*/}
                    <Col lg={2}>
                        <Form.Group className="mb-3">
                            <Form.Label>&nbsp;</Form.Label><br/>
                            <Button size={'md'} type={'submit'} variant={'primary'}>
                                <Icon icon={"solar:magnifer-bold-duotone"}/> &nbsp;
                                Search
                            </Button>&nbsp;
                            <Button size={'md'} variant={'white'} onClick={() => sendRequest({})}>
                                <Icon icon={"solar:restart-bold-duotone"}/> &nbsp;
                                Reset
                            </Button>
                        </Form.Group>
                    </Col>
                </Row>
            </form>

        </>
    );
}

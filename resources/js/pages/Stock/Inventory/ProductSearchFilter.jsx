import React from 'react';
import {Button, Col, Form, Row} from "react-bootstrap";
import {Controller, useForm} from "react-hook-form";
import {Inertia} from "@/util/Inertia";
import { Icon } from "@iconify/react";
import {usePage} from "@/util/Inertia";
import Select from "react-select";

export const ProductSearchFilter = ({filters}) => {
    const {products, product} = usePage().props
    console.log(products)
    const {register, control, setValue, formState: {errors}, handleSubmit, watch} = useForm({defaultValues: filters});
    const sendRequest = async (data) => {
        const post_data = {...data}
        Inertia.get(route('stocks.product-history'), post_data)
    }

    const updateValue = (item) => {
        const {product_id} = {...item}
        setValue('product_id', product_id, {shouldDirty: true})
    }

    return (
        <>
            <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                <Row>


                    <Col md={6}>
                        <Form.Group className="mb-3">
                            <Form.Label>Product:</Form.Label>
                            <Form.Control
                                {...register('product_id')}
                                hidden={true}/>
                            <Controller
                                render={({field}) => (
                                    <Select
                                        theme={theme => ({
                                            ...theme,
                                            borderRadius: 0,
                                            colors: {
                                                ...theme.colors,
                                                primary25: '#c27f69',
                                                primary: '#265c99b3',
                                            },
                                        })}
                                        {...field}
                                        defaultValue={product}
                                        options={products.data}
                                        onChange={(e) => {
                                            field.onChange(e)
                                            updateValue(e)
                                        }}

                                        getOptionValue={option => option['product_id']}
                                        getOptionLabel={option => option['name'] + ' ' + option['finish']}
                                    />
                                )}
                                control={control}
                                name={'product'}
                            />

                        </Form.Group>
                    </Col>
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

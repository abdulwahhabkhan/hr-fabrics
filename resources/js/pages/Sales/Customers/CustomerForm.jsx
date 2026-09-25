import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import {
    Panel,
    PanelBody,
    PanelFooter,
    PanelHeader,
} from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, InputGroup, Row, Tooltip } from 'react-bootstrap';
import OverlayTrigger from '@/components/ui/OverlayTrigger';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';
import StyledSelect from '@/components/StyledSelect';
import BackButton from '@/components/button/back';

const CustomerForm = () => {
    const {
        customer,
        errors: serverErrors,
        agents,
        brands,
        cities,
        discountTypes,
    } = usePage().props;
    const title = customer ? 'Edit Customer' : 'Add Customer';
    const [processing, setProcessing] = useState(false);
    const customer_agent = customer ? customer.agent : null;
    const city = customer ? customer.address.city : null;
    const {
        register,
        handleSubmit,
        setError,
        watch,
        control,
        formState: { errors },
    } = useForm({ defaultValues: customer });
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const agent = watch('agent', customer_agent);
    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        if (customer)
            Inertia.put(
                route('sales.customers.update', customer['id']),
                post_data,
                options,
            );
        else Inertia.post(route('sales.customers.store'), post_data, options);
    };

    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);
    return (
        <>
            <Head title="Customer Update" />
            <PageHeader
                title="Customer Update"
                buttons={
                    <>
                        <BackButton href={route('sales.customers.index')} />
                    </>
                }
            />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} />
                    <PanelBody>
                        <ErrorPanel errors={serverErrors} />
                        <form
                            action=""
                            className=""
                            onSubmit={handleSubmit(sendRequest)}
                        >
                            <Row>
                                <Col lg={12}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Agent:</Form.Label>
                                        <Controller
                                            render={({ field }) => (
                                                <StyledSelect
                                                    {...field}
                                                    defaultValue={
                                                        customer_agent
                                                    }
                                                    options={agents}
                                                    getOptionValue={(option) =>
                                                        option['id']
                                                    }
                                                    getOptionLabel={(option) =>
                                                        option['name']
                                                    }
                                                    isClearable
                                                />
                                            )}
                                            control={control}
                                            name={'agent'}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>
                            <Row>
                                {agent &&
                                    agent.id &&
                                    brands.map(({ id, name }, index) => {
                                        const field_name =
                                            'commission_rate.brand_' + id; //`commission_rate[${id}]`
                                        return (
                                            <Col sm={2} key={index}>
                                                <Form.Group className="mb-3">
                                                    <Form.Label>
                                                        {name}
                                                    </Form.Label>
                                                    <Form.Control
                                                        {...register(
                                                            field_name,
                                                            { required: true },
                                                        )}
                                                        isInvalid={
                                                            errors.commission_rate
                                                        }
                                                        placeholder={
                                                            'commission rate'
                                                        }
                                                    />
                                                </Form.Group>
                                            </Col>
                                        );
                                    })}
                            </Row>
                            <Row>
                                <Col sm={5}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Customer Name:</Form.Label>
                                        <InputGroup className="mb-3">
                                            <Form.Control
                                                {...register('name', {
                                                    required: true,
                                                })}
                                                isInvalid={errors.name}
                                                placeholder={'name'}
                                            />
                                        </InputGroup>
                                    </Form.Group>
                                </Col>
                                <Col sm={5}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Customer Urdu:</Form.Label>
                                        <InputGroup className="mb-3">
                                            <Form.Control
                                                className={'urdu'}
                                                {...register('name_urdu', {
                                                    required: true,
                                                })}
                                                isInvalid={errors.name_urdu}
                                                placeholder={'name in urdu'}
                                            />
                                        </InputGroup>
                                    </Form.Group>
                                </Col>
                                <Col sm={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Discount:</Form.Label>
                                        <InputGroup>
                                            <Form.Control
                                                {...register('discount', {
                                                    required: true,
                                                })}
                                                isInvalid={errors.discount}
                                                placeholder={'discount'}
                                            />
                                            <Form.Select
                                                aria-label="per meter or on total"
                                                {...register('discount_type', {
                                                    required: true,
                                                })}
                                                isInvalid={errors.discount_type}
                                            >
                                                {discountTypes &&
                                                    discountTypes.map(
                                                        (type, index) => {
                                                            return (
                                                                <option
                                                                    key={index}
                                                                    value={
                                                                        type.value
                                                                    }
                                                                >
                                                                    {type.label}
                                                                </option>
                                                            );
                                                        },
                                                    )}
                                            </Form.Select>
                                        </InputGroup>
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row>
                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Credit Allowed</Form.Label>
                                        <br />
                                        <div className="switcher">
                                            <input
                                                type="checkbox"
                                                id="switcher_checkbox"
                                                {...register('credit')}
                                            />
                                            <label htmlFor="switcher_checkbox" />
                                        </div>
                                    </Form.Group>
                                </Col>
                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Credit Limit: &nbsp;
                                            <OverlayTrigger
                                                placement={'bottom'}
                                                overlay={
                                                    <Tooltip>
                                                        Add zero value for
                                                        unlimited
                                                    </Tooltip>
                                                }
                                            >
                                                <Icon
                                                    icon={
                                                        'solar:question-circle-bold-duotone'
                                                    }
                                                />
                                            </OverlayTrigger>
                                        </Form.Label>
                                        <Form.Control
                                            {...register('limit', {
                                                required: true,
                                            })}
                                            isInvalid={errors.phone}
                                            placeholder={'credit limit'}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Phone:</Form.Label>
                                        <Form.Control
                                            {...register('phone', {
                                                email: true,
                                            })}
                                            isInvalid={errors.phone}
                                            placeholder={'phone'}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col sm={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Email:</Form.Label>
                                        <Form.Control
                                            {...register('email')}
                                            isInvalid={errors.email}
                                            placeholder={'email'}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>
                            <Form.Group className="mb-3">
                                <Form.Label> Address:</Form.Label>
                                <Form.Control
                                    {...register('address.address')}
                                    placeholder={'address'}
                                />
                            </Form.Group>
                            <Row>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label> City:</Form.Label>
                                        <Form.Select
                                            {...register('address.city', {
                                                required: true,
                                            })}
                                        >
                                            <option value="">
                                                Select City
                                            </option>
                                            {cities &&
                                                cities.map((city, index) => {
                                                    return (
                                                        <option key={index}>
                                                            {city.name}
                                                        </option>
                                                    );
                                                })}
                                        </Form.Select>
                                    </Form.Group>
                                </Col>

                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label> Region:</Form.Label>
                                        <Form.Control
                                            {...register('address.region')}
                                            placeholder={'region'}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>
                        </form>
                    </PanelBody>
                    <PanelFooter className={'text-center'}>
                        <BackButton
                            size={'md'}
                            href={route('sales.customers.index')}
                        />

                        <LoadingButton
                            processing={processing}
                            onClick={handleSubmit(sendRequest)}
                        >
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default CustomerForm;

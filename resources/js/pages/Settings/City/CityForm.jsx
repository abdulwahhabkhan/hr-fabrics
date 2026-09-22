import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelFooter, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { ErrorPanel, updateErrors } from '@/components/panel/ErrorPanel';


const CityForm = () => {
    const { city, errors: serverErrors } = usePage().props;
    const title = city ? "Edit City" : "Add City";
    const [processing, setProcessing] = useState(false);

    const defaultValues = city;
    const { register, handleSubmit, setError, formState: { errors } } = useForm({ defaultValues: defaultValues });
    const sendRequest = async (data) => {
        setProcessing(true);
        const options = {
            onFinish: () => {
                setProcessing(false);
            }
        };
        if (city)
            Inertia.put(route("settings.cities.update", city.id), { ...data }, options);
        else
            Inertia.post(route("settings.cities.store"), { ...data }, options);
    };
    useEffect(() => {
        if (!_.isEmpty(serverErrors)) {
            updateErrors(serverErrors, setError);
        }
    }, [serverErrors]);

    return (
        <>
            <Head title="City Update" />
            <PageHeader title="City Update" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} buttons={(
                        <>
                            <InertiaLink href={route("settings.cities.index")} className="btn btn-xs  btn-primary">
                                <Icon icon={"solar:reply-bold-duotone"} /> City List
                            </InertiaLink>
                        </>
                    )} />
                    <PanelBody>
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <ErrorPanel errors={serverErrors} />

                            <Row>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Name:</Form.Label>
                                        <Form.Control
                                            {...register("name", { required: true })}
                                            isInvalid={errors.name}
                                            placeholder={"city name"} />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Name (Urdu):</Form.Label>

                                        <Form.Control className={"text-right"}
                                                      {...register("name_urdu", { required: true })}
                                                      isInvalid={errors.name_urdu}
                                                      placeholder={"city urdu"} />
                                    </Form.Group>
                                </Col>
                            </Row>

                        </form>

                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <InertiaLink href={route("settings.cities.index")} className={"btn btn-white"}>
                            <Icon icon={"solar:reply-bold-duotone"} />
                        </InertiaLink>
                        &nbsp;
                        <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default CityForm;

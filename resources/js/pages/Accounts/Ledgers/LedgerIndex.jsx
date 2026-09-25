import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Button, Col, Form, InputGroup, Row } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import 'react-datetime/css/react-datetime.css';
import ledgers from '@/routes/accounts/ledgers';

const LedgerIndex = () => {
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const { accounts, date } = usePage().props;
    const { start_date, end_date } = date;
    const defaultLedger = { start_date, end_date };
    const {
        register,
        handleSubmit,
        formState: { errors },
        control
    } = useForm({ defaultValues: defaultLedger });
    const sendRequest = async (data) => {
        const post_data = { ...data };
        const { start_date, end_date, account } = post_data;

        Inertia.get(ledgers.show(account.id, {
            query: {
                start_date: start_date.toString(),
                end_date: end_date.toString()
            }
        }).url);
    };
    return (
        <>
            <Head title="Manage Ledgers" />
            <PageHeader title="Manage Ledgers" />
            <PageContent>
                <Panel>
                    <PanelHeader>Ledgers List</PanelHeader>
                    <PanelBody>
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>
                                <Col lg={{ span: 8, offset: 2 }}>
                                    <Form.Label>Select Account:</Form.Label>
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                options={accounts}
                                                getOptionValue={(option) => option["id"]}
                                                getOptionLabel={(option) =>
                                                    option["name"] + (option["city"] ? " " + option["city"] : "")
                                                }
                                                isClearable
                                            />
                                        )}
                                        control={control}
                                        name={"account"}
                                    />
                                </Col>
                            </Row>
                            <Row>
                                <Col lg={{ span: 6, offset: 3 }}>
                                    <InputGroup className="mb-3 mt-3">
                                        <InputGroup.Text>Date Range</InputGroup.Text>
                                        <Controller
                                            control={control}
                                            name="start_date"
                                            render={({ field }) => (
                                                <DatetimeComponent
                                                    value={field.value}
                                                    onBlur={field.onBlur}
                                                    name={field.name}
                                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                                    onChange={(e) =>
                                                        field.onChange(typeof e === "string" ? e : e.format("YYYY-MM-DD"))
                                                    }
                                                    closeOnSelect={true}
                                                    placeholder={"start date"}
                                                    timeFormat={false}
                                                />
                                            )}
                                        />
                                        <Controller
                                            control={control}
                                            name="end_date"
                                            render={({ field }) => (
                                                <DatetimeComponent
                                                    value={field.value}
                                                    onBlur={field.onBlur}
                                                    name={field.name}
                                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                                    onChange={(e) =>
                                                        field.onChange(typeof e === "string" ? e : e.format("YYYY-MM-DD"))
                                                    }
                                                    closeOnSelect={true}
                                                    placeholder={"end date"}
                                                    timeFormat={false}
                                                />
                                            )}
                                        />

                                        <Button type={"submit"} variant="primary" style={{ zIndex: 0 }}>
                                            View Ledger
                                        </Button>
                                    </InputGroup>
                                </Col>
                            </Row>
                        </form>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default LedgerIndex;

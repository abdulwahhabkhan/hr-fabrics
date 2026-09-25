import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import React, { useState } from 'react';
import { Button, InputGroup, Table } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import widgets from '@/routes/widgets';

export const SalePerMeterWidget = () => {
    const filters = {};
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const [values, setValues] = useState({
        start_date: filters.start_date || "",
        end_date: filters.end_date || ""
    });
    axios.get(widgets.averageSaleMeter().url).then((res) => {
        console.log(res.data);
    });
    const { setValue, control } = useForm([]);
    return (
        <Panel>
            <PanelHeader>
                Avg Sales Widget
                <div className="float-end">
                    <InputGroup className="mb-3 mt-3">
                        <Controller
                            control={control}
                            name="start_date"
                            render={({ field }) => (
                                <DatetimeComponent
                                    initialValue={new Date(filters.start_date)}
                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                    onChange={(e) =>
                                        setValues((values) => ({
                                            ...values,
                                            start_date: e.format("YYYY-MM-DD")
                                        }))
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
                                    initialValue={new Date(filters.end_date)}
                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                    onChange={(e) =>
                                        setValues((values) => ({
                                            ...values,
                                            end_date: e.format("YYYY-MM-DD")
                                        }))
                                    }
                                    closeOnSelect={true}
                                    placeholder={"end date"}
                                    timeFormat={false}
                                />
                            )}
                        />

                        <Button type={"button"} variant="primary" style={{ zIndex: 0 }}>
                            View Report
                        </Button>
                    </InputGroup>
                </div>
            </PanelHeader>
            <PanelBody>
                <Table bordered={true} hover={true}>
                    <thead>
                    <tr>
                        <th>Avg Per Transaction</th>
                        <th>Avg Per Meter</th>
                    </tr>
                    </thead>
                </Table>
            </PanelBody>
        </Panel>
    );
};

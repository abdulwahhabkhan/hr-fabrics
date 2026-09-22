import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { Button, InputGroup } from "react-bootstrap";
import Datetime from "react-datetime";
import { settings } from "@/config/page-settings";
import { Controller, useForm } from "react-hook-form";
import "react-datetime/css/react-datetime.css";

export default () => {
    const { filters } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;

    const [values, setValues] = useState({
        start_date: filters.start_date || "",
        end_date: filters.end_date || "",
    });

    const prevValues = usePrevious(values);
    const { control } = useForm(values);

    useEffect(() => {
        // https://reactjs.org/docs/hooks-faq.html#how-to-get-the-previous-props-or-state
        if (prevValues) {
            const { cancel, token } = axios.CancelToken.source();
            const timeOutId = setTimeout(() => {
                const query = Object.keys(pickBy(values)).length ? pickBy(values) : { remember: "forget" };
                Inertia.get(route(route().current()), query, {
                    replace: true,
                    preserveState: true,
                });
            }, 500);
            return () => cancel("No longer latest query") || clearTimeout(timeOutId);
        }
    }, [values]);

    function doSearch(e) {
        e.preventDefault();
        const query = Object.keys(pickBy(values)).length ? pickBy(values) : { remember: "forget" };
        Inertia.get(route(route().current()), query, {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <div className="default-search hidden-print">
            <div>
                <InputGroup className="m-0">
                    <Controller
                        control={control}
                        name="start_date"

                        render={({ field }) => (
                            <DatetimeComponent
                                key={`start_date-${values.start_date}`}
                                initialValue={values.start_date ? new Date(values.start_date) : undefined}
                                dateFormat={settings.SEARCH_DATE_FORMAT}
                                onChange={(e) =>
                                    e.format &&
                                    setValues((values) => ({
                                        ...values,
                                        start_date: e.format("YYYY-MM-DD"),
                                    }))
                                }
                                closeOnSelect={true}
                                inputProps={{ placeholder: "from date" }}
                                timeFormat={false}
                            />
                        )}
                    />
                    <Controller
                        control={control}
                        name="end_date"

                        render={({ field }) => (
                            <DatetimeComponent
                                key={`end_date-${values.end_date}`}
                                initialValue={values.end_date ? new Date(values.end_date) : undefined}
                                dateFormat={settings.SEARCH_DATE_FORMAT}
                                onChange={(e) =>
                                    e.format &&
                                    setValues((values) => ({
                                        ...values,
                                        end_date: e.format("YYYY-MM-DD"),
                                    }))
                                }
                                closeOnSelect={true}
                                inputProps={{ placeholder: "to date" }}
                                timeFormat={false}
                            />
                        )}
                    />
                    <Button type={"button"} onClick={doSearch} variant="primary" style={{ zIndex: 0 }}>
                        View Report
                    </Button>
                </InputGroup>
            </div>
        </div>
    );
};

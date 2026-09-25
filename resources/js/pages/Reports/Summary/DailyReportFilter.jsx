import React, { useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { Button, InputGroup } from "react-bootstrap";
import Datetime from "react-datetime";
import { settings } from "@/config/page-settings";
import { useForm } from "react-hook-form";
import "react-datetime/css/react-datetime.css";
import { Icon } from "@iconify/react";

export default () => {
    const { filters } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const [values, setValues] = useState({
        start_date: filters.start_date || "",
        end_date: filters.end_date || "",
    });

    const prevValues = usePrevious(values);
    const {
        register,
        handleSubmit,
        setError,
        control,
        watch,
        formState: { errors },
    } = useForm();

    function reset() {
        setValues({
            start_date: "",
            end_date: "",
        });
    }

    /*useEffect(() => {
        // https://reactjs.org/docs/hooks-faq.html#how-to-get-the-previous-props-or-state
        if (prevValues) {
            const {cancel, token} = axios.CancelToken.source();
            const timeOutId = setTimeout(() => {
                const query = Object.keys(pickBy(values)).length
                    ? pickBy(values)
                    : {remember: 'forget'};
                Inertia.get(window.location.pathname, query, {
                    replace: true,
                    preserveState: true
                });
            }, 500);
            return () => cancel("No longer latest query") || clearTimeout(timeOutId);
        }
    }, [values]);*/

    function handleChange(e) {
        const key = e.target.name;
        const value = e.target.value;

        setValues((values) => ({
            ...values,
            [key]: value,
        }));
    }

    function doSearch(e) {
        e.preventDefault();
        const query = Object.keys(pickBy(values)).length ? pickBy(values) : { remember: "forget" };
        Inertia.get(window.location.pathname, query, {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <div className="default-search">
            <div>
                <InputGroup className="">
                    <DatetimeComponent
                        initialValue={filters.start_date}
                        dateFormat={settings.SEARCH_DATE_FORMAT}
                        onChange={(e) =>
                            setValues((values) => ({
                                ...values,
                                start_date: e.format("YYYY-MM-DD"),
                            }))
                        }
                        closeOnSelect={true}
                        inputProps={{ name: "start_date" }}
                        placeholder={"start date"}
                        timeFormat={false}
                    />

                    <DatetimeComponent
                        initialValue={filters.end_date}
                        dateFormat={settings.SEARCH_DATE_FORMAT}
                        onChange={(e) =>
                            setValues((values) => ({
                                ...values,
                                end_date: e.format("YYYY-MM-DD"),
                            }))
                        }
                        closeOnSelect={true}
                        inputProps={{ name: "end_date" }}
                        placeholder={"end date"}
                        timeFormat={false}
                    />

                    <Button variant="outline-primary" onClick={doSearch}>
                        <Icon icon={"solar:magnifer-bold-duotone"} />
                        &nbsp;View Report
                    </Button>
                </InputGroup>
            </div>
        </div>
    );
};

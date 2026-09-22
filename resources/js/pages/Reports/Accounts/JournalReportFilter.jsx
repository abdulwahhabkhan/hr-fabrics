import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, FormSelect, InputGroup } from "react-bootstrap";
import Datetime from "react-datetime";
import { settings } from "@/config/page-settings";
import { useForm } from "react-hook-form";
import "react-datetime/css/react-datetime.css";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters, accounts } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const [values, setValues] = useState({
        date: filters.date || "",
        type: filters.type || "",
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
            date: "",
            type: "",
        });
    }

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

    function handleChange(e) {
        console.log(e);
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
        Inertia.get(route(route().current()), query, {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <div className="default-search">
            <div>
                <InputGroup className="mb-20px">
                    <FormSelect
                        name={"type"}
                        className="input-150"
                        autoComplete="off"
                        value={values.type}
                        onChange={handleChange}
                    >
                        <option value="">Account Type</option>
                        {accounts.map((account) => {
                            return (
                                <option key={account} value={account}>
                                    {account}
                                </option>
                            );
                        })}
                    </FormSelect>

                    <DatetimeComponent
                        initialValue={values.date}
                        dateFormat={settings.SEARCH_DATE_FORMAT}
                        onChange={(e) =>
                            setValues((values) => ({
                                ...values,
                                date: e.format("YYYY-MM-DD"),
                            }))
                        }
                        closeOnSelect={true}
                        inputProps={{ name: "date" }}
                        placeholder={"date"}
                        timeFormat={false}
                    />

                    <FilterButton onClick={reset} />
                </InputGroup>
            </div>
        </div>
    );
};

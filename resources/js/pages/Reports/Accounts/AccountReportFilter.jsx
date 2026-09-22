import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, FormSelect, InputGroup } from "react-bootstrap";
import { useForm } from "react-hook-form";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters, accounts } = usePage().props;

    const [values, setValues] = useState({
        name: filters.name || "",
        city: filters.city || "",
        type: filters.type || "",
        date: filters.date || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            name: "",
            city: "",
            type: "",
            date: "",
        });
    }

    const {
        setValue,
        control,
        formState: { errors },
    } = useForm(values);
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
                    <FormControl
                        placeholder="date"
                        type="date"
                        name={"date"}
                        className="input-150"
                        autoComplete="off"
                        value={values.date}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="name"
                        type="text"
                        name={"name"}
                        className=""
                        autoComplete="off"
                        value={values.name}
                        onChange={handleChange}
                    />

                    <FormControl
                        placeholder="city"
                        type="text"
                        name={"city"}
                        className=""
                        autoComplete="off"
                        value={values.city}
                        onChange={handleChange}
                    />

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

                    <FilterButton onClick={reset} />
                </InputGroup>
            </div>
        </div>
    );
};

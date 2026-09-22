import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, FormSelect, InputGroup } from "react-bootstrap";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters, types } = usePage().props;

    const [values, setValues] = useState({
        from_name: filters.from_name || "",
        to_name: filters.to_name || "",
        account: filters.account || "",
        reference_no: filters.reference_no || "",
        type: filters.type || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            from_name: "",
            account: "",
            reference_no: "",
            to_name: "",
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
                        <option value="">Types</option>
                        {types.map((type) => {
                            return (
                                <option key={type} value={type}>
                                    {type}
                                </option>
                            );
                        })}
                    </FormSelect>
                    <FormControl
                        placeholder="voucher no"
                        type="text"
                        name={"reference_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.reference_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="account"
                        type="text"
                        name={"account"}
                        className=""
                        autoComplete="off"
                        value={values.account}
                        onChange={handleChange}
                    />

                    {/*<FormControl
                        placeholder="to account"
                        type="text"
                        name={'to_account'}
                        className=""
                        autoComplete="off"
                        value={values.city}
                        onChange={handleChange}
                    />*/}

                    <FilterButton onClick={reset} />
                </InputGroup>
            </div>
        </div>
    );
};

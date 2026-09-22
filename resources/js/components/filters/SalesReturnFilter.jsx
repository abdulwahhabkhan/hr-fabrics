import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, InputGroup } from "react-bootstrap";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters } = usePage().props;

    const [values, setValues] = useState({
        customer_name: filters.customer_name || "",
        ref_no: filters.ref_no || "",
        order_no: filters.order_no || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            customer_name: "",
            ref_no: "",
            order_no: "",
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
                    <FormControl
                        placeholder="ref no"
                        type="text"
                        name={"ref_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.ref_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="order no"
                        type="text"
                        name={"order_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.order_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="customer name"
                        type="text"
                        name={"customer_name"}
                        className="input-white"
                        autoComplete="off"
                        value={values.customer_name}
                        onChange={handleChange}
                    />

                    {Object.keys(pickBy(values)).length > 0 && <FilterButton onClick={reset} />}
                </InputGroup>
            </div>
        </div>
    );
};

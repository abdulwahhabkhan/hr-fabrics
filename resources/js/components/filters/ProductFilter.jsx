import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, InputGroup } from "react-bootstrap";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters } = usePage().props;

    const [values, setValues] = useState({
        product_name: filters.product_name || "",
        vendor_name: filters.vendor_name || "",
        has_vendor: filters.has_vendor || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            product_name: "",
            has_vendor: "",
            vendor_name: "",
        });
    }

    useEffect(() => {
        // https://reactjs.org/docs/hooks-faq.html#how-to-get-the-previous-props-or-state
        if (prevValues) {
            const { cancel, token } = axios.CancelToken.source();
            const timeOutId = setTimeout(() => {
                const query = Object.keys(pickBy(values)).length ? pickBy(values) : { remember: "forget" };
                Inertia.get(window.location.pathname, query, {
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
        Inertia.get(window.location.pathname, query, {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <div className="default-search">
            <div>
                <InputGroup className="mb-20px">
                    <FormControl
                        placeholder="Search products by name..."
                        type="text"
                        name={"product_name"}
                        className="input-white"
                        autoComplete="off"
                        value={values.product_name}
                        onChange={handleChange}
                    />
                    {/*<FormControl
                        placeholder="vendor name"
                        type="text"
                        name={'vendor_name'}
                        className="input-250"
                        autoComplete="off"
                        value={values.vendor_name}
                        onChange={handleChange}
                    />*/}
                    <FormControl
                        as="select"
                        name={"has_vendor"}
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.has_vendor}
                        onChange={handleChange}
                    >
                        <option value={""}>All vendors</option>
                        <option value="yes">With vendor</option>
                        <option value="no">No vendor</option>
                    </FormControl>

                    {Object.keys(pickBy(values)).length > 0 && <FilterButton onClick={reset} />}
                </InputGroup>
            </div>
        </div>
    );
};

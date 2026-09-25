import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, InputGroup } from "react-bootstrap";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters } = usePage().props;

    const [values, setValues] = useState({
        supplier_name: filters.supplier_name || "",
        ref_no: filters.ref_no || "",
        bill_no: filters.bill_no || "",
        bilti_no: filters.bilti_no || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            supplier_name: "",
            ref_no: "",
            bill_no: "",
            bilti_no: "",
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
                        placeholder="ref no"
                        type="text"
                        name={"ref_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.ref_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="bill no"
                        type="text"
                        name={"bill_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.bill_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="bilti no"
                        type="text"
                        name={"bilti_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.bilti_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="supplier name"
                        type="text"
                        name={"supplier_name"}
                        className="input-white"
                        autoComplete="off"
                        value={values.supplier_name}
                        onChange={handleChange}
                    />

                    {Object.keys(pickBy(values)).length > 0 && <FilterButton onClick={reset} />}
                </InputGroup>
            </div>
        </div>
    );
};

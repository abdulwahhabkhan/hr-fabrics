import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, InputGroup } from "react-bootstrap";
import { STATUS_CLOSE, STATUS_OPEN } from "@/util/util";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters } = usePage().props;

    const [values, setValues] = useState({
        customer_name: filters.customer_name || "",
        invoice_no: filters.invoice_no || "",
        lot_no: filters.lot_no || "",
        paid: filters.paid || "",
        shipped: filters.shipped || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            customer_name: "",
            invoice_no: "",
            lot_no: "",
            paid: "",
            shipped: "",
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
                        placeholder="invoice no"
                        type="text"
                        name={"invoice_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.invoice_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="lot no"
                        type="text"
                        name={"lot_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.lot_no}
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
                    <FormControl
                        as="select"
                        name={"paid"}
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.paid}
                        onChange={handleChange}
                    >
                        <option value={""}>Paid</option>
                        <option value={1}>Yes</option>
                        <option value={0}>No</option>
                    </FormControl>
                    {/* <FormControl
                        as="select"
                        name={'shipped'}
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.shipped}
                        onChange={handleChange}
                    >
                        <option value={''}>Shipped</option>
                        <option value={1}>Yes</option>
                        <option value={0}>No</option>
                    </FormControl>*/}
                    <FormControl
                        as="select"
                        name={"status"}
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.status}
                        onChange={handleChange}
                    >
                        <option value={""}>Status</option>
                        <option value={STATUS_CLOSE}>Close</option>
                        <option value={STATUS_OPEN}>Open</option>
                    </FormControl>
                    {/*<Button variant="primary" type={"submit"} >
                        <Icon icon={"solar:magnifer-bold-duotone"} />&nbsp;Search
                    </Button>*/}

                    <FilterButton onClick={reset} />
                </InputGroup>
            </div>
        </div>
    );
};

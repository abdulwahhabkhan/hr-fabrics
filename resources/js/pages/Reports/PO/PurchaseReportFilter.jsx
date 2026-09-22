import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, FormSelect, InputGroup } from "react-bootstrap";
import { UNIT_BOX, UNIT_THAAN } from "@/util/util";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters } = usePage().props;

    const [values, setValues] = useState({
        supplier_name: filters.supplier_name || "",
        bill_no: filters.bill_no || "",
        bilti_no: filters.bilti_no || "",
        product_name: filters.product_name || "",
        finish: filters.finish || "",
        invoice_no: filters.invoice_no || "",
        type: filters.type || "",
        thaan: filters.thaan || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            supplier_name: "",
            invoice_no: "",
            type: "",
            thaan: "",
            bill_no: "",
            bilti_no: "",
            finish: "",
            product_name: "",
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
                        placeholder="bilti no"
                        type="text"
                        name={"bilti_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.bilti_no}
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
                        placeholder="product"
                        type="text"
                        name={"product_name"}
                        autoComplete="off"
                        value={values.product_name}
                        onChange={handleChange}
                    />

                    <FormControl
                        placeholder="finish"
                        type="text"
                        name={"finish"}
                        className="input-white input-150"
                        autoComplete="off"
                        value={values.finish}
                        onChange={handleChange}
                    />
                    <FormSelect
                        name={"type"}
                        className="input-150"
                        autoComplete="off"
                        value={values.type}
                        onChange={handleChange}
                    >
                        <option value={""}>Type</option>
                        <option value={UNIT_BOX}>{UNIT_BOX}</option>
                        <option value={UNIT_THAAN}>{UNIT_THAAN}</option>
                    </FormSelect>
                    {/*<Button variant="primary" type={"submit"} >
                        <Icon icon={"solar:magnifer-bold-duotone"} />&nbsp;Search
                    </Button>*/}

                    <FilterButton onClick={reset} />
                </InputGroup>
            </div>
        </div>
    );
};

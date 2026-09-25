import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, FormSelect, InputGroup } from "react-bootstrap";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters, vendors, brands } = usePage().props;
    const [values, setValues] = useState({
        product_name: filters.product_name || "",
        vendor: filters.vendor || "",
        brand: filters.brand || "",
        unit: filters.unit || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            product_name: "",
            brand: "",
            vendor: "",
            unit: "",
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
                    only: ["products"],
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
                        placeholder="product name"
                        type="text"
                        name={"product_name"}
                        className="input-white"
                        autoComplete="off"
                        value={values.product_name}
                        onChange={handleChange}
                    />

                    <FormSelect
                        name={"vendor"}
                        autoComplete="off"
                        value={values.vendor}
                        onChange={handleChange}
                    >
                        <option value={""}>Vendor</option>
                        {vendors.map((vendor) => {
                            return (
                                <option key={vendor.id} value={vendor.id}>
                                    {vendor.name}
                                </option>
                            );
                        })}
                    </FormSelect>

                    <FormSelect
                        name={"brand"}
                        autoComplete="off"
                        value={values.brand}
                        onChange={handleChange}
                    >
                        <option value={""}>Brand</option>
                        {brands.map((brand) => {
                            return (
                                <option key={brand.id} value={brand.id}>
                                    {brand.name}
                                </option>
                            );
                        })}
                    </FormSelect>
                    <FormSelect
                        name={"unit"}
                        className="form-select select-100"
                        autoComplete="off"
                        value={values.unit}
                        onChange={handleChange}
                    >
                        <option value={""}>Unit</option>
                        <option value="Thaan">Thaan</option>
                        <option value="Box">Box</option>
                    </FormSelect>

                    <FilterButton onClick={reset} />
                </InputGroup>
            </div>
        </div>
    );
};

import React, { useEffect, useState } from "react";
import { Inertia, usePage } from "@/util/Inertia";
import { usePrevious } from "react-use";
import pickBy from "lodash/pickBy";
import { FormControl, InputGroup } from "react-bootstrap";
import FilterButton from "@/components/button/FilterButton.jsx";

export default () => {
    const { filters } = usePage().props;

    const [values, setValues] = useState({
        search: filters.search || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            search: "",
        });
    }

    useEffect(() => {
        // https://reactjs.org/docs/hooks-faq.html#how-to-get-the-previous-props-or-state
        if (prevValues) {
            const query = Object.keys(pickBy(values)).length ? pickBy(values) : { remember: "forget" };
            Inertia.get(route(route().current()), query, {
                replace: true,
                preserveState: true,
            });
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

    return (
        <div className="default-search">
            <div>
                <InputGroup className="mb-4">
                    <FormControl
                        placeholder="Search..."
                        type="text"
                        name={"search"}
                        className="input-white"
                        autoComplete="off"
                        value={values.search}
                        onChange={handleChange}
                    />

                    {Object.keys(pickBy(values)).length > 0 && <FilterButton onClick={reset} />}
                </InputGroup>
            </div>
        </div>
    );
};

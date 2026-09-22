import React, { useEffect, useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import { usePrevious } from 'react-use';
import pickBy from 'lodash/pickBy';
import { FormControl, InputGroup } from 'react-bootstrap';
import FilterButton from '@/components/button/FilterButton.jsx';
import { ORDER_CLOSED, ORDER_OPEN } from '@/util/util';

export default () => {
    const { filters } = usePage().props;

    const [values, setValues] = useState({
        transfer_no: filters.transfer_no || "",
        store: filters.store || "",
        status: filters.status || "",
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            transfer_no: "",
            store: "",
            status: "",
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

    return (
        <div className="page-filters">
            <div>
                <InputGroup className="mb-20px">
                    <FormControl
                        placeholder="Transfer No"
                        type="text"
                        name={"transfer_no"}
                        className="input-150"
                        autoComplete="off"
                        value={values.transfer_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="Store"
                        type="text"
                        name={"store"}
                        className="input-white"
                        autoComplete="off"
                        value={values.store}
                        onChange={handleChange}
                    />
                    <FormControl
                        as="select"
                        name={"status"}
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.status}
                        onChange={handleChange}
                    >
                        <option value={""}>Status</option>
                        <option value={ORDER_OPEN}>Open</option>
                        <option value={ORDER_CLOSED}>Closed</option>
                    </FormControl>

                    {Object.keys(pickBy(values)).length > 0 && <FilterButton onClick={reset} />}
                </InputGroup>
            </div>
        </div>
    );
};

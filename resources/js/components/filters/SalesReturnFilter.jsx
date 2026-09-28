import React, { useEffect, useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import { usePrevious } from 'react-use';
import pickBy from 'lodash/pickBy';
import { FormControl, InputGroup } from 'react-bootstrap';
import FilterButton from '@/components/button/FilterButton.jsx';

const EMPTY_FILTERS = {
    ref_no: '',
    order_no: '',
    customer_name: '',
    status: '',
};

export default () => {
    const { filters = {} } = usePage().props;

    const [values, setValues] = useState(
        Object.fromEntries(Object.keys(EMPTY_FILTERS).map((key) => [key, filters[key] ?? ''])),
    );

    const prevValues = usePrevious(values);

    function reset() {
        setValues(EMPTY_FILTERS);
    }

    useEffect(() => {
        if (prevValues) {
            const { cancel } = axios.CancelToken.source();
            const timeOutId = setTimeout(() => {
                const query = Object.keys(pickBy(values)).length
                    ? pickBy(values)
                    : { remember: 'forget' };
                Inertia.get(window.location.pathname, query, {
                    replace: true,
                    preserveState: true,
                });
            }, 400);
            return () =>
                cancel('No longer latest query') || clearTimeout(timeOutId);
        }
    }, [values]);

    function handleChange(e) {
        const key = e.target.name;
        const value = e.target.value;

        setValues((prev) => ({
            ...prev,
            [key]: value,
        }));
    }

    return (
        <div className="default-search mb-20px">
            <div>
                <InputGroup>
                    <FormControl
                        placeholder="Ref no"
                        type="text"
                        name="ref_no"
                        className="input-150"
                        autoComplete="off"
                        value={values.ref_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="Order no"
                        type="text"
                        name="order_no"
                        className="input-150"
                        autoComplete="off"
                        value={values.order_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="Search by customer name..."
                        type="text"
                        name="customer_name"
                        className="input-white"
                        autoComplete="off"
                        value={values.customer_name}
                        onChange={handleChange}
                    />
                    <FormControl
                        as="select"
                        name="status"
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.status}
                        onChange={handleChange}
                    >
                        <option value="">All Status</option>
                        <option value="0">Open</option>
                        <option value="1">Confirmed</option>
                    </FormControl>

                    {Object.keys(pickBy(values)).length > 0 && (
                        <FilterButton onClick={reset} />
                    )}
                </InputGroup>
            </div>
        </div>
    );
};

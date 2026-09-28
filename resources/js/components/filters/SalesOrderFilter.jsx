import React, { useEffect, useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import { usePrevious } from 'react-use';
import pickBy from 'lodash/pickBy';
import { FormControl, InputGroup } from 'react-bootstrap';
import FilterButton from '@/components/button/FilterButton.jsx';

const EMPTY_FILTERS = {
    invoice_no: '',
    customer_name: '',
    customer_city: '',
    paid: '',
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
                        placeholder="Invoice no"
                        type="text"
                        name="invoice_no"
                        className="input-150"
                        autoComplete="off"
                        value={values.invoice_no}
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
                        placeholder="City"
                        type="text"
                        name="customer_city"
                        className="input-150"
                        autoComplete="off"
                        value={values.customer_city}
                        onChange={handleChange}
                    />
                    <FormControl
                        as="select"
                        name="paid"
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.paid}
                        onChange={handleChange}
                    >
                        <option value="">All Payments</option>
                        <option value="1">Paid</option>
                        <option value="0">Credit</option>
                    </FormControl>
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
                        <option value="2">Cancelled</option>
                    </FormControl>

                    {Object.keys(pickBy(values)).length > 0 && (
                        <FilterButton onClick={reset} />
                    )}
                </InputGroup>
            </div>
        </div>
    );
};

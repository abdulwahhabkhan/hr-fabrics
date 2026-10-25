import React, { useEffect, useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import { usePrevious } from 'react-use';
import pickBy from 'lodash/pickBy';
import { FormControl, InputGroup } from 'react-bootstrap';
import FilterButton from '@/components/button/FilterButton.jsx';
import { useStockLocations } from '@/util/util';

const EMPTY_FILTERS = {
    transfer_no: '',
    product: '',
    location: '',
    status: '',
};

export default () => {
    const { filters = {} } = usePage().props;
    const { locations } = useStockLocations();

    const [values, setValues] = useState(
        Object.fromEntries(Object.keys(EMPTY_FILTERS).map((key) => [key, filters[key] ?? ''])),
    );

    const prevValues = usePrevious(values);

    function reset() {
        setValues(EMPTY_FILTERS);
    }

    useEffect(() => {
        if (prevValues) {
            const timeOutId = setTimeout(() => {
                const query = Object.keys(pickBy(values)).length ? pickBy(values) : { remember: 'forget' };
                Inertia.get(window.location.pathname, query, {
                    replace: true,
                    preserveState: true,
                });
            }, 400);
            return () => clearTimeout(timeOutId);
        }
    }, [values]);

    function handleChange(e) {
        const { name, value } = e.target;
        setValues((prev) => ({ ...prev, [name]: value }));
    }

    return (
        <div className="default-search mb-20px">
            <div>
                <InputGroup>
                    <FormControl
                        placeholder="Transfer no"
                        type="text"
                        name="transfer_no"
                        className="input-150"
                        autoComplete="off"
                        value={values.transfer_no}
                        onChange={handleChange}
                    />
                    <FormControl
                        placeholder="Search by product..."
                        type="text"
                        name="product"
                        className="input-white"
                        autoComplete="off"
                        value={values.product}
                        onChange={handleChange}
                    />
                    <FormControl
                        as="select"
                        name="location"
                        className="form-select input-150"
                        value={values.location}
                        onChange={handleChange}
                    >
                        <option value="">All Locations</option>
                        {locations.map(({ id, name }) => (
                            <option key={id} value={id}>
                                {name}
                            </option>
                        ))}
                    </FormControl>
                    <FormControl
                        as="select"
                        name="status"
                        className="form-select input-150"
                        value={values.status}
                        onChange={handleChange}
                    >
                        <option value="">All Status</option>
                        <option value="Open">Draft</option>
                        <option value="Close">Moved</option>
                    </FormControl>

                    {Object.keys(pickBy(values)).length > 0 && <FilterButton onClick={reset} />}
                </InputGroup>
            </div>
        </div>
    );
};

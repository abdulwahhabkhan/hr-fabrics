import React, { useEffect, useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import { usePrevious } from 'react-use';
import pickBy from 'lodash/pickBy';
import { FormControl, InputGroup } from 'react-bootstrap';
import FilterButton from '@/components/button/FilterButton.jsx';
import { useStockLocations } from '@/util/util';

const EMPTY_FILTERS = {
    search: '',
    location: '',
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
                Inertia.get(window.location.pathname, pickBy(values), {
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
                        placeholder="Search by product..."
                        type="text"
                        name="search"
                        className="input-white"
                        autoComplete="off"
                        value={values.search}
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

                    {Object.keys(pickBy(values)).length > 0 && <FilterButton onClick={reset} />}
                </InputGroup>
            </div>
        </div>
    );
};

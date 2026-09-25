import React, { useEffect, useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import { usePrevious } from 'react-use';
import pickBy from 'lodash/pickBy';
import { FormControl, InputGroup } from 'react-bootstrap';
import FilterButton from '@/components/button/FilterButton.jsx';

export default () => {
    const { filters = {}, cities = [], agents = [] } = usePage().props;

    const [values, setValues] = useState({
        search: filters.search || '',
        city: filters.city || '',
        agent_id: filters.agent_id || '',
        status: filters.status || '',
        credit: filters.credit || '',
    });

    const prevValues = usePrevious(values);

    function reset() {
        setValues({
            search: '',
            city: '',
            agent_id: '',
            status: '',
            credit: '',
        });
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
                <InputGroup className="">
                    <FormControl
                        placeholder="Search by name, urdu, phone, city..."
                        type="text"
                        name="search"
                        className="input-white"
                        autoComplete="off"
                        value={values.search}
                        onChange={handleChange}
                    />
                    <FormControl
                        as="select"
                        name="city"
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.city}
                        onChange={handleChange}
                    >
                        <option value="">All Cities</option>
                        {cities.map((city, idx) => (
                            <option key={idx} value={city.name}>
                                {city.name}
                            </option>
                        ))}
                    </FormControl>
                    <FormControl
                        as="select"
                        name="agent_id"
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.agent_id}
                        onChange={handleChange}
                    >
                        <option value="">All Agents</option>
                        {agents.map((agent) => (
                            <option key={agent.id} value={agent.id}>
                                {agent.name}
                            </option>
                        ))}
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
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </FormControl>
                    <FormControl
                        as="select"
                        name="credit"
                        className="form-select input-150"
                        autoComplete="off"
                        value={values.credit}
                        onChange={handleChange}
                    >
                        <option value="">All Credit</option>
                        <option value="1">Credit Allowed</option>
                        <option value="0">Cash Only</option>
                    </FormControl>

                    {Object.keys(pickBy(values)).length > 0 && (
                        <FilterButton onClick={reset} />
                    )}
                </InputGroup>
            </div>
        </div>
    );
};

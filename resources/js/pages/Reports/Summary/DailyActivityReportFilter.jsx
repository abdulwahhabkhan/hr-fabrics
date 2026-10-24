import React, { useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import pickBy from 'lodash/pickBy';
import { Button, InputGroup } from 'react-bootstrap';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import 'react-datetime/css/react-datetime.css';
import { Icon } from '@iconify/react';

export default () => {
    const { filters } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const [values, setValues] = useState({
        date: '',
    });

    function doSearch(e) {
        e.preventDefault();
        Inertia.get(window.location.pathname, pickBy(values), {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <div className="default-search">
            <div>
                <InputGroup className="">
                    <DatetimeComponent
                        initialValue={filters.date}
                        dateFormat={settings.SEARCH_DATE_FORMAT}
                        onChange={(e) =>
                            setValues((values) => ({
                                ...values,
                                date: e.format('YYYY-MM-DD'),
                            }))
                        }
                        closeOnSelect={true}
                        inputProps={{ name: 'date' }}
                        placeholder={'date'}
                        timeFormat={false}
                    />

                    <Button variant="outline-primary" onClick={doSearch}>
                        <Icon icon={'andrsrxn:database-search'} />
                        &nbsp;View Report
                    </Button>
                </InputGroup>
            </div>
        </div>
    );
};

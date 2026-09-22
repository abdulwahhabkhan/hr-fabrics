import * as React from 'react';
import { format as DateFormat } from 'date-fns';
import { settings } from '@/config/page-settings.jsx';

export const Moment = ({ date }) => {
    let pdate = date;
    try {
        pdate = DateFormat(date, settings.DATE_FORMAT);
    } catch (error) {
        console.error('Date time invalid: ', error);
    }

    return <>{pdate}</>;
};

export const MomentFull = ({ date }) => {
    let pdate = date;
    try {
        pdate = DateFormat(date, 'dd MMM yyyy, HH:mm aa');
    } catch (error) {
        console.error('Date time invalid: ', error);
    }

    return <>{pdate}</>;
};

export default Moment;

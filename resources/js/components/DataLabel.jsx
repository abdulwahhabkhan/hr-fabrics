import React from 'react';
import Moment from '@/components/Moment.jsx';
import { settings } from '@/config/page-settings.jsx';

export default function DataLabel({label, value, parentClass, date}) {
    return (
        <>
            <dl className={'d-flex gap-2 mb-0 align-items-center' + (parentClass ? ' ' + parentClass : '')}>
                <dt className="label">{label} :</dt>
                {value && (
                    <dd className="info mb-0 ">{value}</dd>
                )}

                {date && (
                    <dd className="info mb-0">
                        <Moment
                            format={settings.INVOICE_FORMAT}
                            date={date}/>
                    </dd>
                )}

            </dl>
        </>
    )
}

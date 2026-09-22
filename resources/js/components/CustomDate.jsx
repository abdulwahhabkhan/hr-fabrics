import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import React from 'react';
import { OverlayTrigger, Tooltip } from 'react-bootstrap';


export const Date = ({date}) => {
    return (
        <>
            <OverlayTrigger
                placement={'bottom'}
                overlay={
                    <Tooltip>
                        {date}
                    </Tooltip>
                }
            >
                <span>
                    <Moment
                        format={settings.DATE_FORMAT}
                        date={date}/>
                </span>

            </OverlayTrigger>

        </>
    )
}

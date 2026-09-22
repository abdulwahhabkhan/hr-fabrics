import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import React from 'react';
import { Tooltip } from 'react-bootstrap';
import OverlayTrigger from '@/components/ui/OverlayTrigger';


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

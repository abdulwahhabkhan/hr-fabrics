import React from 'react';
import { Icon } from '@iconify/react';

export default function ({ className, ...props }) {
    return (
        <button className={"btn btn-sm btn-white hidden-print " + className} onClick={() => window.print()} {...props}>
            <Icon icon={"solar:printer-bold-duotone"}/>
            Print
        </button>
    );
}

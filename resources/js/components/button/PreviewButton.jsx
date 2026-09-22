import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia.jsx';
import React from 'react';

const PreviewButton = ({href, className, size, label, ...props}) => {
    let sizeClass = 'btn-sm'
    if (size === 'xs') {
        sizeClass = 'btn-xs'
    }
    if (size === 'lg') {
        sizeClass = 'btn-lg'
    }
    if (label === undefined) {
        label = 'Preview'
    }
    return (
        <InertiaLink href={href} className={`btn  btn-white  ${className} ${sizeClass}`} {...props}>
            <Icon icon={"solar:documents-bold-duotone"}/>
            <span className={'d-none d-md-inline'}>&nbsp;{label}</span>
        </InertiaLink>
    )
}


export default PreviewButton;
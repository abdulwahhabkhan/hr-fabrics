import { Icon } from '@iconify/react';
import React from 'react';
import { Link } from '@/util/Inertia.jsx';

type BackButtonSize = 'xs' | 'sm' | 'md' | 'lg';

interface BackButtonProps extends Omit<
    React.ComponentProps<typeof Link>,
    'className' | 'size'
> {
    href: string;
    className?: string;
    size?: BackButtonSize;
    label?: string;
}

const BackButton = ({
    href,
    className = '',
    size,
    label = 'Back',
    ...props
}: BackButtonProps) => {
    let sizeClass = 'btn-sm';

    if (size === 'xs') {
        sizeClass = 'btn-xs';
    }

    if (size === 'md') {
        sizeClass = 'btn-md';
    }

    if (size === 'lg') {
        sizeClass = 'btn-lg';
    }

    return (
        <Link
            href={href}
            className={`btn  btn-white ${className} ${sizeClass}`}
            {...props}
        >
            <Icon icon={'iconamoon:arrow-left-1-duotone'} />
            <span className={'d-none d-md-inline'}>&nbsp;{label}</span>
        </Link>
    );
};

export default BackButton;

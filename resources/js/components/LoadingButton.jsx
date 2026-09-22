import React from 'react';
import { Button, Spinner } from 'react-bootstrap';
import { Icon } from '@iconify/react';

export default ({
                    children = null,
                    disabled = false,
                    processing = false,
                    className = null,
                    variant = 'success',
                    ...rest
                }) => {
    function showIcon() {
        return processing ? (
                <div className="position-absolute d-flex justify-content-center align-items-center inset-0">
                    <Spinner
                        as="span"
                        animation="border"
                        size="sm"
                        role="status"
                        aria-hidden="true"
                    />
                </div>
            )
            : null;
    }

    const buttonDisabled = disabled || processing;
    className += processing ? ' loading position-relative' : ' position-relative';
    return (
        <>
            <Button className={className} disabled={buttonDisabled} variant={variant} {...rest}>
                {showIcon()}
                <Icon icon={"solar:clipboard-check-bold-duotone"} />
                {children}
            </Button>
        </>
    )

}

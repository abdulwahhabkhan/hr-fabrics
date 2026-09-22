import React from 'react';
import { Icon } from '@iconify/react';

import { Tooltip } from 'react-bootstrap';
import OverlayTrigger from '@/components/ui/OverlayTrigger';
import { Inertia, Link, router } from '@/util/Inertia';
import { confirmDelete, confirmSwal } from '@/util/swal';

export const ConfirmAction = ({ action, id, icon, tooltip = 'Confirm' }) => {
    const handleClick = async (event) => {
        event.preventDefault();
        const { isConfirmed } = await confirmSwal({
            text: 'You want to proceed with this action!',
        });
        if (isConfirmed) {
            router.post(route(action, id));
        }
    };
    return (
        <a href="" onClick={handleClick}>
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>{tooltip}</Tooltip>}
            >
                <Icon icon={icon} />
            </OverlayTrigger>
        </a>
    );
};
/**
 *
 * @returns {React.AnchorHTMLAttributes<HTMLAnchorElement>}
 */
export const Delete = ({ action, id }) => {
    const handleClick = async (event) => {
        event.preventDefault();
        await confirmDelete({
            onConfirm: () =>
                new Promise((resolve, reject) => {
                    router.delete(route(action, id), {
                        preserveScroll: true,
                        onSuccess: () => resolve(),
                        onError: () => reject(),
                    });
                }),
        });
    };
    return (
        <button
            type="button"
            onClick={handleClick}
            className="btn btn-link p-0"
        >
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>Delete</Tooltip>}
            >
                <Icon
                    icon={'solar:trash-bin-trash-line-duotone'}
                    className={'text-danger-500'}
                />
            </OverlayTrigger>
        </button>
    );
};

export const ToggleAction = ({ action, id, message = '', icon = null }) => {
    const handleClick = async (event) => {
        event.preventDefault();
        const { isConfirmed } = await confirmSwal({
            text: message ? message : 'You are going to toggle this record!',
        });
        if (isConfirmed) {
            router.delete(route(action, id));
        }
    };
    return (
        <a href="" onClick={handleClick}>
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>Toggle</Tooltip>}
            >
                <Icon icon={icon ?? 'solar:settings-bold-duotone'} />
            </OverlayTrigger>
        </a>
    );
};

export const UnLock = ({ action, id, children }) => {
    const handleClick = async (event) => {
        event.preventDefault();
        const { isConfirmed } = await confirmSwal({
            title: 'You are going to unlock...',
            text: 'You will not be able to recover this resource!',
            confirmButtonText: 'Confirm',
            confirmButtonStyle: 'danger',
        });
        if (isConfirmed) {
            Inertia.post(route(action, id));
        }
    };
    return (
        <button
            className={
                'btn btn-link p-0' +
                (children
                    ? ' d-flex align-items-center gap-2 w-100 px-3 py-2'
                    : '')
            }
            onClick={handleClick}
        >
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>Unlock Record</Tooltip>}
            >
                <Icon
                    className={'text-danger-600'}
                    icon={'solar:key-square-bold-duotone'}
                />
            </OverlayTrigger>
            {children}
        </button>
    );
};
export const UnLockDropdownItem = ({ action, id, children }) => {
    const handleClick = async (event) => {
        event.preventDefault();
        const { isConfirmed } = await confirmSwal({
            title: 'You are going to unlock...',
            text: 'You will not be able to recover this resource!',
            confirmButtonText: 'Confirm',
            confirmButtonStyle: 'danger',
        });
        if (isConfirmed) {
            Inertia.post(route(action, id));
        }
    };
    return (
        <button
            className={'btn dropdown-item border-top justify-content-start'}
            onClick={handleClick}
        >
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>Unlock Record</Tooltip>}
            >
                <Icon
                    className={'text-danger-600'}
                    icon={'solar:key-square-bold-duotone'}
                />
            </OverlayTrigger>
            {children || 'Unlock Record'}
        </button>
    );
};

export const DeleteAjax = ({ onDelete, id, className }) => {
    const handleClick = async (event) => {
        event.preventDefault();
        const { isConfirmed } = await confirmDelete();
        if (isConfirmed) {
            onDelete(id);
        }
    };
    return (
        <button
            className={'btn btn-link p-0 b-2 ' + className}
            type="button"
            onClick={handleClick}
        >
            <Icon
                icon={'solar:trash-bin-trash-bold-duotone'}
                className={'text-danger-600'}
            />
        </button>
    );
};

export const DeleteAction = ({ onDelete, id }) => {
    const handleClick = async (event) => {
        event.preventDefault();
        const { isConfirmed } = await confirmDelete();
        if (isConfirmed) {
            onDelete(id);
        }
    };
    return (
        <button
            className={'btn btn-link p-0 '}
            type="button"
            onClick={handleClick}
        >
            <Icon
                icon={'solar:trash-bin-trash-bold-duotone'}
                className={'text-danger-500'}
            />
        </button>
    );
};

export const Edit = (props) => {
    return (
        <>
            <button className={'btn btn-link p-0 '} {...props}>
                <OverlayTrigger
                    placement={'bottom'}
                    overlay={<Tooltip>Edit</Tooltip>}
                >
                    <Icon icon={'solar:pen-2-bold-duotone'} />
                </OverlayTrigger>
            </button>
        </>
    );
};
/**
 *
 * @param {React.AnchorHTMLAttributes<HTMLAnchorElement>} props
 * @returns {React.AnchorHTMLAttributes<HTMLAnchorElement>}
 */
export const InertiaEdit = (props) => {
    return (
        <>
            <Link {...props}>
                <OverlayTrigger
                    placement={'bottom'}
                    overlay={<Tooltip>Edit</Tooltip>}
                >
                    <Icon icon={'solar:pen-2-bold-duotone'} />
                </OverlayTrigger>
            </Link>
        </>
    );
};
/**
 *
 * @param {React.AnchorHTMLAttributes<HTMLAnchorElement>} props
 * @returns {React.AnchorHTMLAttributes<HTMLAnchorElement>}
 */
export const InertiaView = (props) => {
    return (
        <>
            <Link {...props}>
                <OverlayTrigger
                    placement={'bottom'}
                    overlay={
                        <Tooltip>
                            {props.link_title
                                ? props.link_title
                                : 'View Details'}
                        </Tooltip>
                    }
                >
                    <Icon
                        icon={
                            props.icon
                                ? props.icon
                                : 'solar:documents-bold-duotone'
                        }
                    />
                </OverlayTrigger>
            </Link>
        </>
    );
};

export const ShippedIcon = ({ shipped }) => {
    return (
        <>
            <span className={'' + (shipped ? 'text-success' : 'text-muted')}>
                <OverlayTrigger
                    placement={'bottom'}
                    overlay={
                        <Tooltip>{shipped ? 'Shipped' : 'In Progress'}</Tooltip>
                    }
                >
                    <Icon icon={'solar:delivery-bold-duotone'} />
                </OverlayTrigger>
            </span>
        </>
    );
};

export const FromShop = ({ shop }) => {
    return (
        <>
            <span
                className={
                    '' + (shop !== 'Online' ? 'text-success' : 'text-muted')
                }
            >
                <OverlayTrigger
                    placement={'bottom'}
                    overlay={<Tooltip>{shop}</Tooltip>}
                >
                    <Icon icon={'solar:bag-bold-duotone'} />
                </OverlayTrigger>
            </span>
        </>
    );
};

export const PaidIcon = ({ paid }) => {
    return (
        <>
            <span className={'' + (paid ? 'text-success' : 'text-muted')}>
                <OverlayTrigger
                    placement={'bottom'}
                    overlay={<Tooltip>{paid ? 'Paid' : 'UnPaid'}</Tooltip>}
                >
                    <Icon icon={'solar:bill-check-bold-duotone'} />
                </OverlayTrigger>
            </span>
        </>
    );
};

/**
 *
 * @param {React.AnchorHTMLAttributes<HTMLAnchorElement>} props
 * @returns {React.AnchorHTMLAttributes<HTMLAnchorElement>}
 */
export const InertiaInventory = (props) => {
    return (
        <a {...props}>
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>Inventory</Tooltip>}
            >
                <Icon icon={'solar:clipboard-list-bold-duotone'} />
            </OverlayTrigger>
        </a>
    );
};
/**
 *
 * @param {React.AnchorHTMLAttributes<HTMLAnchorElement>} props
 * @returns {React.AnchorHTMLAttributes<HTMLAnchorElement>}
 */
export const InertiaInventoryAction = (props) => {
    return (
        <a
            {...props}
            className={'dropdown-item border-top justify-content-start'}
        >
            <Icon icon={'solar:clipboard-list-bold-duotone'} /> View Inventory
        </a>
    );
};
/**
 *
 * @param {React.AnchorHTMLAttributes<HTMLAnchorElement>} props
 */
export const InertiaLedger = (props) => {
    return (
        <a {...props}>
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>View Ledger</Tooltip>}
            >
                <Icon icon={'stash:billing-info-duotone'} />
            </OverlayTrigger>
        </a>
    );
};

/**
 *
 * @param {React.AnchorHTMLAttributes<HTMLAnchorElement>} props
 */
export const InertiaLedgerAction = (props) => {
    return (
        <a
            {...props}
            className={'dropdown-item border-top justify-content-start'}
        >
            <Icon icon={'stash:billing-info-duotone'} /> View Ledger
        </a>
    );
};

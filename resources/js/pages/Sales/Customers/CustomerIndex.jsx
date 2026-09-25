import React, { useState } from 'react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import CustomerFilter from '@/components/filters/CustomerFilter.jsx';
import { ConfirmAction, InertiaEdit, InertiaView } from '@/components/Actions';
import { Badge } from 'react-bootstrap';
import PaginationFull from '@/components/PaginationFull';
import NoData from '@/components/NoData';
import { NumberFormat } from '@/util/NumberFormat';

const Customers = () => {
    const { customers, canAdd = true, canUpdate = true } = usePage().props;
    const { data = [] } = customers;
    const meta = customers.meta || customers;

    const [viewMode, setViewMode] = useState(() => {
        try {
            return localStorage.getItem('customer_view_mode') || 'cards';
        } catch {
            return 'cards';
        }
    });

    const handleViewChange = (mode) => {
        setViewMode(mode);
        try {
            localStorage.setItem('customer_view_mode', mode);
        } catch {
            // ignore localStorage errors
        }
    };

    return (
        <>
            <Head title="Customers List" />
            <PageHeader
                title="Customers List"
                buttons={
                    <div className="d-flex align-items-center gap-2">
                        <div className="btn-group btn-group-sm bg-white rounded border">
                            <button
                                type="button"
                                className={`btn btn-sm ${viewMode === 'cards' ? 'btn-primary' : 'btn-light border-0'}`}
                                onClick={() => handleViewChange('cards')}
                                title="Card View"
                            >
                                <Icon
                                    icon="solar:widget-5-bold-duotone"
                                    className="me-1"
                                />
                                Cards
                            </button>
                            <button
                                type="button"
                                className={`btn btn-sm ${viewMode === 'table' ? 'btn-primary' : 'btn-light border-0'}`}
                                onClick={() => handleViewChange('table')}
                                title="Table View"
                            >
                                <Icon
                                    icon="solar:list-bold-duotone"
                                    className="me-1"
                                />
                                Table
                            </button>
                        </div>

                        {canAdd && (
                            <InertiaLink
                                href={route('sales.customers.create')}
                                className="btn btn-sm btn-theme"
                            >
                                <Icon icon={'solar:add-bold-duotone'} /> Create
                                Customer
                            </InertiaLink>
                        )}
                    </div>
                }
            />

            <PageFilters>
                <CustomerFilter />
            </PageFilters>

            <PageContent>
                {viewMode === 'cards' ? (
                    <>
                        <div className="row g-3">
                            {data.map((customer, index) => {
                                const {
                                    id,
                                    name,
                                    name_urdu,
                                    limit,
                                    credit,
                                    phone,
                                    email,
                                    address,
                                    discount,
                                    discount_label,
                                    updated_at,
                                    agent_name,
                                    suspended,
                                    suspended_at,
                                } = customer;

                                const city = address?.city;
                                const region = address?.region;
                                const streetAddress = address?.address;
                                const initial = name
                                    ? name.trim().charAt(0).toUpperCase()
                                    : 'C';

                                return (
                                    <div
                                        key={id}
                                        className="col-12 col-md-6 col-xl-4 col-xxl-3"
                                    >
                                        <div
                                            className={`card h-100 border rounded-3 shadow-sm position-relative overflow-hidden ${
                                                suspended
                                                    ? 'border-danger-subtle bg-danger-subtle bg-opacity-10'
                                                    : 'border-secondary-subtle bg-white'
                                            }`}
                                            style={{
                                                borderTop: suspended
                                                    ? '3px solid var(--bs-danger, #dc3545)'
                                                    : '3px solid var(--bs-primary, #2a5bd7)',
                                                transition:
                                                    'all 0.2s ease-in-out',
                                            }}
                                        >
                                            <div className="card-body p-3 d-flex flex-column">
                                                {/* Header: Avatar, Name, Urdu, Status */}
                                                <div className="d-flex align-items-start justify-content-between gap-2 mb-2">
                                                    <div className="d-flex align-items-center gap-2 overflow-hidden">
                                                        <div
                                                            className="d-flex align-items-center justify-content-center flex-shrink-0 rounded-circle fw-bold"
                                                            style={{
                                                                width: '40px',
                                                                height: '40px',
                                                                backgroundColor:
                                                                    suspended
                                                                        ? '#f8d7da'
                                                                        : '#e8f0fe',
                                                                color: suspended
                                                                    ? '#dc3545'
                                                                    : '#1a73e8',
                                                                fontSize:
                                                                    '17px',
                                                            }}
                                                        >
                                                            {initial}
                                                        </div>
                                                        <div className="overflow-hidden">
                                                            <h6
                                                                className="card-title mb-0 fw-bold text-dark text-truncate"
                                                                title={name}
                                                            >
                                                                {name}
                                                            </h6>
                                                            {name_urdu && (
                                                                <div
                                                                    className="urdu text-muted small text-truncate"
                                                                    title={
                                                                        name_urdu
                                                                    }
                                                                >
                                                                    {name_urdu}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex-shrink-0 text-end">
                                                        {suspended ? (
                                                            <Badge
                                                                pill
                                                                bg="danger"
                                                                title={
                                                                    suspended_at
                                                                        ? `Suspended on ${suspended_at}`
                                                                        : undefined
                                                                }
                                                                className="px-2 py-1"
                                                            >
                                                                Suspended
                                                            </Badge>
                                                        ) : (
                                                            <Badge
                                                                pill
                                                                bg="success"
                                                                className="px-2 py-1"
                                                            >
                                                                Active
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Contact Details Box */}
                                                <div className="p-2 mb-2 rounded bg-light border border-light-subtle d-flex flex-column gap-1 small">
                                                    <div className="d-flex align-items-center justify-content-between">
                                                        <span className="text-muted d-inline-flex align-items-center gap-1">
                                                            <Icon
                                                                icon="solar:phone-bold-duotone"
                                                                className="text-primary"
                                                            />
                                                            Phone:
                                                        </span>
                                                        {phone ? (
                                                            <a
                                                                href={`tel:${phone}`}
                                                                className="fw-semibold text-decoration-none text-dark"
                                                            >
                                                                {phone}
                                                            </a>
                                                        ) : (
                                                            <span className="text-muted">
                                                                -
                                                            </span>
                                                        )}
                                                    </div>

                                                    {email && (
                                                        <div className="d-flex align-items-center justify-content-between">
                                                            <span className="text-muted d-inline-flex align-items-center gap-1">
                                                                <Icon
                                                                    icon="solar:letter-bold-duotone"
                                                                    className="text-secondary"
                                                                />
                                                                Email:
                                                            </span>
                                                            <a
                                                                href={`mailto:${email}`}
                                                                className="text-decoration-none text-muted text-truncate"
                                                                style={{
                                                                    maxWidth:
                                                                        '160px',
                                                                }}
                                                                title={email}
                                                            >
                                                                {email}
                                                            </a>
                                                        </div>
                                                    )}

                                                    <div className="d-flex align-items-start justify-content-between gap-1">
                                                        <span className="text-muted d-inline-flex align-items-center gap-1 flex-shrink-0">
                                                            <Icon
                                                                icon="solar:map-point-bold-duotone"
                                                                className="text-danger"
                                                            />
                                                            Address:
                                                        </span>
                                                        <span
                                                            className="text-end text-truncate"
                                                            title={[
                                                                streetAddress,
                                                                city,
                                                                region,
                                                            ]
                                                                .filter(Boolean)
                                                                .join(', ')}
                                                        >
                                                            {[
                                                                streetAddress,
                                                                city,
                                                                region,
                                                            ]
                                                                .filter(Boolean)
                                                                .join(', ') ||
                                                                '-'}
                                                        </span>
                                                    </div>
                                                </div>

                                                {/* Details Chips */}
                                                <div className="d-flex flex-wrap gap-1 mb-3 align-items-center">
                                                    {city && (
                                                        <span className="badge bg-light text-secondary border d-inline-flex align-items-center gap-1">
                                                            <Icon
                                                                icon="solar:city-bold-duotone"
                                                                className="text-secondary"
                                                            />
                                                            {city}
                                                        </span>
                                                    )}

                                                    {credit ? (
                                                        <span className="badge bg-info-subtle text-info-emphasis border border-info-subtle d-inline-flex align-items-center gap-1">
                                                            <Icon icon="solar:wallet-money-bold-duotone" />
                                                            {limit > 0 ? (
                                                                <>
                                                                    Limit: Rs.{' '}
                                                                    <NumberFormat
                                                                        displayType="text"
                                                                        value={
                                                                            limit
                                                                        }
                                                                        thousandSeparator={
                                                                            true
                                                                        }
                                                                    />
                                                                </>
                                                            ) : (
                                                                'Credit: Unlimited'
                                                            )}
                                                        </span>
                                                    ) : (
                                                        <span className="badge bg-light text-muted border d-inline-flex align-items-center gap-1">
                                                            <Icon icon="solar:card-line-duotone" />
                                                            Cash Only
                                                        </span>
                                                    )}

                                                    {discount > 0 &&
                                                        discount_label && (
                                                            <span className="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-inline-flex align-items-center gap-1">
                                                                <Icon icon="solar:tag-price-bold-duotone" />
                                                                Disc:{' '}
                                                                {discount_label}
                                                            </span>
                                                        )}

                                                    <span className="badge bg-light text-secondary border d-inline-flex align-items-center gap-1">
                                                        <Icon
                                                            icon="solar:user-bold-duotone"
                                                            className="text-primary"
                                                        />
                                                        {agent_name
                                                            ? agent_name
                                                            : 'Direct'}
                                                    </span>
                                                </div>

                                                {/* Footer: Date & Actions */}
                                                <div className="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                                    <div className="small text-muted d-flex align-items-center gap-1">
                                                        <Icon icon="solar:clock-circle-line-duotone" />
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </div>

                                                    <div className="d-flex align-items-center gap-1">
                                                        <InertiaLink
                                                            href={route(
                                                                'accounts.ledgers.show',
                                                                id,
                                                            )}
                                                            className="btn btn-sm btn-light border text-primary d-inline-flex align-items-center gap-1 py-1 px-2"
                                                            title="View Ledger"
                                                        >
                                                            <Icon icon="stash:billing-info-duotone" />
                                                            <span className="small">
                                                                Ledger
                                                            </span>
                                                        </InertiaLink>

                                                        {canUpdate && (
                                                            <InertiaLink
                                                                href={route(
                                                                    'sales.customers.edit',
                                                                    id,
                                                                )}
                                                                className="btn btn-sm btn-light border text-secondary d-inline-flex align-items-center gap-1 py-1 px-2"
                                                                title="Edit Customer"
                                                            >
                                                                <Icon icon="solar:pen-bold-duotone" />
                                                            </InertiaLink>
                                                        )}

                                                        {!suspended ? (
                                                            <ConfirmAction
                                                                action="sales.customers.suspend"
                                                                id={id}
                                                                icon="solar:forbidden-circle-bold-duotone"
                                                                tooltip="Suspend Customer"
                                                            />
                                                        ) : (
                                                            <ConfirmAction
                                                                action="sales.customers.activate"
                                                                id={id}
                                                                icon="solar:check-circle-bold-duotone"
                                                                tooltip="Activate Customer"
                                                            />
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {data.length === 0 && (
                            <NoData label={'No customers found.'} />
                        )}
                        <div className="mt-3">
                            <PaginationFull meta={meta} />
                        </div>
                    </>
                ) : (
                    <Panel>
                        <PanelBody>
                            <div className="table-responsive">
                                <table className="table table-bordered table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th className="w-1 text-center">
                                                Sr
                                            </th>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Agent</th>
                                            <th className="w-1 text-center">
                                                Credit Limit
                                            </th>
                                            <th className="w-1 text-center">
                                                Discount
                                            </th>
                                            <th className="w-1 text-center">
                                                Status
                                            </th>
                                            <th className="w-1 text-nowrap">
                                                Last Modified
                                            </th>
                                            <th className="w-1 text-center">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.map(
                                            (
                                                {
                                                    id,
                                                    name,
                                                    name_urdu,
                                                    limit,
                                                    credit,
                                                    phone,
                                                    email,
                                                    address,
                                                    discount,
                                                    discount_label,
                                                    updated_at,
                                                    agent_name,
                                                    suspended,
                                                    suspended_at,
                                                },
                                                index,
                                            ) => {
                                                const city = address?.city;
                                                const region = address?.region;
                                                const streetAddress =
                                                    address?.address;

                                                return (
                                                    <tr key={id}>
                                                        <td className="w-1 text-center text-muted fw-semibold">
                                                            {meta?.from
                                                                ? meta.from +
                                                                  index
                                                                : index + 1}
                                                        </td>
                                                        <td>
                                                            <div className="d-flex flex-column">
                                                                <div className="d-flex align-items-center gap-2">
                                                                    <span className="fw-bold text-dark">
                                                                        {name}
                                                                    </span>
                                                                    {city && (
                                                                        <span className="badge bg-light text-secondary border">
                                                                            {
                                                                                city
                                                                            }
                                                                        </span>
                                                                    )}
                                                                </div>
                                                                {name_urdu && (
                                                                    <span className="urdu text-muted">
                                                                        {
                                                                            name_urdu
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="text-nowrap">
                                                            <div className="d-flex flex-column gap-1">
                                                                {phone ? (
                                                                    <a
                                                                        href={`tel:${phone}`}
                                                                        className="text-decoration-none text-body d-inline-flex align-items-center gap-1"
                                                                    >
                                                                        <Icon
                                                                            icon="solar:phone-bold-duotone"
                                                                            className="text-primary"
                                                                        />
                                                                        <span>
                                                                            {
                                                                                phone
                                                                            }
                                                                        </span>
                                                                    </a>
                                                                ) : (
                                                                    <span className="text-muted">
                                                                        -
                                                                    </span>
                                                                )}
                                                                {email && (
                                                                    <a
                                                                        href={`mailto:${email}`}
                                                                        className="text-decoration-none text-muted small d-inline-flex align-items-center gap-1"
                                                                    >
                                                                        <Icon
                                                                            icon="solar:letter-bold-duotone"
                                                                            className="text-secondary"
                                                                        />
                                                                        <span>
                                                                            {
                                                                                email
                                                                            }
                                                                        </span>
                                                                    </a>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            {streetAddress ||
                                                            city ||
                                                            region ? (
                                                                <div className="d-flex align-items-start gap-1">
                                                                    <Icon
                                                                        icon="solar:map-point-bold-duotone"
                                                                        className="text-danger-500 mt-1 flex-shrink-0"
                                                                    />
                                                                    <span className="small text-secondary">
                                                                        {[
                                                                            streetAddress,
                                                                            city,
                                                                            region,
                                                                        ]
                                                                            .filter(
                                                                                Boolean,
                                                                            )
                                                                            .join(
                                                                                ', ',
                                                                            )}
                                                                    </span>
                                                                </div>
                                                            ) : (
                                                                <span className="text-muted">
                                                                    -
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="text-nowrap">
                                                            {agent_name ? (
                                                                <span className="d-inline-flex align-items-center gap-1 text-secondary">
                                                                    <Icon
                                                                        icon="solar:user-bold-duotone"
                                                                        className="text-primary"
                                                                    />
                                                                    {agent_name}
                                                                </span>
                                                            ) : (
                                                                <span className="text-muted">
                                                                    Direct
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="w-1 text-center text-nowrap">
                                                            {credit ? (
                                                                <Badge
                                                                    pill
                                                                    bg="info"
                                                                    className="text-dark fw-normal"
                                                                >
                                                                    {limit >
                                                                    0 ? (
                                                                        <>
                                                                            Rs.{' '}
                                                                            <NumberFormat
                                                                                displayType={
                                                                                    'text'
                                                                                }
                                                                                value={
                                                                                    limit
                                                                                }
                                                                                thousandSeparator={
                                                                                    true
                                                                                }
                                                                            />
                                                                        </>
                                                                    ) : (
                                                                        'Unlimited'
                                                                    )}
                                                                </Badge>
                                                            ) : (
                                                                <span className="badge bg-light text-muted border">
                                                                    Cash Only
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="w-1 text-center text-nowrap">
                                                            {discount > 0 &&
                                                            discount_label ? (
                                                                <span className="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-inline-flex align-items-center gap-1">
                                                                    <Icon icon="solar:tag-price-bold-duotone" />
                                                                    {
                                                                        discount_label
                                                                    }
                                                                </span>
                                                            ) : (
                                                                <span className="text-muted">
                                                                    -
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="w-1 text-center text-nowrap">
                                                            {suspended ? (
                                                                <Badge
                                                                    pill
                                                                    bg="danger"
                                                                    title={
                                                                        suspended_at
                                                                            ? `Suspended on ${suspended_at}`
                                                                            : undefined
                                                                    }
                                                                >
                                                                    Suspended
                                                                </Badge>
                                                            ) : (
                                                                <Badge
                                                                    pill
                                                                    bg="success"
                                                                >
                                                                    Active
                                                                </Badge>
                                                            )}
                                                        </td>
                                                        <td className="w-1 text-nowrap small text-muted">
                                                            <Moment
                                                                format={
                                                                    settings.DATE_FORMAT
                                                                }
                                                                date={
                                                                    updated_at
                                                                }
                                                            />
                                                        </td>
                                                        <td className="actions w-1 text-center">
                                                            <InertiaView
                                                                href={route(
                                                                    'accounts.ledgers.show',
                                                                    id,
                                                                )}
                                                                link_title="View Ledger"
                                                                icon="stash:billing-info-duotone"
                                                            />
                                                            {canUpdate && (
                                                                <InertiaEdit
                                                                    href={route(
                                                                        'sales.customers.edit',
                                                                        id,
                                                                    )}
                                                                />
                                                            )}
                                                            {!suspended && (
                                                                <ConfirmAction
                                                                    action="sales.customers.suspend"
                                                                    id={id}
                                                                    icon="solar:forbidden-circle-bold-duotone"
                                                                    tooltip="Suspend Customer"
                                                                />
                                                            )}
                                                            {suspended && (
                                                                <ConfirmAction
                                                                    action="sales.customers.activate"
                                                                    id={id}
                                                                    icon="solar:check-circle-bold-duotone"
                                                                    tooltip="Activate Customer"
                                                                />
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            },
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            {data.length === 0 && (
                                <NoData label={'No customers found.'} />
                            )}
                            <PaginationFull meta={meta} />
                        </PanelBody>
                    </Panel>
                )}
            </PageContent>
        </>
    );
};

export default Customers;

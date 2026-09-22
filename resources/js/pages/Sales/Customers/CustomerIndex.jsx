import React from 'react';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import SearchFilter from '@/components/SearchFilter';
import { ConfirmAction, InertiaEdit } from '@/components/Actions';
import { Badge } from 'react-bootstrap';
import PaginationFull from '@/components/PaginationFull';
import NoData from '@/components/NoData';
import { NumberFormat } from '@/util/NumberFormat';
import { PageContent, PageHeader } from '@/components/page.jsx';

const Customers = () => {
    const { customers } = usePage().props;
    const { data, meta } = customers;

    return (
        <>
            <Head title="Customers List" />
            <PageHeader
                title="Customers List"
                buttons={
                    <InertiaLink
                        href={route('sales.customers.create')}
                        className="btn btn-sm  btn-theme"
                    >
                        <Icon icon={'solar:add-bold-duotone'} /> Create Customer
                    </InertiaLink>
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <SearchFilter />
                        <div className={'table-responsive'}>
                            <table className={'table table-bordered'}>
                                <thead>
                                    <tr>
                                        <th className={'w-1'}>Sr</th>
                                        <th>Customer Name</th>
                                        <th>Agent</th>
                                        <th className={'w-1'}>
                                            Customer Phone
                                        </th>
                                        <th className={'w-1'}>Address</th>
                                        <th className={'w-1'}>Discount</th>
                                        <th className={'w-1'}>Last Modified</th>
                                        <th className={'w-1'}>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                name,
                                                name_urdu,
                                                city,
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
                                            return (
                                                <tr key={id}>
                                                    <td className="w-1">
                                                        {index + 1}
                                                    </td>
                                                    <td>
                                                        {name} {city} &nbsp;
                                                        {credit && (
                                                            <Badge
                                                                pill
                                                                bg="secondary"
                                                            >
                                                                {limit > 0 ? (
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
                                                                ) : (
                                                                    'unlimited'
                                                                )}
                                                            </Badge>
                                                        )}
                                                        {suspended && (
                                                            <Badge
                                                                pill
                                                                bg="danger"
                                                            >
                                                                Suspended
                                                            </Badge>
                                                        )}
                                                        <span className="urdu float-end">
                                                            {name_urdu}
                                                        </span>
                                                    </td>
                                                    <td className="w-1">
                                                        {agent_name}
                                                    </td>
                                                    <td className="w-1">
                                                        {phone}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {address && (
                                                            <>
                                                                {
                                                                    address.address
                                                                }
                                                                , {address.city}
                                                                ,{' '}
                                                                {address.region}
                                                            </>
                                                        )}
                                                    </td>
                                                    <td className="w-1">
                                                        {discount
                                                            ? discount_label
                                                            : ''}
                                                    </td>
                                                    <td className="w-1">
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'actions w-1'
                                                        }
                                                    >
                                                        <InertiaEdit
                                                            href={route(
                                                                'sales.customers.edit',
                                                                id,
                                                            )}
                                                        />
                                                        {!suspended && (
                                                            <ConfirmAction
                                                                action="sales.customers.suspend"
                                                                id={id}
                                                                icon={
                                                                    'solar:forbidden-circle-bold-duotone'
                                                                }
                                                                tooltip="Suspend Customer"
                                                            />
                                                        )}
                                                        {suspended && (
                                                            <ConfirmAction
                                                                action="sales.customers.activate"
                                                                id={id}
                                                                icon={
                                                                    'solar:check-circle-bold-duotone'
                                                                }
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
                            <NoData label={'No customer data found!'} />
                        )}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Customers;

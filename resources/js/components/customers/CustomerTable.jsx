import React from 'react';
import cx from 'classnames';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import CustomerActions from './CustomerActions';
import { CreditValue, CustomerStatus, DiscountValue } from './CustomerBadges';

export default function CustomerTable({ customers, canUpdate }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-cust-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Agent</th>
                        <th className="num">Credit limit</th>
                        <th>Discount</th>
                        <th>Status</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {customers.map((customer) => {
                        const {
                            id, name, name_urdu, limit, credit, phone, email, address,
                            discount, discount_label, updated_at, agent_name, suspended, suspended_at,
                        } = customer;

                        return (
                            <tr key={id} className={cx({ 'is-suspended': suspended })}>
                                <td>
                                    <div className="hf-cust-cell">
                                        <div className="hf-cust-name">{name}</div>
                                        {name_urdu && (
                                            <div className="hf-cust-sub">
                                                <span className="urdu" dir="rtl" lang="ur">{name_urdu}</span>
                                            </div>
                                        )}
                                    </div>
                                </td>
                                <td className="text-nowrap">
                                    {phone ? <a href={`tel:${phone}`} className="hf-link">{phone}</a> : <span className="hf-muted-value">—</span>}
                                    {email && <div><a href={`mailto:${email}`} className="hf-cust-sub hf-link">{email}</a></div>}
                                </td>
                                <td className="hf-cust-address">
                                    {address?.city && <div className="hf-cust-city">{address.city}</div>}
                                    <span className="hf-clamp-2">
                                        {[address?.address, address?.region].filter(Boolean).join(', ') || (address?.city ? '' : '—')}
                                    </span>
                                </td>
                                <td className="text-nowrap">{agent_name || <span className="hf-muted-value">Direct</span>}</td>
                                <td className="num text-nowrap"><CreditValue credit={credit} limit={limit} /></td>
                                <td className="text-nowrap"><DiscountValue discount={discount} label={discount_label} /></td>
                                <td className="text-nowrap">
                                    <CustomerStatus suspended={suspended} suspendedAt={suspended_at} />
                                    <div className="hf-cust-sub" title="Last updated">
                                        <Moment format={settings.DATE_FORMAT} date={updated_at} />
                                    </div>
                                </td>
                                <td className="text-end">
                                    <CustomerActions id={id} suspended={suspended} canUpdate={canUpdate} />
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

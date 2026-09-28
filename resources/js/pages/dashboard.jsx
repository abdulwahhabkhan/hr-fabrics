import React from 'react';
import { Icon } from '@iconify/react';
import cx from 'classnames';
import { format, parseISO } from 'date-fns';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Moment } from '@/components/Moment';
import NoData from '@/components/NoData.jsx';
import { OrderStatus } from '@/components/sales/OrderTable.jsx';
import orders from '@/routes/sales/orders';
import pos from '@/routes/purchases/pos';
import reports from '@/routes/reports';

const greeting = () => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 17 ? 'Good afternoon' : 'Good evening';
};

const Amount = ({ value }) => (
    <NumberFormat displayType="text" value={value} thousandSeparator={true} />
);

function StatTile({ icon, tone, label, value, footer }) {
    return (
        <div className={cx('hf-stat', `tone-${tone}`)}>
            <div className="hf-stat-head">
                <span className="hf-stat-label">{label}</span>
                <span className="hf-stat-icon">
                    <Icon icon={icon} />
                </span>
            </div>
            <div className="hf-stat-value">{value}</div>
            {footer && <div className="hf-stat-footer">{footer}</div>}
        </div>
    );
}

function ChangeBadge({ current, previous }) {
    if (!previous) {
        return <span className="hf-muted-value">No sales yesterday</span>;
    }

    const change = ((current - previous) / previous) * 100;
    const up = change >= 0;

    return (
        <>
            <span className={cx('hf-stat-change', up ? 'is-up' : 'is-down')}>
                <Icon
                    icon={
                        up
                            ? 'solar:arrow-right-up-linear'
                            : 'solar:arrow-right-down-linear'
                    }
                />
                {Math.abs(change).toFixed(1)}%
            </span>
            vs yesterday
        </>
    );
}

function SalesTrend({ trend }) {
    const max = Math.max(...trend.map(({ total }) => total), 1);
    const total = trend.reduce((sum, day) => sum + day.total, 0);

    return (
        <Panel className="hf-trend-panel">
            <PanelHeader
                heading="Sales — last 14 days"
                buttons={
                    <span className="hf-trend-total">
                        <Amount value={total} />
                    </span>
                }
            />
            <PanelBody>
                <div
                    className="hf-trend"
                    role="img"
                    aria-label="Daily confirmed sales for the last 14 days"
                >
                    {trend.map(({ date, total: dayTotal }, index) => {
                        const isToday = index === trend.length - 1;

                        return (
                            <div
                                key={date}
                                className={cx('hf-trend-col', {
                                    'is-today': isToday,
                                })}
                                title={format(parseISO(date), 'EEE, dd MMM')}
                            >
                                <div className="hf-trend-bar-wrap">
                                    <div
                                        className="hf-trend-bar"
                                        style={{
                                            height: `${Math.max((dayTotal / max) * 100, dayTotal ? 3 : 0)}%`,
                                        }}
                                    >
                                        <span className="hf-trend-tip">
                                            <Amount value={dayTotal} />
                                        </span>
                                    </div>
                                </div>
                                <span className="hf-trend-day">
                                    {format(parseISO(date), 'dd')}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </PanelBody>
        </Panel>
    );
}

function RecentOrders({ rows, canView }) {
    return (
        <Panel className="hf-activity-panel">
            <PanelHeader
                heading="Recent invoices"
                buttons={
                    canView && (
                        <InertiaLink
                            href={orders.index()}
                            className="btn btn-xs btn-white"
                        >
                            View all
                        </InertiaLink>
                    )
                }
            />
            <PanelBody>
                {rows.length ? (
                    <ul className="hf-activity">
                        {rows.map(
                            ({
                                id,
                                invoice_no,
                                customer_name,
                                city,
                                net_total,
                                status,
                                transaction_date,
                            }) => (
                                <li key={id}>
                                    <div className="hf-activity-main">
                                        {canView ? (
                                            <InertiaLink
                                                href={orders.show(id)}
                                                className="hf-link hf-mono"
                                            >
                                                {invoice_no || `#${id}`}
                                            </InertiaLink>
                                        ) : (
                                            <span className="hf-mono">
                                                {invoice_no || `#${id}`}
                                            </span>
                                        )}
                                        <span className="hf-activity-party">
                                            {customer_name}
                                            {city && (
                                                <span className="hf-muted-value">
                                                    , {city}
                                                </span>
                                            )}
                                        </span>
                                    </div>
                                    <div className="hf-activity-side">
                                        <span className="hf-mono fw-semibold">
                                            <Amount value={net_total} />
                                        </span>
                                        <span className="hf-muted-value small">
                                            <Moment date={transaction_date} />
                                        </span>
                                    </div>
                                    <OrderStatus status={status} />
                                </li>
                            ),
                        )}
                    </ul>
                ) : (
                    <NoData label="No invoices yet." />
                )}
            </PanelBody>
        </Panel>
    );
}

const Dashboard = () => {
    const { sales, purchases, meters, trend, recent_orders, auth } =
        usePage().props;
    const permissions = auth?.permissions ?? [];
    const can = (name) => permissions.includes(name);
    const firstName = auth?.user?.name?.split(' ')[0];

    return (
        <>
            <Head title="Dashboard" />
            <PageHeader
                title={`${greeting()}${firstName ? `, ${firstName}` : ''}`}
                description={format(
                    parseISO(trend[trend.length - 1].date),
                    'EEEE, d MMMM yyyy',
                )}
                buttons={
                    <>
                        {can('reports.summary') && (
                            <InertiaLink
                                href={reports.summary()}
                                className="btn btn-sm btn-white"
                            >
                                <Icon icon="solar:chart-2-bold-duotone" /> Daily
                                Summary
                            </InertiaLink>
                        )}
                        {can('purchases.pos.create') && (
                            <InertiaLink
                                href={pos.create()}
                                className="btn btn-sm btn-white"
                            >
                                <Icon icon="solar:delivery-bold-duotone" /> New
                                Purchase
                            </InertiaLink>
                        )}
                        {can('sales.orders.create') && (
                            <InertiaLink
                                href={orders.create()}
                                className="btn btn-sm btn-theme"
                            >
                                <Icon icon="solar:add-bold-duotone" /> New
                                Invoice
                            </InertiaLink>
                        )}
                    </>
                }
            />
            <PageContent>
                <div className="hf-stat-grid">
                    <StatTile
                        tone="navy"
                        icon="solar:banknote-2-bold-duotone"
                        label="Today's sales"
                        value={<Amount value={sales.net} />}
                        footer={
                            <ChangeBadge
                                current={sales.net}
                                previous={sales.yesterday}
                            />
                        }
                    />
                    <StatTile
                        tone="gold"
                        icon="solar:ruler-cross-pen-bold-duotone"
                        label="Meters sold"
                        value={<Amount value={meters.sales.net} />}
                        footer="Confirmed invoices today"
                    />
                    <StatTile
                        tone="teal"
                        icon="solar:bill-list-bold-duotone"
                        label="Invoices"
                        value={<Amount value={sales.invoices} />}
                        footer={
                            sales.invoices ? (
                                <>
                                    Avg{' '}
                                    <Amount
                                        value={Math.round(
                                            sales.net / sales.invoices,
                                        )}
                                    />{' '}
                                    per invoice
                                </>
                            ) : (
                                'None confirmed yet'
                            )
                        }
                    />
                    <StatTile
                        tone="slate"
                        icon="solar:box-bold-duotone"
                        label="Today's purchases"
                        value={<Amount value={purchases.net} />}
                        footer={
                            <>
                                <Amount value={meters.purchase} /> meters
                                received
                            </>
                        }
                    />
                </div>

                <div className="hf-dash-grid">
                    <SalesTrend trend={trend} />
                    <RecentOrders
                        rows={recent_orders}
                        canView={can('sales.orders.index')}
                    />
                </div>
            </PageContent>
        </>
    );
};

export default Dashboard;

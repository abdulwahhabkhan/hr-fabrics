import React, { useState } from 'react';
import { Icon } from '@iconify/react';
import { Button, FormControl, InputGroup } from 'react-bootstrap';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, router, usePage } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { NumberFormat } from '@/util/NumberFormat';
import { confirmSwal } from '@/util/swal';
import NoData from '@/components/NoData.jsx';
import BackButton from '@/components/button/back';
import LoadingButton from '@/components/LoadingButton';
import SummaryStat from '@/components/SummaryStat.jsx';
import ValidationErrors from '@/components/ValidationErrors';
import PurchaseStatus from '@/components/purchases/PurchaseStatus';
import orders from '@/routes/sales/orders';
import master from '@/routes/settings/master';
import masterOrders from '@/routes/settings/master/orders';

const allocationTones = { 0: 'red', 1: 'gold', 2: 'green' };

function AllocationStatus({ allocation, label }) {
    return <span className={`hf-pill tone-${allocationTones[allocation]}`}>{label}</span>;
}

function Meters({ value }) {
    return (
        <>
            <NumberFormat displayType="text" value={value} thousandSeparator />
            <span className="hf-price-unit">m</span>
        </>
    );
}

function ItemsPanel({ items }) {
    return (
        <Panel className="hf-table-panel">
            <PanelHeader heading={`Items (${items.length})`} />
            <PanelBody>
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0 hf-list-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th className="w-1">Unit</th>
                                <th className="w-1">Location</th>
                                <th className="w-1 text-end">Size</th>
                                <th className="w-1 text-end">Qty</th>
                                <th className="w-1 text-end">Meters</th>
                                <th className="w-1 text-end">Allocated</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item) => (
                                <tr key={item.id}>
                                    <td className="text-nowrap hf-cell-title">{item.product_name}</td>
                                    <td className="text-nowrap">{item.unit}</td>
                                    <td className="text-nowrap">{item.location || '—'}</td>
                                    <td className="num text-end hf-mono">{item.size}</td>
                                    <td className="num text-end hf-mono">{item.qty}</td>
                                    <td className="num text-end text-nowrap hf-mono">
                                        <NumberFormat displayType="text" value={item.meters} thousandSeparator />
                                    </td>
                                    <td
                                        className={`num text-end text-nowrap hf-mono ${
                                            item.allocated_meters < item.meters ? 'text-danger' : ''
                                        }`}
                                    >
                                        <NumberFormat displayType="text" value={item.allocated_meters} thousandSeparator />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {items.length === 0 && <NoData label="This order has no items." />}
                </div>
            </PanelBody>
        </Panel>
    );
}

function OrderDetail({ order }) {
    const [processing, setProcessing] = useState(false);

    const allocate = async () => {
        const { isConfirmed } = await confirmSwal({
            title: `Allocate ${order.invoice_no}?`,
            text: `${order.required_meters}m will be booked against stock first-in first-out. Lines already booked to this order are booked again. Nothing changes if any item is short of stock.`,
            confirmButtonText: 'Allocate',
        });
        if (isConfirmed) {
            router.post(masterOrders.allocate(order.id), {}, {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            });
        }
    };

    return (
        <>
            <Panel>
                <PanelHeader
                    heading={
                        <>
                            <InertiaLink href={orders.show(order.id)} className="hf-link hf-mono">
                                {order.invoice_no}
                            </InertiaLink>{' '}
                            · {order.customer_name}
                        </>
                    }
                    buttons={
                        order.can_allocate ? (
                            <LoadingButton size="xs" variant="theme" processing={processing} onClick={allocate}>
                                Allocate inventory
                            </LoadingButton>
                        ) : (
                            <span className="hf-pill tone-slate">
                                <Icon icon="solar:lock-bold-duotone" />
                                {order.allocate_blocked_reason}
                            </span>
                        )
                    }
                />
                <PanelBody>
                    <div className="hf-order-summary">
                        <SummaryStat icon="solar:calendar-date-bold-duotone" label="Date">
                            {order.transaction_date ? (
                                <Moment format={settings.DATE_FORMAT} date={order.transaction_date} />
                            ) : (
                                '—'
                            )}
                        </SummaryStat>
                        <SummaryStat icon="solar:ruler-bold-duotone" label="Meters" tone="brand">
                            <Meters value={order.required_meters} />
                        </SummaryStat>
                        <SummaryStat icon="solar:box-bold-duotone" label="Allocated">
                            <Meters value={order.allocated_meters} />
                        </SummaryStat>
                        <SummaryStat icon="solar:wallet-money-bold-duotone" label="Net Total">
                            <NumberFormat displayType="text" value={order.net_total} thousandSeparator />
                        </SummaryStat>
                        <SummaryStat icon="solar:bill-list-bold-duotone" label="Status">
                            <span className="d-inline-flex gap-1">
                                <PurchaseStatus status={order.status === 1 ? 'Close' : 'Open'} />
                                <AllocationStatus allocation={order.allocation} label={order.allocation_label} />
                            </span>
                        </SummaryStat>
                    </div>
                </PanelBody>
            </Panel>

            <ItemsPanel items={order.items} />
        </>
    );
}

const OrderAllocateIndex = () => {
    const { ref_no, order, errors } = usePage().props;
    const [refNo, setRefNo] = useState(ref_no || '');

    const search = (event) => {
        event.preventDefault();
        router.get(masterOrders.index({ query: refNo.trim() ? { ref_no: refNo.trim() } : {} }), {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Allocate Order" />
            <PageHeader
                title="Allocate Order"
                description="Find a confirmed order by its invoice number and book stock for it when it is unallocated or partially allocated."
                buttons={<BackButton href={master.index()} label="Master Actions" />}
            />

            <PageContent>
                <ValidationErrors errors={errors} />

                <Panel>
                    <PanelBody>
                        <form onSubmit={search}>
                            <InputGroup>
                                <FormControl
                                    name="ref_no"
                                    value={refNo}
                                    onChange={(event) => setRefNo(event.target.value)}
                                    placeholder="Invoice No, e.g. 261012345"
                                    autoFocus
                                />
                                <Button type="submit" variant="outline-primary" disabled={!refNo.trim()}>
                                    <Icon icon="andrsrxn:database-search" />
                                    &nbsp;Search
                                </Button>
                            </InputGroup>
                        </form>
                    </PanelBody>
                </Panel>

                {order && <OrderDetail order={order} />}
                {!order && ref_no && <NoData label={`No sale order found for invoice ${ref_no}.`} />}
            </PageContent>
        </>
    );
};

export default OrderAllocateIndex;

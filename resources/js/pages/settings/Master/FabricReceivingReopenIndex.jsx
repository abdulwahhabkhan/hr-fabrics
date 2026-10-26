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
import SummaryStat from '@/components/SummaryStat.jsx';
import PurchaseStatus from '@/components/purchases/PurchaseStatus';
import { InvoicedStatus } from '@/components/purchases/FabricReceivingTable';
import fabricReceivings from '@/routes/purchases/fabric-receivings';
import pos from '@/routes/purchases/pos';
import orders from '@/routes/sales/orders';
import master from '@/routes/settings/master';
import masterReceivings from '@/routes/settings/master/fabric-receivings';

function DateCell({ date }) {
    return (
        <td className="text-nowrap hf-muted-value">
            <Moment format={settings.DATE_FORMAT} date={date} />
        </td>
    );
}

function PurchasesPanel({ purchases }) {
    return (
        <Panel className="hf-table-panel">
            <PanelHeader heading={`Purchase vouchers (${purchases.length})`} />
            <PanelBody>
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0 hf-list-table">
                        <thead>
                            <tr>
                                <th className="w-1">Bill No</th>
                                <th>Ref No</th>
                                <th className="w-1">Date</th>
                                <th className="w-1 text-end">Total</th>
                                <th className="w-1">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {purchases.map((purchase) => (
                                <tr key={purchase.id}>
                                    <td className="text-nowrap hf-cell-title hf-mono">
                                        <InertiaLink href={pos.show(purchase.id)} className="hf-link">
                                            {purchase.bill_no || `#${purchase.id}`}
                                        </InertiaLink>
                                    </td>
                                    <td className="text-nowrap hf-mono">{purchase.invoice_no}</td>
                                    <DateCell date={purchase.transaction_date} />
                                    <td className="num text-end text-nowrap hf-mono">
                                        <NumberFormat displayType="text" value={purchase.total} thousandSeparator />
                                    </td>
                                    <td className="text-nowrap">
                                        <PurchaseStatus status={purchase.status} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {purchases.length === 0 && <NoData label="This receiving is not invoiced on any voucher." />}
                </div>
            </PanelBody>
        </Panel>
    );
}

function SaleOrdersPanel({ saleOrders }) {
    return (
        <Panel className="hf-table-panel">
            <PanelHeader heading={`Sale orders (${saleOrders.length})`} />
            <PanelBody>
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0 hf-list-table">
                        <thead>
                            <tr>
                                <th className="w-1">Invoice No</th>
                                <th>Customer</th>
                                <th className="w-1">Date</th>
                                <th className="w-1 text-end">Net Total</th>
                                <th className="w-1">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {saleOrders.map((order) => (
                                <tr key={order.id}>
                                    <td className="text-nowrap hf-cell-title hf-mono">
                                        <InertiaLink href={orders.show(order.id)} className="hf-link">
                                            {order.invoice_no}
                                        </InertiaLink>
                                    </td>
                                    <td className="text-nowrap">{order.customer_name}</td>
                                    <DateCell date={order.transaction_date} />
                                    <td className="num text-end text-nowrap hf-mono">
                                        <NumberFormat displayType="text" value={order.net_total} thousandSeparator />
                                    </td>
                                    <td className="text-nowrap">
                                        <PurchaseStatus status={order.status === 1 ? 'Close' : 'Open'} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {saleOrders.length === 0 && <NoData label="No stock from this receiving is sold." />}
                </div>
            </PanelBody>
        </Panel>
    );
}

function ReceivingDetail({ receiving }) {
    const [processing, setProcessing] = useState(false);

    const reopen = async () => {
        const { isConfirmed } = await confirmSwal({
            title: `Reopen ${receiving.invoice_no}?`,
            text: `${receiving.purchases.length} voucher(s) will be reopened. ${receiving.sale_orders.length} sale order(s) will be reallocated when the receiving is confirmed again. Transaction dates are kept.`,
            confirmButtonText: 'Reopen',
            confirmButtonStyle: 'danger',
        });
        if (isConfirmed) {
            router.post(masterReceivings.reopen(receiving.id), {}, {
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
                            <InertiaLink href={fabricReceivings.show(receiving.id)} className="hf-link hf-mono">
                                {receiving.invoice_no}
                            </InertiaLink>{' '}
                            · {receiving.supplier_name}
                        </>
                    }
                    buttons={
                        receiving.can_reopen ? (
                            <Button size="xs" variant="danger" disabled={processing} onClick={reopen}>
                                <Icon icon="solar:lock-keyhole-unlocked-bold-duotone" /> Reopen receiving
                            </Button>
                        ) : (
                            <span className="hf-pill tone-slate">
                                <Icon icon="solar:lock-bold-duotone" />
                                {receiving.reopen_blocked_reason}
                            </span>
                        )
                    }
                />
                <PanelBody>
                    <div className="hf-order-summary">
                        <SummaryStat icon="solar:calendar-date-bold-duotone" label="Date">
                            <Moment format={settings.DATE_FORMAT} date={receiving.transaction_date} />
                        </SummaryStat>
                        <SummaryStat icon="solar:delivery-bold-duotone" label="Bilti / Lot">
                            {receiving.bilti_no || '—'} / {receiving.lot_no || '—'}
                        </SummaryStat>
                        <SummaryStat icon="solar:layers-bold-duotone" label="Qty">
                            <NumberFormat displayType="text" value={receiving.total_qty} thousandSeparator />
                        </SummaryStat>
                        <SummaryStat icon="solar:ruler-bold-duotone" label="Meters" tone="brand">
                            <NumberFormat displayType="text" value={receiving.total_meters} thousandSeparator />
                            <span className="hf-price-unit">m</span>
                        </SummaryStat>
                        <SummaryStat icon="solar:bill-list-bold-duotone" label="Status">
                            <span className="d-inline-flex gap-1">
                                <PurchaseStatus status={receiving.status} />
                                <InvoicedStatus invoiced={receiving.invoiced} />
                            </span>
                        </SummaryStat>
                    </div>
                </PanelBody>
            </Panel>

            <PurchasesPanel purchases={receiving.purchases} />
            <SaleOrdersPanel saleOrders={receiving.sale_orders} />
        </>
    );
}

const FabricReceivingReopenIndex = () => {
    const { ref_no, receiving } = usePage().props;
    const [refNo, setRefNo] = useState(ref_no || '');

    const search = (event) => {
        event.preventDefault();
        router.get(masterReceivings.index({ query: refNo.trim() ? { ref_no: refNo.trim() } : {} }), {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Reopen Receivings" />
            <PageHeader
                title="Reopen Receivings"
                description="Find a receiving by its reference number to see its vouchers and sales, then reopen it to correct stock and prices."
                buttons={<BackButton href={master.index()} label="Master Actions" />}
            />

            <PageContent>
                <Panel>
                    <PanelBody>
                        <form onSubmit={search}>
                            <InputGroup>
                                <FormControl
                                    name="ref_no"
                                    value={refNo}
                                    onChange={(event) => setRefNo(event.target.value)}
                                    placeholder="Reference No, e.g. ASN-2610001"
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

                {receiving && <ReceivingDetail receiving={receiving} />}
                {!receiving && ref_no && <NoData label={`No fabric receiving found for reference ${ref_no}.`} />}
            </PageContent>
        </>
    );
};

export default FabricReceivingReopenIndex;

import React, { useMemo } from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { ProductSearchFilter } from '@/pages/Stock/Inventory/ProductSearchFilter';
import { Date } from '@/components/CustomDate';
import NoData from '@/components/NoData.jsx';

/**
 * Stock direction and pill tone per movement type returned by
 * InventoryService::getProductHistory(). Meters are stored unsigned.
 */
const MOVEMENT_TYPES = {
    Purchase: { totalKey: 'purchased', direction: 1, tone: 'green', icon: 'solar:download-minimalistic-bold-duotone' },
    'Sale Return': { totalKey: 'saleReturns', direction: 1, tone: 'gold', icon: 'solar:undo-left-round-bold-duotone' },
    Sale: { totalKey: 'sold', direction: -1, tone: 'navy', icon: 'solar:upload-minimalistic-bold-duotone' },
    'PO Return': { totalKey: 'purchaseReturns', direction: -1, tone: 'red', icon: 'solar:undo-right-round-bold-duotone' },
};

const Meters = ({ value, className }) => (
    <NumberFormat
        displayType="text"
        value={value}
        decimalScale={2}
        thousandSeparator={true}
        className={className}
    />
);

const StatTile = ({ tone, icon, label, value, footer }) => (
    <div className={`hf-stat tone-${tone}`}>
        <div className="hf-stat-head">
            <span className="hf-stat-label">{label}</span>
            <span className="hf-stat-icon">
                <Icon icon={icon} />
            </span>
        </div>
        <div className="hf-stat-value">
            <Meters value={value} /> <small className="fs-13px fw-semibold text-muted">m</small>
        </div>
        {footer && <div className="hf-stat-footer">{footer}</div>}
    </div>
);

const ProductHistory = () => {
    const { items, filters } = usePage().props;

    const { rows, totals } = useMemo(() => {
        const totals = { purchased: 0, sold: 0, purchaseReturns: 0, saleReturns: 0, in: 0, out: 0 };
        let balance = 0;

        const rows = items.map((item) => {
            const movement = MOVEMENT_TYPES[item.type] ?? { direction: 1, tone: 'slate', icon: 'solar:box-bold-duotone' };
            const meters = Number(item.meters) || 0;

            balance += movement.direction * meters;
            totals[movement.direction > 0 ? 'in' : 'out'] += meters;

            if (movement.totalKey) {
                totals[movement.totalKey] += meters;
            }

            return { ...item, movement, balance };
        });

        return { rows, totals: { ...totals, balance } };
    }, [items]);

    const product = items[0]?.product;
    const productName = product ? `${product.name} ${product.finish ?? ''}`.trim() : null;

    return (
        <>
            <Head title="Product History" />
            <PageHeader
                title="Product History"
                description={productName ? `${productName} · ${items.length} movements` : 'Stock movements of a product'}
            />

            <PageFilters>
                <ProductSearchFilter filters={filters} />
            </PageFilters>

            <PageContent>
                {rows.length > 0 && (
                    <div className="hf-stat-grid">
                        <StatTile
                            tone="teal"
                            icon="solar:download-minimalistic-bold-duotone"
                            label="Purchased"
                            value={totals.purchased}
                            footer={<>Total in <Meters value={totals.in} /> m incl. customer returns</>}
                        />
                        <StatTile
                            tone="navy"
                            icon="solar:upload-minimalistic-bold-duotone"
                            label="Sold"
                            value={totals.sold}
                            footer={<>Total out <Meters value={totals.out} /> m incl. supplier returns</>}
                        />
                        <StatTile
                            tone="gold"
                            icon="solar:undo-left-round-bold-duotone"
                            label="Returns"
                            value={totals.purchaseReturns + totals.saleReturns}
                            footer={<>Supplier <Meters value={totals.purchaseReturns} /> m · Customer <Meters value={totals.saleReturns} /> m</>}
                        />
                        <StatTile
                            tone="slate"
                            icon="solar:box-bold-duotone"
                            label="Balance"
                            value={totals.balance}
                            footer="Meters in hand after last movement"
                        />
                    </div>
                )}

                <Panel className="hf-table-panel">
                    <PanelHeader heading={productName ? `Movements (${rows.length})` : 'Movements'} />
                    <PanelBody>
                        <div className="table-responsive hf-table-scroll">
                            <table className="table table-hover align-middle mb-0 hf-list-table">
                                <thead>
                                    <tr>
                                        <th className="w-1">#</th>
                                        <th className="w-1">Date</th>
                                        <th className="w-1">Type</th>
                                        <th className="w-1">Ref No</th>
                                        <th>Account</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1 text-end">Size</th>
                                        <th className="w-1 text-end">Qty</th>
                                        <th className="w-1 text-end">In (m)</th>
                                        <th className="w-1 text-end">Out (m)</th>
                                        <th className="w-1 text-end">Balance (m)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row, index) => (
                                        <tr key={`${row.type}-${row.invoice_no}-${index}`}>
                                            <td className="text-muted hf-mono">{index + 1}</td>
                                            <td className="text-nowrap">
                                                <Date date={row.transaction_date} />
                                            </td>
                                            <td>
                                                <span className={`hf-pill tone-${row.movement.tone}`}>
                                                    <Icon icon={row.movement.icon} />
                                                    {row.type}
                                                </span>
                                            </td>
                                            <td className="hf-cell-title text-nowrap">{row.invoice_no}</td>
                                            <td>{row.account?.name}</td>
                                            <td>{row.unit}</td>
                                            <td className="text-end hf-mono">
                                                <NumberFormat displayType="text" value={row.size} thousandSeparator={true} />
                                            </td>
                                            <td className="text-end hf-mono">
                                                <NumberFormat displayType="text" value={row.qty} thousandSeparator={true} />
                                            </td>
                                            <td className="text-end hf-mono">
                                                {row.movement.direction > 0 && <Meters value={row.meters} className="hf-num-in fw-semibold" />}
                                            </td>
                                            <td className="text-end hf-mono">
                                                {row.movement.direction < 0 && <Meters value={row.meters} className="hf-num-out fw-semibold" />}
                                            </td>
                                            <td className="text-end hf-mono fw-semibold">
                                                <Meters value={row.balance} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                {rows.length > 0 && (
                                    <tfoot>
                                        <tr>
                                            <td colSpan="8" className="text-end">Total</td>
                                            <td className="text-end hf-mono">
                                                <Meters value={totals.in} className="hf-num-in" />
                                            </td>
                                            <td className="text-end hf-mono">
                                                <Meters value={totals.out} className="hf-num-out" />
                                            </td>
                                            <td className="text-end hf-mono">
                                                <Meters value={totals.balance} />
                                            </td>
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>
                        {rows.length === 0 && (
                            <NoData label={filters?.product_id ? 'No stock movements found for this product.' : 'Select a product to view its stock history.'} />
                        )}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ProductHistory;

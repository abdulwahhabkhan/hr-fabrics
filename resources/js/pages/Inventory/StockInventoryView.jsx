import React from 'react';
import { Col, Row } from 'react-bootstrap';
import { Head, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { NumberFormat } from '@/util/NumberFormat.jsx';
import { getPOUnit, UNIT_THAAN } from '@/util/util.jsx';
import Moment from '@/components/Moment.jsx';
import NoData from '@/components/NoData.jsx';
import BackButton from '@/components/button/back.tsx';
import Print from '@/components/button/Print.jsx';
import SummaryStat from '@/components/SummaryStat';
import InventoryParentCard from '@/components/inventory/InventoryParentCard';
import { StockLocationBadge } from '@/components/stock/StockLocation';

const Num = ({ value }) => <NumberFormat displayType="text" value={value} thousandSeparator />;

/**
 * Whether a line's qty is shown. Thaan stock is tracked by meters, so pages can hide its qty.
 */
const showsQty = (item, hideThaanQty) => !(hideThaanQty && item.unit === UNIT_THAAN);

const sumQty = (items, hideThaanQty) =>
    items.filter((item) => showsQty(item, hideThaanQty)).reduce((sum, item) => sum + item.qty, 0);

/**
 * Inventory lines with their qty and meter totals.
 */
const InventoryTable = ({ heading, inventories, showReference, hideThaanQty = false, className = 'mb-0' }) => {
    const hasQty = inventories.some((item) => showsQty(item, hideThaanQty));
    const totalQty = sumQty(inventories, hideThaanQty);
    const totalMeters = inventories.reduce((sum, item) => sum + item.meters, 0);

    return (
        <Panel className={`hf-table-panel ${className}`}>
            <PanelHeader heading={`${heading ?? 'Inventory'} (${inventories.length})`} />
            <PanelBody>
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0 hf-list-table hf-order-items">
                        <thead>
                            <tr>
                                {showReference && <th className="w-1 text-nowrap">Reference no</th>}
                                <th>Product</th>
                                <th className="w-1 text-nowrap">Inbound on</th>
                                <th className="w-1 text-nowrap">Outbound on</th>
                                <th className="w-1 text-center">Unit</th>
                                <th className="w-1">Location</th>
                                <th className="num w-1 text-nowrap">Unit cost</th>
                                <th className="num w-1">Qty</th>
                                <th className="num w-1">Meters</th>
                            </tr>
                        </thead>
                        <tbody>
                            {inventories.map((inventory, index) => (
                                <tr key={inventory.id ?? index}>
                                    {showReference && <td className="text-nowrap">{inventory.reference_no}</td>}
                                    <td className="fw-semibold">{inventory.product?.name}</td>
                                    <td className="text-nowrap"><Moment date={inventory.transaction_date} /></td>
                                    <td className="text-nowrap">
                                        {inventory.outbound_on ? (
                                            <Moment date={inventory.outbound_on} />
                                        ) : (
                                            <span className="hf-pill tone-green">In stock</span>
                                        )}
                                    </td>
                                    <td className="text-center text-nowrap">
                                        {getPOUnit(inventory.unit, inventory.size, inventory.qty)}
                                    </td>
                                    <td>
                                        <StockLocationBadge location={inventory.location} />
                                    </td>
                                    <td className="num"><Num value={inventory.cost} /></td>
                                    <td className="num">
                                        {!showsQty(inventory, hideThaanQty) ? '-' : inventory.qty > 0 ? <Num value={inventory.qty} /> : ''}
                                    </td>
                                    <td className="num"><Num value={inventory.meters} /></td>
                                </tr>
                            ))}
                        </tbody>
                        {inventories.length > 0 && (
                            <tfoot>
                                <tr>
                                    <th colSpan={showReference ? 7 : 6}>Total</th>
                                    <th className="num">{hasQty ? <Num value={totalQty} /> : '-'}</th>
                                    <th className="num"><Num value={totalMeters} /></th>
                                </tr>
                            </tfoot>
                        )}
                    </table>
                    {inventories.length === 0 && <NoData label="No inventory found." />}
                </div>
            </PanelBody>
        </Panel>
    );
};

const StockInventoryView = () => {
    const { page_header, back_url, inventories = [], parent, sections, hide_thaan_qty: hideThaanQty = false } = usePage().props;
    const totalQty = sumQty(inventories, hideThaanQty);
    const totalMeters = inventories.reduce((sum, item) => sum + item.meters, 0);
    const inStock = inventories.filter((item) => !item.outbound_on).length;
    const showReference = !parent;
    const sectionRows = sections?.map((section) => ({
        ...section,
        rows: inventories.filter((item) => item.direction === section.direction),
    }));

    const table = sectionRows ? (
        <>
            {sectionRows.map(({ direction, heading, rows }, index) => (
                <InventoryTable
                    key={direction}
                    heading={heading}
                    inventories={rows}
                    showReference={showReference}
                    hideThaanQty={hideThaanQty}
                    className={index < sectionRows.length - 1 ? 'mb-3' : 'mb-0'}
                />
            ))}
        </>
    ) : (
        <InventoryTable inventories={inventories} showReference={showReference} hideThaanQty={hideThaanQty} />
    );

    return (
        <>
            <Head title={parent ? `Inventory: ${parent.reference_no}` : 'Inventory View'} />
            <PageHeader
                title={page_header}
                buttons={
                    <>
                        <BackButton href={back_url} />
                        <Print />
                    </>
                }
            />
            <PageContent>
                <div className="hf-order-summary mb-3">
                    <SummaryStat icon="solar:box-bold-duotone" label="Items">
                        <Num value={inventories.length} />
                    </SummaryStat>
                    {sectionRows ? (
                        sectionRows.map(({ direction, heading, rows }) => (
                            <SummaryStat
                                key={direction}
                                icon={direction === 'out' ? 'solar:export-bold-duotone' : 'solar:import-bold-duotone'}
                                label={heading}
                                tone={direction === 'in' ? 'brand' : undefined}
                            >
                                {rows.some((item) => showsQty(item, hideThaanQty)) && (
                                    <>
                                        <Num value={sumQty(rows, hideThaanQty)} /> qty ·{' '}
                                    </>
                                )}
                                <Num value={rows.reduce((sum, item) => sum + item.meters, 0)} /> m
                            </SummaryStat>
                        ))
                    ) : (
                        <>
                            <SummaryStat icon="solar:layers-bold-duotone" label="Total qty">
                                <Num value={totalQty} />
                            </SummaryStat>
                            <SummaryStat icon="solar:ruler-bold-duotone" label="Total meters" tone="brand">
                                <Num value={totalMeters} />
                            </SummaryStat>
                        </>
                    )}
                    <SummaryStat icon="solar:archive-check-bold-duotone" label="In stock">
                        <Num value={inStock} /> / <Num value={inventories.length} />
                    </SummaryStat>
                </div>

                {parent ? (
                    <Row className="g-3">
                        <Col xl={8} lg={7}>{table}</Col>
                        <Col xl={4} lg={5}>
                            <div className="hf-inventory-parent-sticky">
                                <InventoryParentCard parent={parent} />
                            </div>
                        </Col>
                    </Row>
                ) : (
                    table
                )}
            </PageContent>
        </>
    );
};

export default StockInventoryView;

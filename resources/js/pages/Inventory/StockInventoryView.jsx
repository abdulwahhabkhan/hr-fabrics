import React from 'react';
import { Col, Row } from 'react-bootstrap';
import { Head, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { NumberFormat } from '@/util/NumberFormat.jsx';
import { getPOUnit } from '@/util/util.jsx';
import Moment from '@/components/Moment.jsx';
import NoData from '@/components/NoData.jsx';
import BackButton from '@/components/button/back.tsx';
import Print from '@/components/button/Print.jsx';
import SummaryStat from '@/components/SummaryStat';
import InventoryParentCard from '@/components/inventory/InventoryParentCard';

const Num = ({ value }) => <NumberFormat displayType="text" value={value} thousandSeparator />;

const StockInventoryView = () => {
    const { page_header, back_url, inventories = [], parent } = usePage().props;
    const totalQty = inventories.reduce((sum, item) => sum + item.qty, 0);
    const totalMeters = inventories.reduce((sum, item) => sum + item.meters, 0);
    const inStock = inventories.filter((item) => !item.outbound_on).length;
    const showReference = !parent;

    const table = (
        <Panel className="hf-table-panel mb-0">
            <PanelHeader heading={`Inventory (${inventories.length})`} />
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
                                    <td className="num"><Num value={inventory.cost} /></td>
                                    <td className="num">{inventory.qty > 0 ? <Num value={inventory.qty} /> : ''}</td>
                                    <td className="num"><Num value={inventory.meters} /></td>
                                </tr>
                            ))}
                        </tbody>
                        {inventories.length > 0 && (
                            <tfoot>
                                <tr>
                                    <th colSpan={showReference ? 6 : 5}>Total</th>
                                    <th className="num"><Num value={totalQty} /></th>
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
                    <SummaryStat icon="solar:layers-bold-duotone" label="Total qty">
                        <Num value={totalQty} />
                    </SummaryStat>
                    <SummaryStat icon="solar:ruler-bold-duotone" label="Total meters" tone="brand">
                        <Num value={totalMeters} />
                    </SummaryStat>
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

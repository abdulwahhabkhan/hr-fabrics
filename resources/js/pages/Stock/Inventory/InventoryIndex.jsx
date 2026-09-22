import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { InventoryFilter } from '@/pages/Stock/Inventory/InventoryFilter';
import { NumberFormat } from '@/util/NumberFormat';
import { Col, Row } from 'react-bootstrap';
import NoData from '@/components/NoData.jsx';

const Inventory = () => {
    const { items, filters, unit_sums } = usePage().props;
    return (
        <>
            <Head title="Inventory Details" />
            <PageHeader title="Inventory Details" />
            <PageContent>
                <Panel>
                    <PanelHeader>Stock Info</PanelHeader>
                    <PanelBody>
                        <InventoryFilter filters={filters} />
                        <Row className="fw-bold mb-10px p-t-5 p-b-5">
                            {unit_sums.map(({ unit, qty, meters }, index) => {
                                return (
                                    <Col sm={4} key={index}>
                                        Total {unit}:
                                        <span className="ms-5px">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={qty}
                                                thousandSeparator={true}
                                            />{' '}
                                            &{' '}
                                            <NumberFormat
                                                displayType={'text'}
                                                value={meters}
                                                thousandSeparator={true}
                                            />
                                            m
                                        </span>
                                    </Col>
                                );
                            })}
                        </Row>
                        <div className={'table-responsive'}>
                            <table className={'table table-bordered'}>
                                <thead>
                                    <tr>
                                        <th className="num">#</th>
                                        <th>Item Name</th>
                                        <th className="w-1">Finish</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1">Size</th>
                                        <th className={'num'}>Qty</th>
                                        <th className={'num'}>Meters</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map(
                                        (
                                            {
                                                sku,
                                                unit,
                                                size,
                                                qty,
                                                meters,
                                                finish,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className="num">
                                                        {index + 1}
                                                    </td>
                                                    <td>{sku}</td>
                                                    <td className="w-1">
                                                        {finish}
                                                    </td>
                                                    <td className="w-1">
                                                        {unit}
                                                    </td>
                                                    <td className="w-1 num">
                                                        {size > 0 ? size : '-'}
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={meters}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {items.length === 0 && <NoData />}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Inventory;

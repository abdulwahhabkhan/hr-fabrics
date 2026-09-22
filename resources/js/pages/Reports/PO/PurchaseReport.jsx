import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import Pagination from '@/components/Pagination';
import { NumberFormat } from '@/util/NumberFormat';
import PurchaseReportFilter from '@/pages/Reports/PO/PurchaseReportFilter';
import NoData from '@/components/NoData.jsx';

const PurchaseReport = () => {
    const { rows, filters } = usePage().props;
    const {
        data,
        meta: { links },
    } = rows;

    return (
        <>
            <Head title="Purchase Report" />
            <PageHeader title="Purchase Report" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={'Purchases Report'} buttons={<></>} />
                    <PanelBody>
                        <PurchaseReportFilter />

                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th width={'40'}>Sr</th>
                                        <th width={'100'}>Inv. No</th>
                                        <th width={'80'}>Bilti No</th>
                                        <th>Product</th>
                                        <th>Finish</th>
                                        <th width={'90px'}>Type</th>
                                        <th className={'num'} width={'90px'}>
                                            Size
                                        </th>
                                        <th className={'num'} width={'90px'}>
                                            Mtrs
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                invoice_no,
                                                bilti_no,
                                                product_name,
                                                finish,
                                                type,
                                                total_qty,
                                                size,
                                                qty,
                                                status,
                                                updated_at,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>{invoice_no}</td>
                                                    <td>{bilti_no}</td>
                                                    <td>{product_name}</td>
                                                    <td>{finish}</td>
                                                    <td>{type}</td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={size}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                        m
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_qty}
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
                            {data.length === 0 && (
                                <NoData label="No purchase records found." />
                            )}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default PurchaseReport;

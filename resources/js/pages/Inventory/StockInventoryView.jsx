import { Head, usePage } from '@inertiajs/react';
import { NumberFormat } from '@/util/NumberFormat.jsx';
import React from 'react';
import { getPOUnit } from '@/util/util.jsx';
import Moment from '@/components/Moment.jsx';
import NoData from '@/components/NoData.jsx';
import BackButton from '@/components/button/back.tsx';
import Print from '@/components/button/Print.jsx';

const StockInventoryView = () => {
    const {page_header, back_url, inventories} = usePage().props
    const totalQty = inventories.reduce((acc, item) => acc + item.qty, 0)
    const totalMeters = inventories.reduce((acc, item) => acc + item.meters, 0)
    return (
        <>
            <Head title="Inventory View" />
            <div className="d-flex mb-lg-3 mb-2 hidden-print">
                <div className="page-header mb-0 flex-1">{page_header}</div>
                <div className="d-flex gap-2 align-items-center">
                    <BackButton href={back_url} />
                    <Print  />
                </div>
            </div>
            <div className="invoice p-0 rounded-3 overflow-hidden">

                <div className="table-responsive mb-0">
                    <table className="table table-card mb-0">
                        <thead>
                        <tr>
                            <th className='w-1'>Reference No</th>
                            <th>Product</th>
                            <th className="w-1">Inbound On</th>
                            <th className="w-1">Outbound On</th>
                            <th className="text-center w-1">Unit</th>
                            <th className="num w-1">Unit Cost</th>
                            <th className="num w-1">Qty</th>
                            <th className="num w-1">Meters</th>

                        </tr>
                        </thead>
                        <tbody>
                        {
                            inventories && inventories.map((inventory, index) => {
                                return (
                                    <tr key={index}>
                                        <td className='w-1'>{inventory.reference_no}</td>
                                        <td>
                                            {inventory.product && inventory.product.name}
                                        </td>
                                        <td className="w-1"><Moment date={inventory.transaction_date}/></td>
                                        <td className=" w-1">
                                            {inventory.outbound_on && (
                                                <Moment date={inventory.outbound_on}/>
                                            )}
                                            {!inventory.outbound_on && (
                                                <span className="text-muted">In Stock</span>
                                            )}
                                        </td>
                                        <td className="text-center  w-1">
                                            {getPOUnit(inventory.unit, inventory.size, inventory.qty)}
                                        </td>
                                        <td className="num  w-1">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={inventory.cost}
                                                thousandSeparator={true}/>
                                        </td>
                                        <td className="num w-1">
                                            {
                                                inventory.qty > 0 ?
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={inventory.qty}
                                                        thousandSeparator={true}/>
                                                    : ''
                                            }

                                        </td>
                                        <td className="num  w-1">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={inventory.meters}
                                                thousandSeparator={true}/>
                                        </td>
                                    </tr>
                                )
                            })
                        }
                        </tbody>
                        {
                            inventories && inventories.length > 0 && (
                                <tfoot>
                                <tr className="bg-light fw-bold">
                                    <td colSpan={6}>Total</td>
                                    <td className='num'>
                                        <NumberFormat
                                            displayType={'text'}
                                            value={totalQty}
                                            thousandSeparator={true}/>
                                    </td>
                                    <td className='num'>
                                        <NumberFormat
                                            displayType={'text'}
                                            value={totalMeters}
                                            thousandSeparator={true}/>
                                    </td>
                                </tr>
                                </tfoot>
                            )
                        }
                    </table>
                    {
                        inventories && inventories.length === 0 && (
                            <>
                                <NoData label={'No Data Found!'}/>

                            </>

                        )
                    }
                </div>
            </div>
        </>
    )
}

export default StockInventoryView
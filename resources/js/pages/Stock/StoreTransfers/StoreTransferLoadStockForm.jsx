import * as React from 'react';
import { useState } from 'react';
import { Button, Col, Form, Modal, Row, Table } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import StyledSelect from '@/components/StyledSelect';
import { UNIT_BOX, usePackingUnits } from '@/util/util';
import NoData from '@/components/NoData.jsx';

export const StoreTransferLoadStockForm = ({
    show,
    onClose,
    onSave,
    products,
    loading,
    storeTransferId,
}) => {
    const [product, setProduct] = useState(null);
    const [unit, setUnit] = useState('');
    const [expense, setExpense] = useState('');
    const [loadingStock, setLoadingStock] = useState(false);
    const [stockRows, setStockRows] = useState(null);
    const [transferQty, setTransferQty] = useState({});
    const [transferMeters, setTransferMeters] = useState({});

    const packingUnits = usePackingUnits();
    const units = packingUnits.filter((val) => {
        if (product?.is_box) return val === UNIT_BOX;
        else return val !== UNIT_BOX;
    });

    const isBox = unit === UNIT_BOX;
    const rowKey = (row) => `${row.size}_${row.cost}`;

    const handleClose = () => {
        onClose();
    };

    const handleProductChange = (value) => {
        setProduct(value);
        setUnit(value?.is_box ? UNIT_BOX : '');
        setStockRows(null);
        setTransferQty({});
        setTransferMeters({});
    };

    const handleUnitChange = (e) => {
        setUnit(e.target.value);
        setStockRows(null);
        setTransferQty({});
        setTransferMeters({});
    };

    const loadStock = () => {
        setLoadingStock(true);
        axios({
            method: 'get',
            url: route('stocks.store-transfers.stock', storeTransferId),
            params: { product_id: product.product_id, unit },
        })
            .then((res) => {
                setStockRows(res.data.rows);
                const empty = {};
                res.data.rows.forEach((row) => {
                    empty[rowKey(row)] = '';
                });
                setTransferQty(empty);
                setTransferMeters({ ...empty });
            })
            .finally(() => {
                setLoadingStock(false);
            });
    };

    const handleQtyChange = (row, value) => {
        let qty = value === '' ? '' : Number(value);
        if (isBox && qty !== '' && qty > row.available_qty) {
            qty = row.available_qty;
        }
        if (qty !== '' && qty < 0) {
            qty = 0;
        }
        setTransferQty({ ...transferQty, [rowKey(row)]: qty });
        if (!isBox) {
            const meters =
                qty === ''
                    ? ''
                    : Math.min(
                          qty * (parseFloat(row.size) || 0),
                          row.available_meters,
                      );
            setTransferMeters({ ...transferMeters, [rowKey(row)]: meters });
        }
    };

    const handleMetersChange = (row, value) => {
        let meters =
            value === ''
                ? ''
                : Math.max(0, Math.min(Number(value), row.available_meters));
        setTransferMeters({ ...transferMeters, [rowKey(row)]: meters });
    };

    const rowMeters = (row) => parseFloat(transferMeters[rowKey(row)]) || 0;
    const rowTotal = (row) => {
        const qty = parseFloat(transferQty[rowKey(row)]) || 0;
        const expenseRate = parseFloat(expense) || 0;
        const base = isBox ? qty : rowMeters(row);
        return base * ((parseFloat(row.cost) || 0) + expenseRate);
    };

    const hasTransferQty =
        stockRows &&
        stockRows.some(
            (row) => (parseFloat(transferQty[rowKey(row)]) || 0) > 0,
        );

    const handleSave = () => {
        const rows = stockRows
            .map((row) => ({
                product_id: product.product_id,
                unit,
                size: row.size,
                cost: row.cost,
                qty: parseFloat(transferQty[rowKey(row)]) || 0,
                meters: isBox ? null : rowMeters(row),
            }))
            .filter((row) => row.qty > 0);

        onSave({ expense, rows });
    };

    return (
        <Modal show={show} backdrop="static" size={'lg'} keyboard={true}>
            <Modal.Header>
                <Modal.Title>Transfer Item</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={12}>
                        <Form.Group className="mb-3">
                            <Form.Label>Product:</Form.Label>
                            <StyledSelect
                                options={products}
                                value={product}
                                onChange={handleProductChange}
                                getOptionValue={(option) =>
                                    option['product_id']
                                }
                                getOptionLabel={(option) =>
                                    option['product_info']
                                }
                                isClearable
                            />
                        </Form.Group>
                    </Col>
                    <Col md={5}>
                        <Form.Group className="mb-3">
                            <Form.Label>Unit:</Form.Label>
                            <Form.Select
                                value={unit}
                                onChange={handleUnitChange}
                            >
                                <option value="">Unit</option>
                                {units &&
                                    units.map((item, index) => (
                                        <option key={index}>{item}</option>
                                    ))}
                            </Form.Select>
                        </Form.Group>
                    </Col>
                    <Col md={5}>
                        <Form.Group className="mb-3">
                            <Form.Label>Expense:</Form.Label>
                            <Form.Control
                                type={'number'}
                                value={expense}
                                onChange={(e) => setExpense(e.target.value)}
                            />
                        </Form.Group>
                    </Col>
                    <Col md={2} className={'d-flex align-items-end'}>
                        <LoadingButton
                            className={'mb-3 w-100'}
                            processing={loadingStock}
                            disabled={!product || !unit}
                            onClick={loadStock}
                        >
                            Load Stock
                        </LoadingButton>
                    </Col>
                </Row>

                {stockRows !== null && (
                    <>
                        <Table bordered hover>
                            <thead>
                                <tr>
                                    <th className={'num'}>Size</th>
                                    <th className={'num'}>Cost</th>
                                    {isBox && (
                                        <th className={'num'}>Available Qty</th>
                                    )}
                                    {!isBox && (
                                        <th className={'num'}>
                                            Available Meters
                                        </th>
                                    )}
                                    <th className={'num'}>Transfer Qty</th>
                                    {!isBox && (
                                        <th className={'num'}>
                                            Transfer Meters
                                        </th>
                                    )}
                                    <th className={'num'}>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {stockRows.map((row, index) => (
                                    <tr key={index}>
                                        <td className={'num'}>{row.size}</td>
                                        <td className={'num'}>{row.cost}</td>
                                        {isBox && (
                                            <td className={'num'}>
                                                {row.available_qty}
                                            </td>
                                        )}
                                        {!isBox && (
                                            <td className={'num'}>
                                                {row.available_meters}
                                            </td>
                                        )}
                                        <td className={'num'}>
                                            <Form.Control
                                                type={'number'}
                                                min={0}
                                                max={
                                                    isBox
                                                        ? row.available_qty
                                                        : undefined
                                                }
                                                value={
                                                    transferQty[rowKey(row)] ??
                                                    ''
                                                }
                                                onChange={(e) =>
                                                    handleQtyChange(
                                                        row,
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </td>
                                        {!isBox && (
                                            <td className={'num'}>
                                                <Form.Control
                                                    type={'number'}
                                                    min={0}
                                                    max={row.available_meters}
                                                    value={
                                                        transferMeters[
                                                            rowKey(row)
                                                        ] ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        handleMetersChange(
                                                            row,
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                            </td>
                                        )}
                                        <td className={'num'}>
                                            {rowTotal(row).toLocaleString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                        {stockRows.length === 0 && (
                            <NoData label="No available stock found for this product and unit." />
                        )}
                    </>
                )}
            </Modal.Body>
            <Modal.Footer>
                <Button variant="white" onClick={handleClose}>
                    Close
                </Button>
                <LoadingButton
                    processing={loading}
                    disabled={!hasTransferQty}
                    onClick={handleSave}
                >
                    Save
                </LoadingButton>
            </Modal.Footer>
        </Modal>
    );
};

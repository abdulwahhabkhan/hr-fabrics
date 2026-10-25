import React, { useEffect, useMemo, useState } from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { Button, Form, InputGroup, Spinner } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import LoadingButton from '@/components/LoadingButton';
import StyledSelect from '@/components/StyledSelect';
import ValidationErrors from '@/components/ValidationErrors';
import BackButton from '@/components/button/back';
import { FormActions, FormField, FormSection, OptionCards, SegmentedControl } from '@/components/form/FormSection';
import { NumberFormat } from '@/util/NumberFormat';
import { UNIT_BOX, UNIT_SUIT, UNIT_THAAN, useStockLocations } from '@/util/util';
import stockTransfers from '@/routes/stocks/stock-transfers';

const UNIT_ICONS = {
    [UNIT_BOX]: 'solar:box-bold-duotone',
    [UNIT_SUIT]: 'solar:t-shirt-bold-duotone',
    [UNIT_THAAN]: 'solar:layers-bold-duotone',
};

const LOCATION_ICONS = {
    shop: 'solar:shop-2-bold-duotone',
};

const toNumber = (value) => parseFloat(value) || 0;

const round = (value) => Math.round(value * 100) / 100;

const Meters = ({ value }) => <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />;

/**
 * Thaan stock is moved by meters, so its sizes are pooled into one line.
 */
const stockKey = (unit, size) => (unit === UNIT_THAAN ? UNIT_THAAN : `${unit}|${toNumber(size)}`);

/**
 * Group the availability rows into one line per unit/size with the stock held at each location.
 */
const buildMatrix = (rows) => {
    const lines = {};
    rows.forEach(({ location, unit, size, qty, meters }) => {
        const key = stockKey(unit, size);
        lines[key] ??= { key, unit, size: unit === UNIT_THAAN ? 0 : toNumber(size), byLocation: {} };
        const cell = (lines[key].byLocation[location] ??= { qty: 0, meters: 0 });
        cell.qty += qty;
        cell.meters = round(cell.meters + meters);
    });

    return Object.values(lines);
};

const StockTransferForm = () => {
    const { products, transfer, errors: serverErrors } = usePage().props;
    const { locations, locationLabel } = useStockLocations();
    const isEdit = Boolean(transfer);
    const productOptions = products?.data ?? products ?? [];

    const initialProduct = isEdit
        ? (productOptions.find((option) => option.product_id === transfer.product_id) ?? {
              product_id: transfer.product_id,
              name: transfer.product_name,
              finish: transfer.product_finish,
              product_info: `${transfer.product_name} ${transfer.product_finish ?? ''}`.trim(),
          })
        : null;

    const [stock, setStock] = useState([]);
    const [stockLoading, setStockLoading] = useState(false);
    const [processing, setProcessing] = useState(null);

    const {
        register,
        handleSubmit,
        setValue,
        watch,
        control,
        formState: { errors },
    } = useForm({
        defaultValues: {
            from_location: transfer?.from_location ?? locations[0]?.id ?? '',
            to_location: transfer?.to_location ?? locations[1]?.id ?? '',
            product: initialProduct,
            unit: transfer?.unit ?? '',
            size: transfer?.unit && transfer.unit !== UNIT_THAAN ? transfer.size : '',
            qty: transfer?.qty ?? '',
            meters: transfer?.unit === UNIT_THAAN ? transfer.meters : '',
            notes: transfer?.notes ?? '',
        },
    });

    const { from_location: from, to_location: to, product, unit, size, qty, meters } = watch();
    const isThaan = unit === UNIT_THAAN;

    const loadStock = (productId) => {
        setStock([]);
        if (!productId) {
            return;
        }

        setStockLoading(true);
        axios
            .get(stockTransfers.availability(productId).url)
            .then((res) => setStock(res.data.stock))
            .catch((error) => console.error(error))
            .finally(() => setStockLoading(false));
    };

    useEffect(() => {
        loadStock(initialProduct?.product_id);
    }, []);

    useEffect(() => {
        if (from && from === to) {
            setValue('to_location', locations.find(({ id }) => id !== from)?.id ?? '', { shouldDirty: true });
        }
    }, [from]);

    const matrix = useMemo(() => buildMatrix(stock), [stock]);
    const totalAt = (location) => round(matrix.reduce((sum, line) => sum + (line.byLocation[location]?.meters ?? 0), 0));

    const fromLines = matrix.filter((line) => line.byLocation[from]?.meters > 0);
    const units = [...new Set(fromLines.map((line) => line.unit))];
    const sizes = fromLines.filter((line) => line.unit === unit && unit !== UNIT_THAAN);
    const selectedLine = unit ? matrix.find((line) => line.key === stockKey(unit, size)) : null;
    const available = selectedLine?.byLocation[from] ?? { qty: 0, meters: 0 };
    const atDestination = selectedLine?.byLocation[to] ?? { qty: 0, meters: 0 };

    useEffect(() => {
        if (!unit && units.length === 1) {
            setValue('unit', units[0], { shouldDirty: true });
        }
    }, [units.join(), unit]);

    useEffect(() => {
        if (unit && unit !== UNIT_THAAN && !toNumber(size) && sizes.length === 1) {
            setValue('size', sizes[0].size, { shouldDirty: true });
        }
    }, [unit, sizes.length]);

    const movingMeters = isThaan ? toNumber(meters) : round(toNumber(qty) * toNumber(size));
    const tooManyPieces = !isThaan && toNumber(qty) > available.qty;
    const tooManyMeters = movingMeters > available.meters;
    const isReady = product && unit && (isThaan || toNumber(size) > 0) && toNumber(qty) > 0 && movingMeters > 0;
    const canSubmit = isReady && !tooManyPieces && !tooManyMeters && !stockLoading && from !== to;

    const selectProduct = (selected) => {
        loadStock(selected?.product_id);
        setValue('unit', '', { shouldDirty: true });
        setValue('size', '', { shouldDirty: true });
        setValue('qty', '', { shouldDirty: true });
        setValue('meters', '', { shouldDirty: true });
    };

    const pickLine = (line, location) => {
        if (location && location !== from) {
            setValue('from_location', location, { shouldDirty: true });
        }
        setValue('unit', line.unit, { shouldDirty: true });
        setValue('size', line.unit === UNIT_THAAN ? '' : line.size, { shouldDirty: true });
    };

    const swapLocations = () => {
        setValue('from_location', to, { shouldDirty: true });
        setValue('to_location', from, { shouldDirty: true });
    };

    const moveEverything = () => {
        setValue('qty', available.qty, { shouldDirty: true });
        if (isThaan) {
            setValue('meters', available.meters, { shouldDirty: true });
        }
    };

    const sendRequest = (confirm) => (data) => {
        const payload = {
            product_id: data.product?.product_id,
            unit: data.unit,
            size: isThaan ? null : data.size,
            qty: data.qty,
            meters: isThaan ? data.meters : null,
            from_location: data.from_location,
            to_location: data.to_location,
            notes: data.notes,
            confirm,
        };
        const options = { onFinish: () => setProcessing(null) };

        setProcessing(confirm ? 'confirm' : 'draft');
        if (isEdit) {
            Inertia.put(stockTransfers.update(transfer.id), payload, options);
        } else {
            Inertia.post(stockTransfers.store(), payload, options);
        }
    };

    const locationCards = (exclude) =>
        locations.map(({ id, name }) => ({
            value: id,
            label: name,
            icon: LOCATION_ICONS[id] ?? 'solar:buildings-2-bold-duotone',
            disabled: id === exclude,
            description: product ? (stockLoading ? 'Checking stock…' : `${totalAt(id)} m of this product`) : undefined,
        }));

    return (
        <>
            <Head title={isEdit ? `Edit Transfer ${transfer.transfer_no}` : 'Move Stock'} />
            <PageHeader
                title={isEdit ? `Edit Transfer ${transfer.transfer_no}` : 'Move Stock'}
                description="Move one product at a time between locations"
                buttons={<BackButton href={stockTransfers.index()} label="Stock Transfers" />}
            />

            <PageContent>
                <ValidationErrors errors={serverErrors} />
                <form onSubmit={handleSubmit(sendRequest(true))}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:routing-2-bold-duotone"
                                title="Route"
                                description="Where the stock is now and where it should go."
                            >
                                <div className="hf-transfer-route">
                                    <FormField label="From" required>
                                        <OptionCards name="from_location" options={locationCards(null)} register={register} />
                                    </FormField>
                                    <Button
                                        variant="white"
                                        className="hf-transfer-route__swap"
                                        onClick={swapLocations}
                                        title="Swap locations"
                                        aria-label="Swap locations"
                                    >
                                        <Icon icon="solar:transfer-horizontal-bold-duotone" />
                                    </Button>
                                    <FormField label="To" required>
                                        <OptionCards
                                            name="to_location"
                                            options={locationCards(from)}
                                            register={register}
                                            invalid={Boolean(serverErrors?.to_location)}
                                        />
                                    </FormField>
                                </div>
                            </FormSection>

                            <FormSection
                                icon="solar:t-shirt-bold-duotone"
                                title="Product"
                                description="Pick the product, then the unit and size you are moving."
                            >
                                <FormField label="Product" required>
                                    <Controller
                                        render={({ field }) => (
                                            <StyledSelect
                                                {...field}
                                                options={productOptions}
                                                onChange={(selected) => {
                                                    field.onChange(selected);
                                                    selectProduct(selected);
                                                }}
                                                getOptionValue={(option) => option['product_id']}
                                                getOptionLabel={(option) => option['product_info']}
                                                placeholder="Search product by name, finish or brand..."
                                                isClearable
                                                autoFocus={!isEdit}
                                            />
                                        )}
                                        control={control}
                                        name="product"
                                        rules={{ required: true }}
                                    />
                                    {errors.product && <div className="invalid-feedback d-block">Please select a product.</div>}
                                </FormField>

                                {product && (
                                    <div className="hf-item-product">
                                        <div className="d-flex align-items-center gap-2 mb-2">
                                            <span className="hf-item-stock__label">Stock by location</span>
                                            {stockLoading && <Spinner animation="border" size="sm" />}
                                        </div>
                                        {!stockLoading && matrix.length === 0 && (
                                            <span className="text-danger fw-semibold fs-12px">This product has no stock at any location.</span>
                                        )}
                                        {!stockLoading && matrix.length > 0 && (
                                            <table className="table table-sm align-middle mb-0 hf-stock-matrix">
                                                <thead>
                                                    <tr>
                                                        <th>Unit / size</th>
                                                        {locations.map(({ id, name }) => (
                                                            <th key={id} className={cx('text-end', { 'is-from': id === from, 'is-to': id === to })}>
                                                                {name}
                                                            </th>
                                                        ))}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {matrix.map((line) => (
                                                        <tr key={line.key} className={cx({ 'is-active': selectedLine?.key === line.key })}>
                                                            <td>
                                                                <Icon icon={UNIT_ICONS[line.unit]} className="me-1" />
                                                                <strong>{line.unit}</strong>
                                                                {line.unit !== UNIT_THAAN && <span className="text-muted"> · {line.size}m</span>}
                                                            </td>
                                                            {locations.map(({ id }) => {
                                                                const cell = line.byLocation[id];
                                                                return (
                                                                    <td key={id} className={cx('text-end hf-mono', { 'is-from': id === from })}>
                                                                        {cell?.meters > 0 ? (
                                                                            <button
                                                                                type="button"
                                                                                className="hf-stock-matrix__pick"
                                                                                onClick={() => pickLine(line, id)}
                                                                                title={`Move ${line.unit} from ${locationLabel(id)}`}
                                                                            >
                                                                                {line.unit !== UNIT_THAAN && <>{cell.qty} pcs · </>}
                                                                                <Meters value={cell.meters} />m
                                                                            </button>
                                                                        ) : (
                                                                            <span className="text-muted">-</span>
                                                                        )}
                                                                    </td>
                                                                );
                                                            })}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        )}
                                    </div>
                                )}

                                {product && !stockLoading && matrix.length > 0 && units.length === 0 && (
                                    <div className="hf-item-warning">
                                        <Icon icon="solar:danger-triangle-bold-duotone" />
                                        Nothing to move from {locationLabel(from)}. Pick a stock line above to move it from another location.
                                    </div>
                                )}

                                {units.length > 0 && (
                                    <FormField label="Unit" required className="mt-3">
                                        <div>
                                            <SegmentedControl
                                                name="unit"
                                                register={register}
                                                options={units.map((value) => ({ value, label: value, icon: UNIT_ICONS[value] }))}
                                            />
                                        </div>
                                    </FormField>
                                )}

                                {unit && unit !== UNIT_THAAN && sizes.length > 0 && (
                                    <FormField label={`Size at ${locationLabel(from)}`} required className="mt-3">
                                        <div className="hf-item-stock mt-0">
                                            {sizes.map((line) => (
                                                <button
                                                    key={line.key}
                                                    type="button"
                                                    className={cx('hf-stock-chip', { 'is-active': selectedLine?.key === line.key })}
                                                    onClick={() => pickLine(line)}
                                                >
                                                    <strong>{line.size}m</strong>
                                                    <span className="hf-stock-chip__qty">
                                                        {line.byLocation[from].qty} pcs · <Meters value={line.byLocation[from].meters} />m
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    </FormField>
                                )}
                            </FormSection>

                            {unit && (isThaan || toNumber(size) > 0) && (
                                <FormSection
                                    icon="solar:calculator-bold-duotone"
                                    title="Quantity"
                                    description={
                                        isThaan
                                            ? 'Thaan stock moves by meters. Enter the number of thaans and their total meters.'
                                            : `Meters are worked out from ${unit} × size.`
                                    }
                                >
                                    <div className="row g-3">
                                        <div className="col-sm-6">
                                            <FormField
                                                label={unit}
                                                htmlFor="qty"
                                                required
                                                hint={!isThaan && `${available.qty} available at ${locationLabel(from)}`}
                                            >
                                                <InputGroup>
                                                    <Form.Control
                                                        id="qty"
                                                        type="number"
                                                        min={1}
                                                        step={1}
                                                        inputMode="numeric"
                                                        {...register('qty', { required: true, min: 1 })}
                                                        isInvalid={errors.qty || tooManyPieces || serverErrors?.qty}
                                                        placeholder="0"
                                                    />
                                                    {available.meters > 0 && (
                                                        <Button variant="white" onClick={moveEverything} title="Move all stock of this line">
                                                            All
                                                        </Button>
                                                    )}
                                                </InputGroup>
                                            </FormField>
                                        </div>
                                        <div className="col-sm-6">
                                            <FormField
                                                label="Meters"
                                                htmlFor="meters"
                                                required={isThaan}
                                                hint={`${available.meters} m available at ${locationLabel(from)}`}
                                            >
                                                <InputGroup className="hf-amount">
                                                    {isThaan ? (
                                                        <Form.Control
                                                            id="meters"
                                                            type="number"
                                                            min={0.01}
                                                            step="any"
                                                            inputMode="decimal"
                                                            {...register('meters', { required: isThaan, min: 0.01 })}
                                                            isInvalid={errors.meters || tooManyMeters}
                                                            placeholder="0"
                                                        />
                                                    ) : (
                                                        <Form.Control id="meters" value={movingMeters || ''} readOnly placeholder="0" />
                                                    )}
                                                    <InputGroup.Text>m</InputGroup.Text>
                                                </InputGroup>
                                            </FormField>
                                        </div>
                                        <div className="col-12">
                                            <FormField label="Notes" htmlFor="notes">
                                                <Form.Control
                                                    id="notes"
                                                    {...register('notes', { maxLength: 255 })}
                                                    placeholder="Optional, e.g. reason or who is carrying it"
                                                />
                                            </FormField>
                                        </div>
                                    </div>

                                    {(tooManyPieces || tooManyMeters) && (
                                        <div className="hf-item-warning">
                                            <Icon icon="solar:danger-triangle-bold-duotone" />
                                            Only {!isThaan && <>{available.qty} {unit} / </>}
                                            <Meters value={available.meters} /> m available at {locationLabel(from)}.
                                        </div>
                                    )}

                                    {isReady && !tooManyPieces && !tooManyMeters && (
                                        <div className="hf-transfer-summary">
                                            <div className="hf-transfer-summary__move">
                                                Move <strong>{qty} {unit}</strong>
                                                {!isThaan && <> of {size}m</>} (<Meters value={movingMeters} /> m)
                                            </div>
                                            <div className="hf-transfer-summary__route">
                                                <div>
                                                    <span>{locationLabel(from)} after</span>
                                                    <strong>
                                                        {!isThaan && <>{available.qty - toNumber(qty)} pcs · </>}
                                                        <Meters value={round(available.meters - movingMeters)} /> m
                                                    </strong>
                                                </div>
                                                <Icon icon="solar:arrow-right-bold-duotone" />
                                                <div>
                                                    <span>{locationLabel(to)} after</span>
                                                    <strong>
                                                        {!isThaan && <>{atDestination.qty + toNumber(qty)} pcs · </>}
                                                        <Meters value={round(atDestination.meters + movingMeters)} /> m
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </FormSection>
                            )}
                        </PanelBody>
                        <FormActions hint="A draft does not move stock until it is confirmed.">
                            <BackButton href={stockTransfers.index()} label="Cancel" />
                            <LoadingButton
                                variant="white"
                                processing={processing === 'draft'}
                                disabled={!canSubmit || processing !== null}
                                onClick={handleSubmit(sendRequest(false))}
                            >
                                Save draft
                            </LoadingButton>
                            <LoadingButton
                                type="submit"
                                processing={processing === 'confirm'}
                                disabled={!canSubmit || processing !== null}
                            >
                                Move stock now
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default StockTransferForm;

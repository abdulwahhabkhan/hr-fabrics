import React, { useEffect, useMemo, useState } from 'react';
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
import { FormActions, FormField, FormSection, OptionCards } from '@/components/form/FormSection';
import { locationIcon } from '@/components/stock/StockLocation';
import { NumberFormat } from '@/util/NumberFormat';
import { UNIT_SUIT, UNIT_THAAN, useStockLocations } from '@/util/util';
import conversions from '@/routes/stocks/conversions';
import stockTransfers from '@/routes/stocks/stock-transfers';

const CONVERSION_TYPES = [
    {
        value: UNIT_SUIT,
        label: 'Thaan → Suit',
        description: 'Cut thaans into ready suit pieces',
        icon: 'solar:scissors-bold-duotone',
    },
    {
        value: UNIT_THAAN,
        label: 'Thaan → Thaan',
        description: 'Re-cut thaans into thaans of a different length',
        icon: 'solar:layers-bold-duotone',
    },
];

const toNumber = (value) => parseFloat(value) || 0;

const round = (value) => Math.round(value * 100) / 100;

const Meters = ({ value }) => <NumberFormat displayType="text" value={round(value)} thousandSeparator decimalScale={2} />;

const ConversionForm = () => {
    const { products, errors: serverErrors } = usePage().props;
    const { locations, locationLabel } = useStockLocations();
    const productOptions = products?.data ?? products ?? [];

    const [stock, setStock] = useState([]);
    const [stockLoading, setStockLoading] = useState(false);
    const [processing, setProcessing] = useState(false);

    const {
        register,
        handleSubmit,
        setValue,
        watch,
        control,
        formState: { errors },
    } = useForm({
        defaultValues: {
            location: locations[0]?.id ?? '',
            product: null,
            from: { unit: UNIT_THAAN, qty: '', size: '' },
            to: { unit: UNIT_SUIT, qty: '', size: '' },
        },
    });

    const { location, product, from, to } = watch();
    const targetUnit = to?.unit ?? UNIT_SUIT;

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

    /** Thaan stock is cut by meters, so its sizes are pooled per location. */
    const thaanByLocation = useMemo(() => {
        const totals = {};
        stock
            .filter((row) => row.unit === UNIT_THAAN)
            .forEach(({ location: at, qty, meters }) => {
                totals[at] ??= { qty: 0, meters: 0 };
                totals[at].qty += qty;
                totals[at].meters = round(totals[at].meters + meters);
            });

        return totals;
    }, [stock]);

    const available = thaanByLocation[location] ?? { qty: 0, meters: 0 };
    const fromMeters = round(toNumber(from?.qty) * toNumber(from?.size));
    const toMeters = round(toNumber(to?.qty) * toNumber(to?.size));
    const leftoverMeters = round(fromMeters - toMeters);
    const piecesFromThaan = toNumber(to?.size) > 0 ? round(fromMeters / toNumber(to.size)) : 0;
    const exactTargetQty = piecesFromThaan >= 1 && Number.isInteger(piecesFromThaan) ? piecesFromThaan : 0;

    const notEnoughStock = Boolean(product) && !stockLoading && fromMeters > available.meters;
    const hasLeftover = fromMeters > 0 && toMeters > 0 && leftoverMeters !== 0;
    const isReady = product && location && fromMeters > 0 && toMeters > 0 && toNumber(to?.qty) >= 1 && toNumber(from?.qty) >= 1;
    const canSubmit = isReady && !notEnoughStock && !hasLeftover && !stockLoading;

    const selectProduct = (selected) => {
        loadStock(selected?.product_id);
    };

    const cutAllThaan = () => {
        setValue('from.qty', available.qty, { shouldDirty: true });
        if (available.qty > 0) {
            setValue('from.size', round(available.meters / available.qty), { shouldDirty: true });
        }
    };

    const fillExactTargetQty = () => {
        setValue('to.qty', exactTargetQty, { shouldDirty: true, shouldValidate: true });
    };

    useEffect(() => {
        if (product && !stockLoading && !available.meters) {
            const withStock = locations.find(({ id }) => thaanByLocation[id]?.meters > 0);
            if (withStock) {
                setValue('location', withStock.id, { shouldDirty: true });
            }
        }
    }, [thaanByLocation]);

    const sendRequest = (data) => {
        if (!canSubmit) {
            return;
        }

        const payload = {
            product_id: data.product?.product_id,
            location: data.location,
            from: { unit: UNIT_THAAN, qty: data.from.qty, size: data.from.size },
            to: { unit: data.to.unit, qty: data.to.qty, size: data.to.size },
        };

        setProcessing(true);
        Inertia.post(conversions.store(), payload, { onFinish: () => setProcessing(false) });
    };

    const locationCards = locations.map(({ id, name }) => ({
        value: id,
        label: name,
        icon: locationIcon(id),
        description: product
            ? stockLoading
                ? 'Checking stock…'
                : `${thaanByLocation[id]?.qty ?? 0} Thaan · ${round(thaanByLocation[id]?.meters ?? 0)} m`
            : undefined,
    }));

    return (
        <>
            <Head title="Add Conversion" />
            <PageHeader
                title="Add Conversion"
                description="Cut thaan stock into suits or thaans of another length"
                buttons={<BackButton href={conversions.index()} label="Conversions" />}
            />

            <PageContent>
                <ValidationErrors errors={serverErrors} />
                <form onSubmit={handleSubmit(sendRequest)}>
                    <Panel className="hf-form-panel">
                        <PanelBody>
                            <FormSection
                                icon="solar:scissors-bold-duotone"
                                title="Conversion type"
                                description="What the thaans are cut into."
                            >
                                <OptionCards name="to.unit" options={CONVERSION_TYPES} register={register} />
                            </FormSection>

                            <FormSection
                                icon="solar:t-shirt-bold-duotone"
                                title="Product & location"
                                description="Pick the product and where its thaans are cut."
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
                                                autoFocus
                                            />
                                        )}
                                        control={control}
                                        name="product"
                                        rules={{ required: true }}
                                    />
                                    {errors.product && <div className="invalid-feedback d-block">Please select a product.</div>}
                                </FormField>

                                <FormField label="Location" required className="mt-3">
                                    <OptionCards
                                        name="location"
                                        options={locationCards}
                                        register={register}
                                        invalid={Boolean(serverErrors?.location)}
                                    />
                                </FormField>

                                {product && !stockLoading && !available.meters && (
                                    <div className="hf-item-warning">
                                        <Icon icon="solar:danger-triangle-bold-duotone" />
                                        This product has no thaan stock at {locationLabel(location)}.
                                    </div>
                                )}
                                {product && stockLoading && (
                                    <div className="d-flex align-items-center gap-2 mt-3 text-muted fs-12px">
                                        <Spinner animation="border" size="sm" /> Checking thaan stock…
                                    </div>
                                )}
                            </FormSection>

                            <FormSection
                                icon="solar:layers-bold-duotone"
                                title="Consume thaan"
                                description="The thaans being cut, taken oldest stock first."
                            >
                                <div className="row g-3">
                                    <div className="col-sm-6">
                                        <FormField
                                            label="No. of Thaan"
                                            htmlFor="from-qty"
                                            required
                                            hint={product && !stockLoading && `${available.qty} Thaan available at ${locationLabel(location)}`}
                                        >
                                            <InputGroup>
                                                <Form.Control
                                                    id="from-qty"
                                                    type="number"
                                                    min={1}
                                                    step={1}
                                                    inputMode="numeric"
                                                    {...register('from.qty', { required: true, min: 1 })}
                                                    isInvalid={errors.from?.qty || serverErrors?.['from.qty']}
                                                    placeholder="0"
                                                />
                                                {available.qty > 0 && (
                                                    <Button variant="white" onClick={cutAllThaan} title="Cut all thaan stock at this location">
                                                        All
                                                    </Button>
                                                )}
                                            </InputGroup>
                                        </FormField>
                                    </div>
                                    <div className="col-sm-6">
                                        <FormField
                                            label="Meters per Thaan"
                                            htmlFor="from-size"
                                            required
                                            hint={product && !stockLoading && <><Meters value={available.meters} /> m available</>}
                                        >
                                            <InputGroup className="hf-amount">
                                                <Form.Control
                                                    id="from-size"
                                                    type="number"
                                                    min={0.01}
                                                    step="any"
                                                    inputMode="decimal"
                                                    {...register('from.size', { required: true, min: 0.01 })}
                                                    isInvalid={errors.from?.size || notEnoughStock}
                                                    placeholder="0"
                                                />
                                                <InputGroup.Text>m</InputGroup.Text>
                                            </InputGroup>
                                        </FormField>
                                    </div>
                                </div>

                                {notEnoughStock && (
                                    <div className="hf-item-warning">
                                        <Icon icon="solar:danger-triangle-bold-duotone" />
                                        Only {available.qty} Thaan (<Meters value={available.meters} /> m) available at{' '}
                                        {locationLabel(location)}.
                                    </div>
                                )}
                            </FormSection>

                            <FormSection
                                icon="solar:t-shirt-bold-duotone"
                                title={`Produce ${targetUnit}`}
                                description={`The ${targetUnit.toLowerCase()} pieces cut from the thaans.`}
                            >
                                <div className="row g-3">
                                    <div className="col-sm-6">
                                        <FormField label={`Meters per ${targetUnit}`} htmlFor="to-size" required>
                                            <InputGroup className="hf-amount">
                                                <Form.Control
                                                    id="to-size"
                                                    type="number"
                                                    min={0.01}
                                                    step="any"
                                                    inputMode="decimal"
                                                    {...register('to.size', { required: true, min: 0.01 })}
                                                    isInvalid={errors.to?.size}
                                                    placeholder="0"
                                                />
                                                <InputGroup.Text>m</InputGroup.Text>
                                            </InputGroup>
                                        </FormField>
                                    </div>
                                    <div className="col-sm-6">
                                        <FormField
                                            label={`No. of ${targetUnit}`}
                                            htmlFor="to-qty"
                                            required
                                            hint={
                                                piecesFromThaan > 0 &&
                                                (exactTargetQty
                                                    ? `${exactTargetQty} ${targetUnit} use all ${fromMeters} m`
                                                    : `${fromMeters} m does not divide evenly into ${toNumber(to?.size)} m pieces`)
                                            }
                                        >
                                            <InputGroup>
                                                <Form.Control
                                                    id="to-qty"
                                                    type="number"
                                                    min={1}
                                                    step={1}
                                                    inputMode="numeric"
                                                    {...register('to.qty', { required: true, min: 1 })}
                                                    isInvalid={errors.to?.qty || hasLeftover || serverErrors?.['to.qty']}
                                                    placeholder="0"
                                                />
                                                <Button
                                                    variant="white"
                                                    onClick={fillExactTargetQty}
                                                    disabled={!exactTargetQty}
                                                    title="Use exactly the meters being cut"
                                                >
                                                    Fill
                                                </Button>
                                            </InputGroup>
                                        </FormField>
                                    </div>
                                </div>

                                {hasLeftover && (
                                    <div className="hf-item-warning">
                                        <Icon icon="solar:danger-triangle-bold-duotone" />
                                        Produced <Meters value={toMeters} /> m must equal the <Meters value={fromMeters} /> m
                                        being cut, leftover is not allowed.
                                    </div>
                                )}

                                {fromMeters > 0 && (
                                    <div className="hf-transfer-summary">
                                        <div className="hf-transfer-summary__move">
                                            Cut <strong>{toNumber(from?.qty)} Thaan</strong> into{' '}
                                            <strong>
                                                {toNumber(to?.qty)} {targetUnit}
                                            </strong>{' '}
                                            at {locationLabel(location)}
                                        </div>
                                        <div className="hf-transfer-summary__route">
                                            <div>
                                                <span>Consumed</span>
                                                <strong>
                                                    <Meters value={fromMeters} /> m
                                                </strong>
                                            </div>
                                            <Icon icon="solar:arrow-right-bold-duotone" />
                                            <div>
                                                <span>Produced</span>
                                                <strong>
                                                    <Meters value={toMeters} /> m
                                                </strong>
                                            </div>
                                            <div>
                                                <span>Leftover</span>
                                                <strong className={leftoverMeters !== 0 ? 'text-danger' : 'text-success'}>
                                                    <Meters value={leftoverMeters} /> m
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </FormSection>
                        </PanelBody>
                        <FormActions hint="Stock is converted as soon as it is saved.">
                            <BackButton href={conversions.index()} label="Cancel" />
                            <LoadingButton type="submit" variant="theme" processing={processing} disabled={!canSubmit || processing}>
                                Save Conversion
                            </LoadingButton>
                        </FormActions>
                    </Panel>
                </form>
            </PageContent>
        </>
    );
};

export default ConversionForm;

import React from 'react';
import { Icon } from '@iconify/react';
import { SegmentedControl } from '@/components/form/FormSection';
import { LOCATION_SHOP, useStockLocations } from '@/util/util';

export const locationIcon = (location) =>
    location === LOCATION_SHOP ? 'solar:shop-2-bold-duotone' : 'solar:buildings-2-bold-duotone';

/** Location picker for an item form, registered through react-hook-form's `register`. */
export function StockLocationControl({ name = 'location', register, className }) {
    const { locations } = useStockLocations();

    return (
        <SegmentedControl
            name={name}
            register={register}
            className={className}
            options={locations.map(({ id, name: label }) => ({ value: id, label, icon: locationIcon(id) }))}
        />
    );
}

/** Location of a stock line in tables and views. */
export function StockLocationBadge({ location }) {
    const { locationLabel } = useStockLocations();

    if (!location) {
        return null;
    }

    return (
        <span className="hf-chip text-nowrap">
            <Icon icon={locationIcon(location)} className="me-1" />
            {locationLabel(location)}
        </span>
    );
}

import React from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Panel, PanelBody } from '@/components/panel/panel';
import Moment from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { NumberFormat } from '@/util/NumberFormat';

const STATUS_TONES = { open: 'gold', close: 'green', closed: 'green', cancel: 'red' };

const formatAddress = (address) =>
    [address?.address, address?.city, address?.region].filter(Boolean).join(', ');

function DetailValue({ value, type }) {
    if (value === null || value === undefined || value === '') {
        return <span className="hf-muted-value">—</span>;
    }
    if (type === 'date') {
        return <Moment format={settings.DATE_FORMAT} date={value} />;
    }
    if (type === 'number') {
        return <NumberFormat displayType="text" value={value} thousandSeparator />;
    }

    return value;
}

/**
 * Right-hand summary of the document (receiving, invoice, return, transfer) the inventory belongs to.
 *
 * @param {{ parent: { label: string, reference_no: string, url?: string, status?: string,
 *   party?: { label: string, name?: string, address?: object },
 *   details?: Array<{ label: string, value: any, type?: 'date'|'number' }> } }} props
 */
export default function InventoryParentCard({ parent }) {
    const { label, reference_no, url, status, party, details = [] } = parent;

    return (
        <Panel className="hf-order-card hf-inventory-parent mb-0">
            <PanelBody>
                <div className="hf-order-card__head">
                    <span className="hf-form-section__icon">
                        <Icon icon="solar:document-text-bold-duotone" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="hf-form-section__desc mb-0">{label}</p>
                        <h2 className="hf-form-section__title mb-0">{reference_no}</h2>
                    </div>
                    {status && (
                        <span className={cx('hf-pill', `tone-${STATUS_TONES[status.toLowerCase()] ?? 'slate'}`)}>
                            {status}
                        </span>
                    )}
                </div>

                {party?.name && (
                    <div className="hf-order-customer mb-3">
                        <span className="hf-order-customer__icon">
                            <Icon icon="solar:user-rounded-bold-duotone" />
                        </span>
                        <div className="min-w-0">
                            <div className="hf-inventory-parent__caption">{party.label}</div>
                            <div className="fw-semibold text-truncate">{party.name}</div>
                            {formatAddress(party.address) && (
                                <div className="hf-inventory-parent__caption text-truncate">
                                    {formatAddress(party.address)}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {details.length > 0 && (
                    <dl className="hf-order-breakdown mt-0">
                        {details.map(({ label: detailLabel, value, type }) => (
                            <div key={detailLabel}>
                                <dt>{detailLabel}</dt>
                                <dd><DetailValue value={value} type={type} /></dd>
                            </div>
                        ))}
                    </dl>
                )}

                {url && (
                    <InertiaLink href={url} className="btn btn-sm btn-white w-100 mt-3 hidden-print">
                        <Icon icon="solar:eye-bold-duotone" /> Open {label.toLowerCase()}
                    </InertiaLink>
                )}
            </PanelBody>
        </Panel>
    );
}

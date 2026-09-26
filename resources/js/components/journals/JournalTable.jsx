import React from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Delete } from '@/components/Actions';
import { NumberFormat } from '@/util/NumberFormat';
import journals from '@/routes/accounts/journals';

const HEAD_TONES = { journal: 'navy', purchases: 'olive', sales: 'green' };

function Amount({ value }) {
    if (!(value > 0)) {
        return <span className="hf-muted-value">—</span>;
    }

    return <NumberFormat displayType="text" value={value} thousandSeparator />;
}

export default function JournalTable({ vouchers, canView, canDelete, onAttachment }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table hf-journal-table">
                <thead>
                    <tr>
                        <th className="w-1">Voucher</th>
                        <th>Account</th>
                        <th>Narration</th>
                        <th className="w-1 text-end">Debit</th>
                        <th className="w-1 text-end">Credit</th>
                        <th className="w-1 text-nowrap">Posted</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {vouchers.map(({ id, reference_no, head, account, detail, date, debit, credit, file }, index) => {
                        const [accountName, ...cityParts] = (account || '').split(', ');
                        const city = cityParts.join(', ');

                        return (
                            <tr key={`${id}-${index}`}>
                                <td className="text-nowrap">
                                    <div className="hf-cell-title hf-mono">{reference_no}</div>
                                    <div className="hf-cell-sub">
                                        <span className={`hf-pill tone-${HEAD_TONES[head] || 'slate'}`}>{head}</span>
                                    </div>
                                </td>
                                <td>
                                    <div className="hf-cell-title">{accountName}</div>
                                    {city && <div className="hf-cell-sub">{city}</div>}
                                </td>
                                <td className="hf-journal-detail">
                                    <span className="hf-clamp-2" title={detail}>{detail || <span className="hf-muted-value">—</span>}</span>
                                </td>
                                <td className={cx('num text-end text-nowrap hf-amount-cell', { 'is-debit': debit > 0 })}>
                                    <Amount value={debit} />
                                </td>
                                <td className={cx('num text-end text-nowrap hf-amount-cell', { 'is-credit': credit > 0 })}>
                                    <Amount value={credit} />
                                </td>
                                <td className="text-nowrap hf-muted-value">
                                    <Moment format={settings.DATE_FORMAT} date={date} />
                                </td>
                                <td className="text-end">
                                    <div className="hf-row-actions">
                                        <button
                                            type="button"
                                            className={cx('hf-icon-btn hf-icon-btn--boxed', { 'has-file': file })}
                                            title={file ? 'View / replace attachment' : 'Add attachment'}
                                            aria-label="Attachment"
                                            onClick={() => onAttachment(id)}
                                        >
                                            <Icon icon="solar:paperclip-bold-duotone" />
                                        </button>
                                        {canView && (
                                            <InertiaLink
                                                href={journals.show(id)}
                                                className="hf-icon-btn hf-icon-btn--boxed"
                                                title="View voucher"
                                                aria-label={`View ${reference_no}`}
                                            >
                                                <Icon icon="solar:eye-bold-duotone" />
                                            </InertiaLink>
                                        )}
                                        {canDelete && (
                                            <span className="hf-icon-btn hf-icon-btn--boxed is-danger">
                                                <Delete action={journals.destroy} id={id} />
                                            </span>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

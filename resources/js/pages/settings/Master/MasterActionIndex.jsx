import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Head, InertiaLink } from '@/util/Inertia';
import masterReceivings from '@/routes/settings/master/fabric-receivings';
import masterOrders from '@/routes/settings/master/orders';

const actions = [
    {
        href: masterReceivings.index(),
        icon: 'solar:lock-keyhole-unlocked-bold-duotone',
        tone: 'danger',
        title: 'Reopen Receivings',
        description: 'Reopen a confirmed fabric receiving and its vouchers to correct stock and prices, even when it is invoiced or sold.',
        affects: ['Stock', 'Purchase vouchers', 'Sale orders'],
    },
    {
        href: masterOrders.index(),
        icon: 'solar:box-minimalistic-bold-duotone',
        tone: 'gold',
        title: 'Allocate Order',
        description: 'Book stock for a confirmed sale order that is unallocated or partially allocated, first-in first-out.',
        affects: ['Stock', 'Sale orders'],
    },
];

function MasterActionCard({ action }) {
    return (
        <InertiaLink href={action.href} className={`hf-master-card tone-${action.tone}`}>
            <div className="hf-master-card__body">
                <span className="hf-master-card__icon" aria-hidden="true">
                    <Icon icon={action.icon} />
                </span>
                <h3 className="hf-master-card__title">{action.title}</h3>
                <p className="hf-master-card__text">{action.description}</p>
                <div className="hf-master-card__tags">
                    {action.affects.map((item) => (
                        <span key={item} className="hf-master-card__tag">{item}</span>
                    ))}
                </div>
            </div>
            <div className="hf-master-card__footer">
                Open
                <Icon icon="solar:arrow-right-linear" />
            </div>
        </InertiaLink>
    );
}

const MasterActionIndex = () => (
    <>
        <Head title="Master Actions" />
        <PageHeader title="Master Actions" description="Corrections available to master users only." />

        <PageContent>
            <div className="hf-master-notice" role="note">
                <Icon icon="solar:danger-triangle-bold-duotone" />
                <div>
                    <strong>These actions bypass the normal locks.</strong>
                    They change confirmed documents and their ledgers. Check the details on each screen before you continue.
                </div>
            </div>

            <div className="hf-master-grid">
                {actions.map((action) => (
                    <MasterActionCard key={action.title} action={action} />
                ))}
            </div>
        </PageContent>
    </>
);

export default MasterActionIndex;

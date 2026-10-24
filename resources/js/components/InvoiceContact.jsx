import { usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import React from 'react';
import contact_qr from '@/img/contact-qr.jpeg';

const ContactItem = ({ icon, label, wide = false, children }) => (
    <div className={'invoice-contact-item' + (wide ? ' is-wide' : '')}>
        <Icon icon={icon} />
        <div>
            <small>{label}</small>
            {children}
        </div>
    </div>
);

/**
 * Invoice footer with the store's phones, address and contact QR code.
 */
export const InvoiceContact = ({ remark }) => {
    const { contact } = usePage().props.store;
    const mobiles = _.castArray(contact.mobile ?? []).filter(Boolean);
    const storeAddress = [contact.address, contact.city]
        .filter(Boolean)
        .join(', ');

    return (
        <div className="invoice-note invoice-contact d-flex">
            <div className="invoice-contact-body flex-fill">
                {remark && <p className="invoice-contact-remark">* {remark}</p>}
                <p className="invoice-contact-lead">
                    Questions about this invoice? Contact our sales team.
                </p>
                <div className="invoice-contact-grid">
                    {mobiles.length > 0 && (
                        <ContactItem
                            icon={'solar:smartphone-2-bold-duotone'}
                            label="Mobile"
                        >
                            {mobiles.map((mobile) => (
                                <span key={mobile}>{mobile}</span>
                            ))}
                        </ContactItem>
                    )}
                    {contact.telephone && (
                        <ContactItem
                            icon={'solar:phone-bold-duotone'}
                            label="PTCL"
                        >
                            <span>{contact.telephone}</span>
                        </ContactItem>
                    )}
                    {storeAddress && (
                        <ContactItem
                            icon={'solar:map-point-bold-duotone'}
                            label="Address"
                            wide
                        >
                            <span>{storeAddress}</span>
                        </ContactItem>
                    )}
                </div>
            </div>
            <div className="invoice-qr">
                <div className="invoice-qr-frame">
                    <img src={contact_qr} alt="Contact QR code" />
                </div>
            </div>
        </div>
    );
};

import * as React from 'react';

export const Address = ({address, name, email, phone}) => {
    const {city, address:address_line, region} = address
    return (
        <>
            <address className="m-t-5 m-b-5">
                <strong className="text-inverse">{name}</strong><br />
                {address_line}<br/>
                {city}<br/>
                {region}
                { phone && (
                    <span>
                        <br/> Phone: {phone}
                    </span>
                )}
                { email && (
                    <span>
                        <br/> Email: {email}
                    </span>
                )}
            </address>
        </>
    );
};

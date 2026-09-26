import * as React from 'react';

export const Address = ({ address, name, email, phone }) => {
    const { city, address: address_line, region } = address;
    return (
        <>
            <address className="m-t-5 m-b-5">
                <strong className="text-inverse">{name}</strong>
                <br />
                {address_line}
                <br />
                {city}
                <br />
                {region}
                {phone && (
                    <span>
                        <br /> Phone: {phone}
                    </span>
                )}
                {email && (
                    <span>
                        <br /> Email: {email}
                    </span>
                )}
            </address>
        </>
    );
};

export const UrduAddress = ({ address, name }) => {
    const { address_urdu, city_urdu, region_urdu } = address ?? {};
    const city = Array.isArray(city_urdu) ? city_urdu[0] : city_urdu;
    const lines = [name, address_urdu, city, region_urdu];
    if (!lines.some(Boolean)) {
        return null;
    }
    return (
        <address className="m-t-5 m-b-5 text-start" dir="rtl" lang="ur">
            {lines.map((line, index) => (
                <React.Fragment key={index}>
                    {index > 0 && <br />}
                    <span className="urdu">{line}</span>
                </React.Fragment>
            ))}
        </address>
    );
};

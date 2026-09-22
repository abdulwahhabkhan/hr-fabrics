import React from "react";

export default function FormLabel({label, value, ...props}) {
    return (
        <>
            <dl {...props}>
                <dt className="form-label text-gray-600 text-uppercase fw-medium text-base">{label} :</dt>
                <dd className="form-control form-control text-nowrap text-truncate form-label-text border-0 ps-0">{value}</dd>
            </dl>
        </>
    )
}

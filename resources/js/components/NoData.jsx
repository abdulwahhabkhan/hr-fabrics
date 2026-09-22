import React from 'react';
import Content from '@/img/ic-content.svg';

export default function NoData({label = 'No data found!'}) {
    return (
        <>
            <div
                className="no-data position-relative px-4 py-50px  border-dashed rounded-3 border">
                <div className="message position-relative top-0 start-0 end-0">
                    <div className="border-1 text-center fw-bold p-2 text-muted">
                        <img src={Content} alt="content" className="img-fluid"/>
                        <div className="fs-4 mt-3 text-gray-600">{label}</div>
                    </div>

                </div>
            </div>

        </>

    )
        ;
}
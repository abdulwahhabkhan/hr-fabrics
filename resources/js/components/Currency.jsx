import {NumberFormat} from "@/util/NumberFormat.jsx";
import React from "react";

export const Currency = ({value, decimals = 2}) => {
    return (
        <>
            {value > 0 && (
                <div className="d-flex">
                    <div className="ps-1">Rs.</div>
                    <div className="ms-auto">
                        <NumberFormat displayType={'text'} value={value} thousandSeparator={true}/>
                    </div>

                </div>

            )}
            {!value && (
                <div className="d-flex">
                    <div className="ps-1">Rs.</div>
                    <div className="ms-auto me-2">-</div>
                </div>
            )}
        </>
    )
}

import React from 'react';

export default function ValidationErrors({ errors }) {
    return (
        Object.keys(errors).length > 0 && (
            <>
                <div className="note alert-warning mb-2 text-start">
                    <div className="note-content">
                        <h4>Validation Error</h4>
                        <ul className="mt-3 list-inside text-sm ">
                            {Object.keys(errors).map(function (key, index) {
                                return <li key={index}>{errors[key]}</li>;
                            })}
                        </ul>
                    </div>
                </div>
            </>
        )
    );
}

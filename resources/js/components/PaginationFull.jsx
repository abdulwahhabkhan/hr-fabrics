import React from 'react';
import {InertiaLink} from "@/util/Inertia";


const PageLink = ({active, label, url}) => {
    const className = 'page-link';
    return (
        <InertiaLink className={className} href={url}>
            <span dangerouslySetInnerHTML={{__html: label}}></span>
        </InertiaLink>
    );
};

// Previous, if on first page
// Next, if on last page
// and dots, if exists (...)
const PageInactive = ({label}) => {
    const className = 'page-link';
    return (
        <div className={className} dangerouslySetInnerHTML={{__html: label}}/>
    );
};

export default ({meta = []}) => {

    if (meta.total === 0) return null;
    return (
        <div className='d-lg-flex align-items-center mb-n2'>
            <div className="d-lg-block d-none ms-2 text-body text-opacity-50">
                Showing {meta.from} to {meta.to} of {meta.total} entries
            </div>
            {
                meta.total > 0 && (
                    <>
                        <ul className="pagination pagination-sm mb-0 ms-auto justify-content-center">
                            {meta.links.map(({active, label, url}, index) => {
                                return url === null ? (
                                    <li key={index} className={'page-item disabled'}>
                                        <PageInactive key={label} label={label}/>
                                    </li>

                                ) : (
                                    <li key={index} className={`page-item  ${active ? 'active' : ''}`}>
                                        <PageLink key={label} label={label} active={active} url={url}/>
                                    </li>

                                );
                            })}
                        </ul>
                    </>
                )
            }

        </div>

    );
};

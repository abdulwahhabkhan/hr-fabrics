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

export default ({links = []}) => {
    // dont render, if there's only 1 page (previous, 1, next)
    if (links.length === 3) return null;
    return (
        <ul className="pagination pagination-sm m-t-0 m-b-5">
            {links.map(({active, label, url}, index) => {
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
    );
};

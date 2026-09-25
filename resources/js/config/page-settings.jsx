import React from 'react';

export const PageSettings = React.createContext();
export const settings = {
    DATE_FORMAT: 'd MMM yyyy',
    FULL_DATE_FORMAT: 'dd MMM yyyy, HH:mm aa',
    FORM_DATE_FORMAT: 'DD/MM/YYYY',
    TIME_FORMAT: 'hh:mm a',
    INVOICE_FORMAT: 'MMMM D, YYYY',
    SEARCH_DATE_FORMAT: 'DD-MMM-YYYY',
};

export const AppName = 'H.M. Amin Group';
export const AppSubName = import.meta.env.VITE_APP_NAME || 'HR Fabrics';

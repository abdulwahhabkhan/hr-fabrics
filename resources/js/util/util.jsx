import React from 'react';
import { Slide, toast } from 'react-toastify';
import { CustomNotification } from '@/components/FlashMessage.jsx';
import { usePage } from '@/util/Inertia';

export const UNIT_BOX = 'Box';
export const UNIT_SUIT = 'Suit';
export const UNIT_THAAN = 'Thaan';

export const usePackingUnits = () => {
    const { packingUnits } = usePage().props;
    return packingUnits ?? [UNIT_BOX, UNIT_SUIT, UNIT_THAAN];
}
export const useAccountTypes = () => {
    const { accountTypes } = usePage().props;
    return accountTypes;
}

export const STATUS_OPEN = 'Open'
export const STATUS_CLOSE = 'Close'
export const STATUS_CANCEL = 'Cancel'
export const ORDER_OPEN = 0
export const ORDER_CLOSED = 1
export const ORDER_CANCELLED = 2
export const TYPE_CP = 'Fresh'
export const TYPE_CP_F36 = 'Fresh 36 Inch'
export const TYPE_CP34 = '3/4'

export const UNITS = [
    UNIT_BOX,
    UNIT_SUIT,
    UNIT_THAAN
]

export const notifyMessage = ({title, type, message, timeout}) => {
    let tType = type ?? 'default'
    if (tType === 'danger') {
        tType = 'error'
    }
    toast(CustomNotification, {
        data: {title: title, content: message},
        autoClose: timeout ?? 2000,
        type: tType,
        transition: Slide,
    });
}

export const formAlert = (response) => {
    const {status, data} = {...response}
    let message = "Server Side Message";
    let notificationContent = null;
    let type = 'danger'

    if (status == 422) {
        notificationContent = (
            <div className="widget widget-stats bg-gradient-danger mb-10px w-100">
                <div className="widget-list-item">
                    <div className="widget-list-content">
                        <h4 className="widget-list-title">{data.message}</h4>
                        {
                            JSON.stringify(data.errors)
                        }

                    </div>
                </div>
            </div>
        );
        type = 'custom'
    }
    toast(message, {
        autoClose: 5000,
        type: 'error',
        transition: Slide,
    });
}

export const serverSideError = (error) => {
    const {status, data} = error.response
    let message = "Server Side Message";
    let type = 'danger'
    if (status >= 500) {
        message = "Something wrong, please check with support team";
    } else if (status === 422) {
        message = data.message;
        type = 'warning';
    } else {
        message = "Something wrong, please check with support team";
    }
    toast(message, {
        autoClose: 5000,
        type: type,
        transition: Slide,
    });
    console.error(status, data, error.response)
}

/**
 *
 * @param unit
 * @param size
 * @param qty
 * @returns {string}
 */
export const getUnit = (unit, size, qty) => {
    if (unit === UNIT_BOX)
        return unit + ' ' + size + 'm'
    else
        return qty + '*' + unit + ' ' + size + 'm'
}
/**
 *
 * @param unit
 * @param size
 * @param qty
 * @returns {string}
 */
export const getReturnUnit = (unit, size, qty) => {
    if (unit === UNIT_BOX)
        return qty + '*' + unit + ' ' + size + 'm'
    else
        return qty + '*' + unit + ' ' + size + 'm'
}
/**
 *
 * @param unit
 * @param size
 * @param qty
 * @returns {number}
 */
export const getTotalQty = (unit, size, qty) => {
    if (unit === UNIT_BOX)
        return parseFloat(size) * parseFloat(qty)
    else
        return parseFloat(size) * parseFloat(qty)
}

/**
 *
 * @param unit
 * @param size
 * @param qty
 * @returns {string}
 */
export const getPOUnit = (unit, size, qty) => {
    if (unit.toLowerCase() === UNIT_BOX)
        return unit + ' ' + (size > 0 ? size + 'm' : '')
    else
        return unit + ' ' + (size > 0 ? size + 'm' : '')
}

/**
 *
 * @param unit
 * @param size
 * @returns {string}
 */
export const getSOUnit = (unit, size) => {
    if (unit.toLowerCase() === UNIT_BOX)
        return unit + ' ' + (size > 0 ? size + 'm' : '')
    else
        return unit + ' ' + (size > 0 ? size + 'm' : '')
}


/**
 *
 * @returns {string}
 * @param rate
 * @param value
 */
export const getSOCommission = (rate, value) => {

    return value + ' ' + (rate > 0 ? '@' + rate : '')

}

export const getOrderStatus = (status) => {
    if (status === ORDER_CLOSED)
        return 'Close'
    else if (status === ORDER_OPEN)
        return 'Open'
    else if (status === ORDER_CANCELLED)
        return 'Cancel'
}

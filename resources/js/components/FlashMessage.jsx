import React, { useEffect } from 'react'; //import 'react-notifications-component/dist/theme.css';
import { usePage } from '@/util/Inertia';
import { ToastContainer } from 'react-toastify';
import { notifyMessage } from '@/util/util.jsx';
import cx from 'classnames';

export function CustomNotification({ closeToast, data, toastProps }) {
    const isColored = toastProps.theme === 'colored';

    return (
        <div className="flex flex-col w-full">
            <div
                className={cx(
                    'fw-600',
                    isColored ? 'text-white' : 'text-zinc-800',
                )}
            >
                {data.title}
            </div>
            <div className="flex items-center justify-between">
                <p className="mb-0">{data.content}</p>
            </div>
        </div>
    );
}

export default () => {
    const { flash } = usePage().props;

    useEffect(() => {
        Notify();
    }, [flash]);

    const Notify = () => {
        let title = '';
        let message = '';
        let type = '';
        if (flash.success) {
            title = 'Success';
            type = 'success';
            message = flash.success;
        } else if (flash.error) {
            title = 'Error';
            type = 'danger';
            message = flash.error;
        } else return false;
        notifyMessage({
            title: title,
            message: message,
            type: type,
        });
        /*Store.addNotification({
            title: title,
            message: message,
            type: type,
            insert: "top",
            container: 'bottom-right',
            animationIn: ["animated", "fadeIn"],
            animationOut: ["animated", "fadeOut"],
            dismiss: {duration: 2000},
            dismissable: {click: true},
            //content: notificationContent
        });*/
    };
    return (
        <>
            <ToastContainer
                theme="colored"
                position="bottom-right"
                pauseOnFocusLoss
                stacked
                hideProgressBar={false}
            />
        </>
    );
};

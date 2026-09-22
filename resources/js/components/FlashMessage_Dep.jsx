import React, {useEffect} from "react";

import 'react-notifications-component/dist/theme.css';
import {usePage} from "@/util/Inertia";
import {ReactNotifications, Store} from "react-notifications-component";


export default () => {
    const {flash} = usePage().props;

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
        } else
            return false

        Store.addNotification({
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
        });
    }
    return (
        <>
            <ReactNotifications/>
        </>
    )
}

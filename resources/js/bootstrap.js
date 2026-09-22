/* eslint eqeqeq: "off", curly: "error" */

import axios from 'axios';
import _ from 'lodash';

if (typeof window !== 'undefined') {
    window._ = _;

    /**
     * We'll load the axios HTTP library which allows us to easily issue requests
     * to our Laravel back-end. This library automatically handles sending the
     * CSRF token as a header based on the value of the "XSRF" token cookie.
     */

    window.axios = axios;

    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    window.axios.interceptors.response.use(
        (response) => response,
        (error) => {
            if (error?.response?.status === 423) {
                if (typeof route === 'function') {
                    window.location.href = route('password.confirm');
                } else {
                    window.location.href = '/user/confirm-password';
                }
            }

            return Promise.reject(error);
        },
    );
}

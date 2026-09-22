import React from 'react';
import { usePage } from '@/util/Inertia.jsx';
import loginBg from '@/img/bg/auth-bg.webp';
import logo from '@/img/logo-2.png';

export default function AuthLayout({ children, title, description }) {
    const { appName, store } = usePage().props;
    return (
        <div className="login login-with-news-feed">
            <div className="news-feed">
                <div
                    className="news-image"
                    style={{ backgroundImage: `url(${loginBg})` }}
                ></div>

                <div className="news-caption">
                    <a href="/">
                        <img
                            src={logo}
                            className="z-3 w-70px top-0 pb-20px"
                            alt={appName || 'Logo'}
                        />
                    </a>

                    <h4 className="caption-title">{appName}</h4>
                    {store?.branch_name && <p>{store.branch_name}</p>}
                </div>
            </div>
            <div className="login-container">
                <div className="my-auto w-100">
                    <div className="d-block d-md-none text-center mb-4">
                        <a href="/" className="d-inline-block mb-2">
                            <img
                                src={logo}
                                className="w-60px"
                                alt={appName || 'Logo'}
                            />
                        </a>
                        {appName && <h3 className="mb-1">{appName}</h3>}
                        {store?.branch_name && (
                            <p className="text-muted small mb-0">
                                {store.branch_name}
                            </p>
                        )}
                    </div>

                    <div className="login-header mb-4">
                        <div className="w-100 text-center text-md-start">
                            {title && <h1 className="h3 mb-2">{title}</h1>}
                            {description && (
                                <p className="text-muted mb-0">{description}</p>
                            )}
                        </div>
                    </div>
                    <div className="login-content">{children}</div>
                </div>
            </div>
        </div>
    );
}

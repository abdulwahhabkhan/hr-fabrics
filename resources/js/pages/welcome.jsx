import React from "react";
import homeBg from "@/img/bg/bg-home.jpg";
import logo from "@/img/logo-2.png";
import LoginLink from "@/components/LoginLink/LoginLink.jsx";
import { Head, usePage } from "@/util/Inertia.jsx";
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth, appName, environment } = usePage().props;
    return (
        <>
            <Head title="Weclome" />
            <div id="header" className="app-header navbar navbar-expand-lg p-0">
                <div className="container-xl px-3 px-lg-5 d-flex align-items-center flex-1">
                    <div className="navbar-brand">
                        <img src={logo} alt={appName} style={{ maxWidth: "45px", marginRight: "20px" }} />
                        <span className="brand-text">{appName}</span>
                    </div>

                    <div className="" id="header-navbar">
                        <ul className="nav navbar-nav navbar-right">
                            <li className="nav-item">
                                {auth.user ? (
                                    <a href={dashboard().url} className="nav-link">
                                        Dashboard
                                    </a>
                                ) : (
                                    <a href={login().url} className="nav-link">
                                        Log in
                                    </a>
                                )}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div id="home" className="py-5 position-relative bg-black bg-size-cover" data-bs-theme="dark">
                <div className="container-xxl p-3 p-lg-5">
                    <div className="div-hero-content z-3 position-relative">
                        {environment === "local" && (
                            <LoginLink
                                label="Login as Admin"
                                className="pb-3 text-red-500 btn btn-primary"
                                keyId={"1"}
                                redirectUrl={dashboard().url}
                            />
                        )}
                    </div>
                </div>
            </div>
            <div id="page-container">
                <div id="home" className="content has-bg home" style={{ height: "100vh" }}>
                    <div className="content-bg" style={{ backgroundImage: `url(${homeBg})` }} />
                    <div className="container home-content">
                        <h1>Welcome to {appName}</h1>
                        <h3>Explore your true style.</h3>
                    </div>
                </div>
            </div>
        </>
    );
}
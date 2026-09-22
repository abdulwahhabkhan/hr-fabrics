import React from "react";
import { router } from "@/util/Inertia.jsx";

export default function LoginLink({
    className = "underline",
    email = null,
    guard = null,
    keyId = null,
    label = "Login",
    redirectUrl = null,
    userAttributes = null,
}) {
    function submit(event) {
        event.preventDefault();
        router.post(route("loginLinkLogin"), {
            email: email,
            key: keyId,
            redirect_url: redirectUrl,
            guard: guard,
            user_attributes: userAttributes,
        });
    }

    return (
        <form onSubmit={submit} className="d-grid">
            <button className={className} type="submit">
                {label}
            </button>
        </form>
    );
}

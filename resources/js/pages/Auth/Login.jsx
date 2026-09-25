import React, { useEffect } from 'react';
import ValidationErrors from '@/components/ValidationErrors';
import { Head, Link, setLayoutProps, useForm, usePage } from '@/util/Inertia';
import { Button, FloatingLabel, Form } from 'react-bootstrap';
import LoginLink from '@/components/LoginLink/LoginLink.jsx';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { dashboard, login } from '@/routes';
import { request as passwordRequest } from '@/routes/password';

export default function Login() {
    const { status, canResetPassword, environment } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });
    const {
        verify,
        isLoading: passkeyLoading,
        error: passkeyError,
        isSupported: passkeySupported,
    } = usePasskeyVerify({
        onSuccess: (response) => {
            window.location.href = response.redirect || '/';
        },
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const onHandleChange = (event) => {
        setData(
            event.target.name,
            event.target.type === 'checkbox'
                ? event.target.checked
                : event.target.value,
        );
    };

    const submit = (e) => {
        e.preventDefault();

        post(login().url);
    };

    setLayoutProps({
        title: 'Sign In',
        description: 'For your protection, please verify your identity.',
    });

    return (
        <>
            <Head title="Login" />
            {status && (
                <div className="alert alert-success mb-3" role="alert">
                    {status}
                </div>
            )}

            <div>
                <ValidationErrors errors={errors} />
                <form className="margin-bottom-0" onSubmit={submit}>
                    <FloatingLabel
                        controlId="email"
                        label="Email address"
                        className="mb-3"
                    >
                        <Form.Control
                            name="email"
                            type="email"
                            placeholder="name@example.com"
                            value={data.email}
                            className="form-control form-control-lg"
                            autoComplete="username"
                            autoFocus
                            disabled={processing}
                            isInvalid={Boolean(errors.email)}
                            onChange={onHandleChange}
                        />
                    </FloatingLabel>
                    <FloatingLabel
                        controlId="password"
                        label="Password"
                        className="mb-3"
                    >
                        <Form.Control
                            name="password"
                            placeholder="Password"
                            value={data.password}
                            className="form-control form-control-lg"
                            type="password"
                            autoComplete="current-password"
                            disabled={processing}
                            isInvalid={Boolean(errors.password)}
                            onChange={onHandleChange}
                        />
                    </FloatingLabel>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <Form.Check
                            id="remember"
                            type="checkbox"
                            name="remember"
                            label="Remember me"
                            checked={Boolean(data.remember)}
                            disabled={processing}
                            onChange={onHandleChange}
                        />
                        {canResetPassword && (
                            <Link
                                href={passwordRequest()}
                                className="text-decoration-none small text-muted hover-underline"
                            >
                                Forgot password?
                            </Link>
                        )}
                    </div>
                    <Form.Group className="mb-3">
                        <div className="d-grid">
                            <Button
                                type="submit"
                                size="lg"
                                variant="primary"
                                disabled={processing}
                            >
                                {processing ? 'Signing in…' : 'Sign me in'}
                            </Button>
                        </div>
                    </Form.Group>
                </form>
                {passkeySupported && (
                    <div className="d-grid mb-3">
                        <Button
                            type="button"
                            size="lg"
                            variant="outline-secondary"
                            onClick={verify}
                            disabled={passkeyLoading || processing}
                        >
                            {passkeyLoading
                                ? 'Verifying…'
                                : 'Sign in with a passkey'}
                        </Button>
                        {passkeyError && (
                            <div className="text-danger small mt-2">
                                {passkeyError}
                            </div>
                        )}
                    </div>
                )}
                {environment === 'local' && (
                    <div className="d-grid mt-2">
                        <LoginLink
                            label="Login as Admin"
                            className="btn btn-warning w-100"
                            keyId="1"
                            redirectUrl={dashboard().url}
                        />
                    </div>
                )}
            </div>
        </>
    );
}

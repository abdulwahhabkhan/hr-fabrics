import React from 'react';
import ValidationErrors from '@/components/ValidationErrors';
import { Head, Link, setLayoutProps, useForm } from '@/util/Inertia';
import { Button, FloatingLabel, Form } from 'react-bootstrap';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status, appName }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const submit = (e) => {
        e.preventDefault();

        post(email().url);
    };
    setLayoutProps({
        title: 'Forgot Password',
        description: 'Enter your email to receive a password reset link.',
    });
    return (
        <>
            <Head title="Forgot Password" />
            <div>
                <ValidationErrors errors={errors} />
                <div>
                    {status && (
                        <div className="alert alert-success mb-3" role="alert">
                            {status}
                        </div>
                    )}
                    <form onSubmit={submit}>
                        <FloatingLabel
                            controlId="email"
                            label="Email address"
                            className="mb-3"
                        >
                            <Form.Control
                                type="email"
                                name="email"
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
                        <Form.Group className="mb-3">
                            <div className="d-grid">
                                <Button
                                    type="submit"
                                    size="lg"
                                    variant="primary"
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Sending Link…'
                                        : 'Email Password Reset Link'}
                                </Button>
                            </div>
                        </Form.Group>
                    </form>
                    <div className="text-center mt-3">
                        <Link
                            href={login()}
                            className="text-decoration-none small text-muted"
                        >
                            Back to Sign In
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}

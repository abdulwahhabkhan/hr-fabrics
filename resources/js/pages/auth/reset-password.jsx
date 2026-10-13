import React, { useEffect } from 'react';
import ValidationErrors from '@/components/ValidationErrors';
import { Head, setLayoutProps, useForm } from '@/util/Inertia';
import { Button, FloatingLabel, Form } from 'react-bootstrap';
import { update as passwordUpdate } from '@/routes/password';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        return () => {
            reset('password', 'password_confirmation');
        };
    }, []);

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const submit = (e) => {
        e.preventDefault();

        post(passwordUpdate().url);
    };
    setLayoutProps({
        title: 'Reset Password',
        description:
            'Create a strong new password to secure your account. Make sure it’s something only you can remember.',
    });
    return (
        <>
            <Head title="Reset Password" />

            <div>
                <ValidationErrors errors={errors} />
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
                            type="password"
                            name="password"
                            placeholder="Password"
                            value={data.password}
                            className="form-control form-control-lg"
                            autoComplete="new-password"
                            autoFocus
                            disabled={processing}
                            isInvalid={Boolean(errors.password)}
                            onChange={onHandleChange}
                        />
                    </FloatingLabel>

                    <FloatingLabel
                        controlId="password_confirmation"
                        label="Confirm Password"
                        className="mb-3"
                    >
                        <Form.Control
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirm Password"
                            value={data.password_confirmation}
                            className="form-control form-control-lg"
                            autoComplete="new-password"
                            disabled={processing}
                            isInvalid={Boolean(errors.password_confirmation)}
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
                                    ? 'Resetting Password…'
                                    : 'Reset Password'}
                            </Button>
                        </div>
                    </Form.Group>
                </form>
            </div>
        </>
    );
}

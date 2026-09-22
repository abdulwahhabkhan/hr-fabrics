import React, { useEffect } from 'react';
import ValidationErrors from '@/components/ValidationErrors';
import { Head, setLayoutProps, useForm } from '@/util/Inertia';
import { Button, FloatingLabel, Form } from 'react-bootstrap';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const submit = (e) => {
        e.preventDefault();

        post(route('password.confirm'));
    };
    setLayoutProps({
        title: 'Confirm Password',
        description:
            'This is a secure area of the application. Please confirm your password before continuing.',
    });
    return (
        <>
            <Head title="Confirm Password" />

            <div>
                <ValidationErrors errors={errors} />
                <form onSubmit={submit}>
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
                            autoComplete="current-password"
                            autoFocus
                            disabled={processing}
                            isInvalid={Boolean(errors.password)}
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
                                {processing ? 'Confirming…' : 'Confirm'}
                            </Button>
                        </div>
                    </Form.Group>
                </form>
            </div>
        </>
    );
}

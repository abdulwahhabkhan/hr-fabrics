import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import {
    Panel,
    PanelBody,
    PanelFooter,
    PanelHeader,
} from '@/components/panel/panel';
import { Head, Inertia, InertiaLink, useHttp, usePage } from '@/util/Inertia';
import { Alert, Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import { confirm as passwordConfirm } from '@/routes/password';
import profileRoutes from '@/routes/profile';
import passkey from '@/routes/passkey';
import twoFactor from '@/routes/two-factor';

const isPasswordConfirmException = (httpResponse, onRequired) => {
    if (httpResponse.status === 423) {
        onRequired();

        return true;
    }

    return false;
};

const TwoFactorPanel = ({ initialEnabled }) => {
    const [enabled, setEnabled] = useState(initialEnabled);
    const [setupOpen, setSetupOpen] = useState(false);
    const [qr, setQr] = useState(null);
    const [recoveryCodes, setRecoveryCodes] = useState(null);
    const [needsPasswordConfirm, setNeedsPasswordConfirm] = useState(false);
    const http = useHttp({ code: '' });

    const requirePasswordConfirm = () => setNeedsPasswordConfirm(true);

    const loadRecoveryCodes = () => {
        http.get(twoFactor.recoveryCodes().url, {
            onSuccess: (response) => setRecoveryCodes(response),
            onHttpException: (r) =>
                isPasswordConfirmException(r, requirePasswordConfirm),
        });
    };

    const startSetup = () => {
        http.post(twoFactor.enable().url, {
            onSuccess: () => {
                http.get(twoFactor.qrCode().url, {
                    onSuccess: (response) => {
                        setQr(response);
                        setSetupOpen(true);
                    },
                    onHttpException: (r) =>
                        isPasswordConfirmException(r, requirePasswordConfirm),
                });
            },
            onHttpException: (r) =>
                isPasswordConfirmException(r, requirePasswordConfirm),
        });
    };

    const confirm = (e) => {
        e.preventDefault();

        http.post(twoFactor.confirm().url, {
            onSuccess: () => {
                setSetupOpen(false);
                setEnabled(true);
                setQr(null);
                http.setData('code', '');
                loadRecoveryCodes();
            },
            onHttpException: (r) =>
                isPasswordConfirmException(r, requirePasswordConfirm),
        });
    };

    const regenerateRecoveryCodes = () => {
        http.post(twoFactor.regenerateRecoveryCodes().url, {
            onSuccess: loadRecoveryCodes,
            onHttpException: (r) =>
                isPasswordConfirmException(r, requirePasswordConfirm),
        });
    };

    const disable = () => {
        http.delete(twoFactor.disable().url, {
            onSuccess: () => {
                setEnabled(false);
                setSetupOpen(false);
                setQr(null);
                setRecoveryCodes(null);
            },
            onHttpException: (r) =>
                isPasswordConfirmException(r, requirePasswordConfirm),
        });
    };

    return (
        <Panel theme="default">
            <PanelHeader heading={'Two-Factor Authentication'} />
            <PanelBody>
                {needsPasswordConfirm && (
                    <Alert variant="warning">
                        Please{' '}
                        <InertiaLink href={passwordConfirm()}>
                            confirm your password
                        </InertiaLink>{' '}
                        to manage two-factor authentication.
                    </Alert>
                )}

                {!enabled && !setupOpen && (
                    <>
                        <p className="text-muted mb-3">
                            Two-factor authentication is currently disabled.
                        </p>
                        <LoadingButton
                            variant="primary"
                            processing={http.processing}
                            onClick={startSetup}
                        >
                            Enable Two-Factor Authentication
                        </LoadingButton>
                    </>
                )}

                {setupOpen && qr && (
                    <form onSubmit={confirm}>
                        <p className="text-muted">
                            Scan this QR code with your authenticator app, then
                            enter the code it generates below.
                        </p>
                        <div
                            className="mb-3"
                            dangerouslySetInnerHTML={{ __html: qr.svg }}
                        />
                        <Row>
                            <Col lg={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>
                                        Authentication Code:
                                    </Form.Label>
                                    <Form.Control
                                        size="sm"
                                        isInvalid={!!http.errors.code}
                                        value={http.data.code}
                                        onChange={(e) =>
                                            http.setData('code', e.target.value)
                                        }
                                        placeholder="code"
                                    />
                                    {http.errors.code && (
                                        <div className="invalid-feedback d-block">
                                            {http.errors.code}
                                        </div>
                                    )}
                                </Form.Group>
                            </Col>
                        </Row>
                        <LoadingButton
                            type="submit"
                            processing={http.processing}
                        >
                            Confirm
                        </LoadingButton>
                    </form>
                )}

                {enabled && !setupOpen && (
                    <>
                        <p className="text-success mb-3">
                            Two-factor authentication is enabled.
                        </p>

                        {recoveryCodes ? (
                            <div className="mb-3">
                                <p className="text-muted mb-2">
                                    Store these recovery codes somewhere safe.
                                    Each can be used once to sign in if you lose
                                    access to your authenticator.
                                </p>
                                <ul className="font-monospace">
                                    {recoveryCodes.map((code) => (
                                        <li key={code}>{code}</li>
                                    ))}
                                </ul>
                                <LoadingButton
                                    variant="white"
                                    size="sm"
                                    className="me-2"
                                    processing={http.processing}
                                    onClick={regenerateRecoveryCodes}
                                >
                                    Regenerate Recovery Codes
                                </LoadingButton>
                            </div>
                        ) : (
                            <LoadingButton
                                variant="white"
                                size="sm"
                                className="me-2"
                                processing={http.processing}
                                onClick={loadRecoveryCodes}
                            >
                                View Recovery Codes
                            </LoadingButton>
                        )}

                        <LoadingButton
                            variant="danger"
                            size="sm"
                            processing={http.processing}
                            onClick={disable}
                        >
                            Disable Two-Factor Authentication
                        </LoadingButton>
                    </>
                )}
            </PanelBody>
        </Panel>
    );
};

const PasskeysPanel = ({ initialPasskeys }) => {
    const [passkeys, setPasskeys] = useState(initialPasskeys);
    const [name, setName] = useState('');
    const [needsPasswordConfirm, setNeedsPasswordConfirm] = useState(false);
    const deleteHttp = useHttp({});
    const {
        register,
        isLoading: registering,
        error: registerError,
        isSupported: canAddPasskey,
    } = usePasskeyRegister({
        onSuccess: () => {
            setName('');
            Inertia.reload({ only: ['passkeys'] });
        },
    });

    const removePasskey = (id) => {
        deleteHttp.delete(passkey.destroy(id).url, {
            onSuccess: () =>
                setPasskeys((list) =>
                    list.filter((passkey) => passkey.id !== id),
                ),
            onHttpException: (r) =>
                isPasswordConfirmException(r, () =>
                    setNeedsPasswordConfirm(true),
                ),
        });
    };

    return (
        <Panel theme="default">
            <PanelHeader heading={'Passkeys'} />
            <PanelBody>
                {needsPasswordConfirm && (
                    <Alert variant="warning">
                        Please{' '}
                        <InertiaLink href={passwordConfirm()}>
                            confirm your password
                        </InertiaLink>{' '}
                        to manage passkeys.
                    </Alert>
                )}

                {passkeys.length > 0 ? (
                    <ul className="list-unstyled mb-3">
                        {passkeys.map((passkey) => (
                            <li
                                key={passkey.id}
                                className="d-flex align-items-center justify-content-between mb-2"
                            >
                                <span>
                                    {passkey.name}
                                    <span className="text-muted ms-2 small">
                                        {passkey.last_used_at
                                            ? `last used ${passkey.last_used_at}`
                                            : 'never used'}
                                    </span>
                                </span>
                                <LoadingButton
                                    variant="danger"
                                    size="sm"
                                    processing={deleteHttp.processing}
                                    onClick={() => removePasskey(passkey.id)}
                                >
                                    Remove
                                </LoadingButton>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-muted mb-3">
                        No passkeys registered yet.
                    </p>
                )}

                {canAddPasskey ? (
                    <>
                        <Row className="align-items-end">
                            <Col lg={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Passkey Name:</Form.Label>
                                    <Form.Control
                                        size="sm"
                                        value={name}
                                        onChange={(e) =>
                                            setName(e.target.value)
                                        }
                                        placeholder="e.g. MacBook Pro"
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={6}>
                                <LoadingButton
                                    variant="primary"
                                    size="sm"
                                    className="mb-3"
                                    disabled={!name}
                                    processing={registering}
                                    onClick={() => register(name)}
                                >
                                    Add a Passkey
                                </LoadingButton>
                            </Col>
                        </Row>
                        {registerError && (
                            <div className="text-danger small">
                                {registerError}
                            </div>
                        )}
                    </>
                ) : (
                    <p className="text-muted small">
                        Passkeys are not supported in this browser.
                    </p>
                )}
            </PanelBody>
        </Panel>
    );
};

const ProfileIndex = () => {
    const { user, role, twoFactorEnabled, passkeys } = usePage().props;
    const {
        register,
        handleSubmit,
        getValues,
        setError,
        formState: { errors },
    } = useForm();
    const [processing, setProcessing] = useState(false);
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        const post_data = { ...data };
        setProcessing(true);
        Inertia.put(profileRoutes.password(), post_data, options);
    };

    return (
        <>
            <Head title="Profile" />
            <PageHeader title="Profile" />
            <PageContent>
                <Panel theme="default">
                    <PanelHeader>Profile Info</PanelHeader>
                    <PanelBody>
                        <Row>
                            <Col lg={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Name:</Form.Label>
                                    <Form.Control
                                        value={user.name}
                                        readOnly={true}
                                        size={'sm'}
                                        placeholder={''}
                                    />
                                </Form.Group>
                            </Col>
                            <Col lg={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Email:</Form.Label>
                                    <Form.Control
                                        value={user.email}
                                        readOnly={true}
                                        size={'sm'}
                                        placeholder={''}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>
                <Panel theme="default">
                    <PanelHeader>Change Password</PanelHeader>
                    <PanelBody>
                        <form
                            action=""
                            className=""
                            onSubmit={handleSubmit(sendRequest)}
                        >
                            <Row>
                                <Col lg={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Password:</Form.Label>
                                        <Form.Control
                                            size={'sm'}
                                            type={'password'}
                                            isInvalid={errors.password}
                                            {...register('password', {
                                                required:
                                                    'Password is required!',
                                            })}
                                            placeholder={'password'}
                                        />
                                        {errors.password && (
                                            <div className="invalid-feedback">
                                                {errors.password.message}
                                            </div>
                                        )}
                                    </Form.Group>
                                </Col>
                                <Col lg={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Confirm Password:
                                        </Form.Label>
                                        <Form.Control
                                            size={'sm'}
                                            type={'password'}
                                            isInvalid={errors.confirm_password}
                                            {...register('confirm_password', {
                                                required:
                                                    'Please confirm password!',
                                                validate: {
                                                    matchesPreviousPassword: (
                                                        value,
                                                    ) => {
                                                        const { password } =
                                                            getValues();
                                                        return (
                                                            password ===
                                                                value ||
                                                            'Passwords should match!'
                                                        );
                                                    },
                                                },
                                            })}
                                            placeholder={'confirm password'}
                                        />
                                        {errors.confirm_password && (
                                            <div className="invalid-feedback">
                                                {
                                                    errors.confirm_password
                                                        .message
                                                }
                                            </div>
                                        )}
                                    </Form.Group>
                                </Col>
                            </Row>
                        </form>
                    </PanelBody>

                    <PanelFooter>
                        <LoadingButton
                            processing={processing}
                            onClick={handleSubmit(sendRequest)}
                        >
                            Update Password
                        </LoadingButton>
                    </PanelFooter>
                </Panel>

                <TwoFactorPanel initialEnabled={twoFactorEnabled} />
                <PasskeysPanel initialPasskeys={passkeys} />
            </PageContent>
        </>
    );
};

export default ProfileIndex;

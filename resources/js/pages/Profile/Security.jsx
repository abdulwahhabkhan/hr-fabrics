import React, { useRef, useState } from 'react';
import { Head, Inertia, useForm, useHttp, usePage } from '@/util/Inertia';
import { Form } from 'react-bootstrap';
import { Icon } from '@iconify/react';
import LoadingButton from '@/components/LoadingButton';
import { FormField } from '@/components/form/FormSection';
import { MomentFull } from '@/components/Moment';
import HeadingSmall from '@/components/settings/HeadingSmall';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import profile from '@/routes/profile';
import passkey from '@/routes/passkey';
import twoFactor from '@/routes/two-factor';

/**
 * Fortify answers 423 when the password confirmation has expired while the page
 * was open. Revisiting this page lets the `password.confirm` middleware send the
 * user to the confirm screen and back here afterwards.
 */
const redirectWhenPasswordConfirmRequired = (httpResponse) => {
    if (httpResponse.status === 423) {
        Inertia.visit(profile.security());

        return true;
    }

    return false;
};

const StatusBadge = ({ enabled }) => (
    <span className={`hf-pill ${enabled ? 'tone-green' : 'tone-red'} ms-2`}>
        {enabled ? 'Enabled' : 'Disabled'}
    </span>
);

const UpdatePasswordSection = () => {
    const currentPasswordInput = useRef(null);
    const passwordInput = useRef(null);
    const { data, setData, put, processing, errors, reset, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        put(profile.password().url, {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (formErrors) => {
                if (formErrors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (formErrors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    const field = (name, label, ref, autoComplete) => (
        <FormField label={label} htmlFor={name}>
            <Form.Control
                ref={ref}
                id={name}
                name={name}
                type="password"
                value={data[name]}
                autoComplete={autoComplete}
                isInvalid={Boolean(errors[name])}
                onChange={(e) => setData(name, e.target.value)}
            />
            {errors[name] && <div className="invalid-feedback d-block">{errors[name]}</div>}
        </FormField>
    );

    return (
        <section className="hf-settings-section">
            <HeadingSmall
                title="Update password"
                description="Ensure your account is using a long, random password to stay secure."
            />

            <form onSubmit={submit} className="hf-settings-fields">
                {field('current_password', 'Current password', currentPasswordInput, 'current-password')}
                {field('password', 'New password', passwordInput, 'new-password')}
                {field('password_confirmation', 'Confirm password', null, 'new-password')}

                <div className="hf-settings-actions">
                    <LoadingButton type="submit" variant="theme" processing={processing}>
                        Save password
                    </LoadingButton>
                    {recentlySuccessful && <span className="hf-settings-saved">Saved.</span>}
                </div>
            </form>
        </section>
    );
};

const TwoFactorSection = ({ initialEnabled }) => {
    const [enabled, setEnabled] = useState(initialEnabled);
    const [setupOpen, setSetupOpen] = useState(false);
    const [qr, setQr] = useState(null);
    const [recoveryCodes, setRecoveryCodes] = useState(null);
    const http = useHttp({ code: '' });

    const loadRecoveryCodes = () => {
        http.get(twoFactor.recoveryCodes().url, {
            onSuccess: (response) => setRecoveryCodes(response),
            onHttpException: redirectWhenPasswordConfirmRequired,
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
                    onHttpException: redirectWhenPasswordConfirmRequired,
                });
            },
            onHttpException: redirectWhenPasswordConfirmRequired,
        });
    };

    const cancelSetup = () => {
        setSetupOpen(false);
        setQr(null);
        http.setData('code', '');
    };

    const confirm = (e) => {
        e.preventDefault();

        http.post(twoFactor.confirm().url, {
            onSuccess: () => {
                cancelSetup();
                setEnabled(true);
                loadRecoveryCodes();
            },
            onHttpException: redirectWhenPasswordConfirmRequired,
        });
    };

    const regenerateRecoveryCodes = () => {
        http.post(twoFactor.regenerateRecoveryCodes().url, {
            onSuccess: loadRecoveryCodes,
            onHttpException: redirectWhenPasswordConfirmRequired,
        });
    };

    const disable = () => {
        http.delete(twoFactor.disable().url, {
            onSuccess: () => {
                cancelSetup();
                setEnabled(false);
                setRecoveryCodes(null);
            },
            onHttpException: redirectWhenPasswordConfirmRequired,
        });
    };

    return (
        <section className="hf-settings-section">
            <HeadingSmall
                title="Two-factor authentication"
                description="Manage your two-factor authentication settings."
                badge={<StatusBadge enabled={enabled} />}
            />

            {!enabled && !setupOpen && (
                <div className="hf-settings-fields">
                    <p className="hf-settings-text">
                        When you enable two-factor authentication, you will be prompted for a secure
                        code during login. This code can be retrieved from a TOTP-supported
                        application on your phone.
                    </p>
                    <div className="hf-settings-actions">
                        <LoadingButton variant="theme" processing={http.processing} onClick={startSetup}>
                            Enable 2FA
                        </LoadingButton>
                    </div>
                </div>
            )}

            {setupOpen && qr && (
                <form onSubmit={confirm} className="hf-settings-fields">
                    <p className="hf-settings-text">
                        Scan this QR code with your authenticator app, then enter the 6-digit code it
                        generates.
                    </p>
                    <div className="hf-settings-qr" dangerouslySetInnerHTML={{ __html: qr.svg }} />
                    <FormField label="Authentication code" htmlFor="code">
                        <Form.Control
                            id="code"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            maxLength={6}
                            autoFocus
                            isInvalid={Boolean(http.errors.code)}
                            value={http.data.code}
                            onChange={(e) => http.setData('code', e.target.value)}
                        />
                        {http.errors.code && <div className="invalid-feedback d-block">{http.errors.code}</div>}
                    </FormField>
                    <div className="hf-settings-actions">
                        <button type="button" className="btn btn-white" onClick={cancelSetup}>
                            Cancel
                        </button>
                        <LoadingButton type="submit" variant="theme" processing={http.processing}>
                            Confirm
                        </LoadingButton>
                    </div>
                </form>
            )}

            {enabled && (
                <div className="hf-settings-fields">
                    <p className="hf-settings-text">
                        With two-factor authentication enabled, you will be prompted for a secure,
                        random code during login, which you can retrieve from your TOTP-supported
                        application.
                    </p>

                    <div className="hf-settings-card">
                        <div className="hf-settings-card__head">
                            <Icon icon="solar:lock-keyhole-bold-duotone" />
                            <div>
                                <div className="hf-settings-card__title">2FA recovery codes</div>
                                <div className="hf-settings-card__desc">
                                    Recovery codes let you regain access if you lose your 2FA device.
                                    Store them in a secure password manager.
                                </div>
                            </div>
                        </div>

                        {recoveryCodes && (
                            <div className="hf-settings-codes">
                                {recoveryCodes.map((code) => (
                                    <code key={code}>{code}</code>
                                ))}
                            </div>
                        )}

                        <div className="hf-settings-actions">
                            {recoveryCodes ? (
                                <>
                                    <button
                                        type="button"
                                        className="btn btn-white"
                                        onClick={() => setRecoveryCodes(null)}
                                    >
                                        Hide recovery codes
                                    </button>
                                    <LoadingButton
                                        variant="white"
                                        processing={http.processing}
                                        onClick={regenerateRecoveryCodes}
                                    >
                                        Regenerate codes
                                    </LoadingButton>
                                </>
                            ) : (
                                <LoadingButton
                                    variant="white"
                                    processing={http.processing}
                                    onClick={loadRecoveryCodes}
                                >
                                    View recovery codes
                                </LoadingButton>
                            )}
                        </div>
                    </div>

                    <div className="hf-settings-actions">
                        <LoadingButton variant="danger" processing={http.processing} onClick={disable}>
                            Disable 2FA
                        </LoadingButton>
                    </div>
                </div>
            )}
        </section>
    );
};

const PasskeysSection = ({ passkeys }) => {
    const [name, setName] = useState('');
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
            onSuccess: () => Inertia.reload({ only: ['passkeys'] }),
            onHttpException: redirectWhenPasswordConfirmRequired,
        });
    };

    const addPasskey = (e) => {
        e.preventDefault();

        if (name) {
            register(name);
        }
    };

    return (
        <section className="hf-settings-section">
            <HeadingSmall
                title="Passkeys"
                description="Sign in without a password using your device's fingerprint, face or screen lock."
            />

            <div className="hf-settings-fields">
                {passkeys.length > 0 ? (
                    <ul className="hf-settings-list">
                        {passkeys.map((item) => (
                            <li key={item.id}>
                                <Icon icon="solar:key-minimalistic-bold-duotone" />
                                <div className="hf-settings-list__meta">
                                    <div className="hf-settings-list__title">{item.name}</div>
                                    <div className="hf-settings-list__desc">
                                        {item.last_used_at ? (
                                            <>Last used <MomentFull date={new Date(item.last_used_at)} /></>
                                        ) : (
                                            'Never used'
                                        )}
                                    </div>
                                </div>
                                <LoadingButton
                                    variant="white"
                                    size="sm"
                                    className="text-danger"
                                    processing={deleteHttp.processing}
                                    onClick={() => removePasskey(item.id)}
                                >
                                    Remove
                                </LoadingButton>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="hf-settings-text">No passkeys registered yet.</p>
                )}

                {canAddPasskey ? (
                    <form onSubmit={addPasskey}>
                        <FormField label="Passkey name" htmlFor="passkey-name">
                            <Form.Control
                                id="passkey-name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="e.g. MacBook Pro"
                            />
                            {registerError && <div className="invalid-feedback d-block">{registerError}</div>}
                        </FormField>
                        <div className="hf-settings-actions mt-3">
                            <LoadingButton type="submit" variant="theme" disabled={!name} processing={registering}>
                                Add passkey
                            </LoadingButton>
                        </div>
                    </form>
                ) : (
                    <p className="hf-settings-text">Passkeys are not supported in this browser.</p>
                )}
            </div>
        </section>
    );
};

const Security = () => {
    const { twoFactorEnabled, passkeys } = usePage().props;

    return (
        <>
            <Head title="Security settings" />

            <UpdatePasswordSection />
            <TwoFactorSection initialEnabled={twoFactorEnabled} />
            <PasskeysSection passkeys={passkeys} />
        </>
    );
};

export default Security;

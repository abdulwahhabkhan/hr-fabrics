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
        <Icon icon={enabled ? 'solar:check-circle-bold' : 'solar:close-circle-bold'} />
        {enabled ? 'Enabled' : 'Disabled'}
    </span>
);

const strengthLabels = ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'];

/** Rough 0–4 score from length and character variety; only drives the visual meter. */
const passwordStrength = (password) => {
    if (!password) {
        return 0;
    }

    const varietyCount = [/[a-z]/, /[A-Z]/, /\d/, /[^A-Za-z0-9]/].filter((pattern) => pattern.test(password)).length;
    let score = Math.min(varietyCount, 3);

    if (password.length >= 12) {
        score += 1;
    }

    return password.length < 8 ? Math.min(score, 1) : score;
};

const StrengthMeter = ({ password }) => {
    if (!password) {
        return null;
    }

    const score = passwordStrength(password);

    return (
        <div className={`hf-settings-strength is-${score}`}>
            <div className="hf-settings-strength__bars">
                {[1, 2, 3, 4].map((bar) => (
                    <span key={bar} className={bar <= score ? 'is-filled' : undefined} />
                ))}
            </div>
            <span className="hf-settings-strength__label">{strengthLabels[score]}</span>
        </div>
    );
};

const PasswordInput = React.forwardRef(({ id, isInvalid, ...rest }, ref) => {
    const [visible, setVisible] = useState(false);

    return (
        <div className="hf-settings-password">
            <Form.Control ref={ref} id={id} type={visible ? 'text' : 'password'} isInvalid={isInvalid} {...rest} />
            <button
                type="button"
                className="hf-settings-password__toggle"
                onClick={() => setVisible((value) => !value)}
                aria-label={visible ? 'Hide password' : 'Show password'}
                aria-controls={id}
            >
                <Icon icon={visible ? 'solar:eye-closed-bold-duotone' : 'solar:eye-bold-duotone'} />
            </button>
        </div>
    );
});

const CopyButton = ({ text, label = 'Copy' }) => {
    const [copied, setCopied] = useState(false);

    const copy = () => {
        navigator.clipboard?.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    return (
        <button type="button" className="btn btn-white" onClick={copy}>
            <Icon icon={copied ? 'solar:check-read-bold' : 'solar:copy-bold-duotone'} className="me-1" />
            {copied ? 'Copied' : label}
        </button>
    );
};

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
            <PasswordInput
                ref={ref}
                id={name}
                name={name}
                value={data[name]}
                autoComplete={autoComplete}
                isInvalid={Boolean(errors[name])}
                onChange={(e) => setData(name, e.target.value)}
            />
            {name === 'password' && <StrengthMeter password={data.password} />}
            {errors[name] && <div className="invalid-feedback d-block">{errors[name]}</div>}
        </FormField>
    );

    return (
        <section className="hf-settings-section">
            <HeadingSmall
                icon="solar:lock-password-bold-duotone"
                title="Update password"
                description="Ensure your account is using a long, random password to stay secure."
            />

            <form onSubmit={submit} className="hf-settings-fields">
                {field('current_password', 'Current password', currentPasswordInput, 'current-password')}
                <div className="hf-settings-grid">
                    {field('password', 'New password', passwordInput, 'new-password')}
                    {field('password_confirmation', 'Confirm password', null, 'new-password')}
                </div>

                <div className="hf-settings-actions">
                    <LoadingButton type="submit" variant="theme" processing={processing}>
                        Save password
                    </LoadingButton>
                    {recentlySuccessful && (
                        <span className="hf-settings-saved">
                            <Icon icon="solar:check-circle-bold" />
                            Password updated
                        </span>
                    )}
                </div>
            </form>
        </section>
    );
};

const TwoFactorSection = ({ initialEnabled }) => {
    const [enabled, setEnabled] = useState(initialEnabled);
    const [setupOpen, setSetupOpen] = useState(false);
    const [qr, setQr] = useState(null);
    const [secretKey, setSecretKey] = useState(null);
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
                        http.get(twoFactor.secretKey().url, {
                            onSuccess: (keyResponse) => setSecretKey(keyResponse.secretKey),
                            onHttpException: redirectWhenPasswordConfirmRequired,
                        });
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
        setSecretKey(null);
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
                icon="solar:shield-check-bold-duotone"
                title="Two-factor authentication"
                description="Add an extra layer of security to your account."
                badge={<StatusBadge enabled={enabled} />}
            />

            {!enabled && !setupOpen && (
                <div className="hf-settings-fields">
                    <div className="hf-settings-callout tone-warning">
                        <Icon icon="solar:shield-warning-bold-duotone" />
                        <div>
                            <div className="hf-settings-callout__title">Your account is protected by a password only</div>
                            <div className="hf-settings-callout__desc">
                                When enabled, you will be prompted for a secure code during login. The code
                                comes from a TOTP app on your phone, such as Google Authenticator or 1Password.
                            </div>
                        </div>
                    </div>
                    <div className="hf-settings-actions">
                        <LoadingButton variant="theme" processing={http.processing} onClick={startSetup}>
                            Enable 2FA
                        </LoadingButton>
                    </div>
                </div>
            )}

            {setupOpen && qr && (
                <form onSubmit={confirm} className="hf-settings-fields">
                    <ol className="hf-settings-steps">
                        <li>
                            <div className="hf-settings-steps__title">Scan the QR code</div>
                            <div className="hf-settings-steps__desc">
                                Open your authenticator app and scan this code.
                            </div>
                            <div className="hf-settings-setup">
                                <div className="hf-settings-qr" dangerouslySetInnerHTML={{ __html: qr.svg }} />
                                {secretKey && (
                                    <div className="hf-settings-secret">
                                        <div className="hf-settings-secret__label">Can't scan? Enter this key</div>
                                        <code>{secretKey}</code>
                                        <CopyButton text={secretKey} label="Copy key" />
                                    </div>
                                )}
                            </div>
                        </li>
                        <li>
                            <div className="hf-settings-steps__title">Enter the 6-digit code</div>
                            <div className="hf-settings-steps__desc">Type the code your app generates to confirm.</div>
                            <FormField label="Authentication code" htmlFor="code">
                                <Form.Control
                                    id="code"
                                    className="hf-settings-otp"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    placeholder="000000"
                                    maxLength={6}
                                    autoFocus
                                    isInvalid={Boolean(http.errors.code)}
                                    value={http.data.code}
                                    onChange={(e) => http.setData('code', e.target.value.replace(/\D/g, ''))}
                                />
                                {http.errors.code && <div className="invalid-feedback d-block">{http.errors.code}</div>}
                            </FormField>
                        </li>
                    </ol>
                    <div className="hf-settings-actions">
                        <button type="button" className="btn btn-white" onClick={cancelSetup}>
                            Cancel
                        </button>
                        <LoadingButton
                            type="submit"
                            variant="theme"
                            disabled={http.data.code.length !== 6}
                            processing={http.processing}
                        >
                            Confirm & enable
                        </LoadingButton>
                    </div>
                </form>
            )}

            {enabled && (
                <div className="hf-settings-fields">
                    <div className="hf-settings-callout tone-success">
                        <Icon icon="solar:shield-check-bold-duotone" />
                        <div>
                            <div className="hf-settings-callout__title">Two-factor authentication is on</div>
                            <div className="hf-settings-callout__desc">
                                You will be asked for a code from your authenticator app each time you sign in.
                            </div>
                        </div>
                    </div>

                    <div className="hf-settings-card">
                        <div className="hf-settings-card__head">
                            <Icon icon="solar:key-square-2-bold-duotone" />
                            <div>
                                <div className="hf-settings-card__title">Recovery codes</div>
                                <div className="hf-settings-card__desc">
                                    Use one to sign in if you lose your 2FA device. Each code works once —
                                    store them in a secure password manager.
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
                                    <CopyButton text={recoveryCodes.join('\n')} label="Copy codes" />
                                    <LoadingButton
                                        variant="white"
                                        processing={http.processing}
                                        onClick={regenerateRecoveryCodes}
                                    >
                                        Regenerate
                                    </LoadingButton>
                                    <button
                                        type="button"
                                        className="btn btn-link text-muted ms-auto"
                                        onClick={() => setRecoveryCodes(null)}
                                    >
                                        Hide
                                    </button>
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

                    <div className="hf-settings-danger">
                        <div>
                            <div className="hf-settings-danger__title">Disable two-factor authentication</div>
                            <div className="hf-settings-danger__desc">Your account will be protected by a password only.</div>
                        </div>
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
                icon="solar:key-minimalistic-square-2-bold-duotone"
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

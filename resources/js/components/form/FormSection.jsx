import React from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';

/**
 * Two-column form section: intro (icon, title, description) on the left,
 * fields on the right. Stacks on narrow screens.
 */
export function FormSection({ icon, title, description, className, children }) {
    return (
        <section className={cx('hf-form-section', className)}>
            <header className="hf-form-section__intro">
                {icon && (
                    <span className="hf-form-section__icon">
                        <Icon icon={icon} />
                    </span>
                )}
                <div>
                    <h2 className="hf-form-section__title">{title}</h2>
                    {description && <p className="hf-form-section__desc">{description}</p>}
                </div>
            </header>
            <div className="hf-form-section__body">{children}</div>
        </section>
    );
}

/** Label + control + optional hint. */
export function FormField({ label, htmlFor, required = false, hint, className, children }) {
    return (
        <div className={cx('hf-field', className)}>
            {label && (
                <label className="form-label" htmlFor={htmlFor}>
                    {label}
                    {required && <span className="hf-required" aria-hidden="true">*</span>}
                </label>
            )}
            {children}
            {hint && <div className="hf-field-hint">{hint}</div>}
        </div>
    );
}

/** Pill-style segmented radio group, registered through react-hook-form's `register`. */
export function SegmentedControl({ name, options, register, className }) {
    return (
        <div className={cx('hf-segmented', className)} role="radiogroup">
            {options.map(({ value, label, icon }) => (
                <label key={value} className="hf-segmented__option">
                    <input type="radio" value={value} {...register(name)} />
                    <span>
                        {icon && <Icon icon={icon} />}
                        {label}
                    </span>
                </label>
            ))}
        </div>
    );
}

/**
 * Radio group rendered as selectable cards (icon, title, description), registered through react-hook-form's `register`.
 * Options may be `disabled`, e.g. a payment mode not allowed for the customer.
 */
export function OptionCards({ name, options, register, invalid = false, className }) {
    return (
        <div className={cx('hf-option-cards', { 'is-invalid': invalid }, className)} role="radiogroup">
            {options.map(({ value, label, description, icon, disabled }) => (
                <label key={value} className={cx('hf-option-card', { 'is-disabled': disabled })}>
                    <input type="radio" value={value} disabled={disabled} {...register(name)} />
                    <span className="hf-option-card__box">
                        {icon && (
                            <span className="hf-option-card__icon">
                                <Icon icon={icon} />
                            </span>
                        )}
                        <span className="hf-option-card__text">
                            <span className="hf-option-card__title">{label}</span>
                            {description && <span className="hf-option-card__desc">{description}</span>}
                        </span>
                        <Icon icon="solar:check-circle-bold" className="hf-option-card__check" />
                    </span>
                </label>
            ))}
        </div>
    );
}

/** Right-aligned footer holding the form's Cancel / Save buttons. */
export function FormActions({ hint, className, children }) {
    return (
        <div className={cx('hf-form-actions', className)}>
            {hint && <span className="hf-form-actions__hint">{hint}</span>}
            <div className="hf-form-actions__buttons">{children}</div>
        </div>
    );
}

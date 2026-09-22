import { Button, type ButtonProps as BsButtonProps, Spinner } from 'react-bootstrap';
import { type Control, type FieldValues, useFormState } from 'react-hook-form';
import { Icon } from '@iconify/react';
import classNames from 'classnames';

type ButtonSize = 'xs' | 'sm' | 'md' | 'lg'

type NativeSize = NonNullable<BsButtonProps['size']>       // 'sm' | 'lg'
type Variant = NonNullable<BsButtonProps['variant']>

const NATIVE_SIZE: Partial<Record<ButtonSize, NativeSize>> = { sm: 'sm', lg: 'lg' }
const CUSTOM_SIZE_CLASS: Partial<Record<ButtonSize, string>> = { xs: 'btn-xs' }
type SubmitButtonProps = Omit<BsButtonProps, 'size'> & {
    size?: ButtonSize
    loading?: boolean
    icon?: string
}

function SubmitButton({
                                 size = 'md',
                                 variant = 'primary' as Variant,
                                 value,
                                 loading = false,
                                 icon = 'solar:clipboard-check-bold-duotone',
                                 disabled,
                                 className,
                                 children,
                                 ...rest
                             }: SubmitButtonProps) {
    return (
        <Button
            type="submit"
            variant={variant}
            size={NATIVE_SIZE[size]}
            {...rest}
            className={classNames('position-relative', CUSTOM_SIZE_CLASS[size], className)}
            disabled={disabled || loading}
            aria-busy={loading}
        >
            {loading && (
                <span className="position-absolute top-50 start-50 translate-middle">
                    <Spinner as="span" animation="border" size="sm" role="status" aria-hidden="true" />
                </span>
            )}
            <span className={classNames('d-inline-flex align-items-center gap-1', loading && 'invisible')}>
                <Icon icon={icon} />
                {value ?? children}
            </span>
        </Button>
    )
}

type FormSubmitButtonProps<T extends FieldValues> = SubmitButtonProps & {
    control?: Control<T>
}

function FormSubmitButton<T extends FieldValues>({
                                                            control,
                                                            size = 'sm',
                                                            ...props
                                                        }: FormSubmitButtonProps<T>) {
    const { isSubmitting } = useFormState({ control })
    return <SubmitButton size={size} {...props} loading={isSubmitting} />
}

export { SubmitButton, FormSubmitButton }
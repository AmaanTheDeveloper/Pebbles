import { firstFieldError } from '../../lib/errors';

export const inputClassName = 'admin-input';

export function AdminFormField({
    label,
    name,
    type = 'text',
    value,
    onChange,
    required = false,
    hint,
    error,
    errors = {},
    children,
    className = '',
    ...props
}) {
    const fieldError = error ?? firstFieldError(errors, name);

    return (
        <div className={`space-y-1.5 ${className}`}>
            {label ? (
                <label htmlFor={name} className="admin-field-label">
                    {label}
                    {required ? <span className="text-red-600"> *</span> : null}
                </label>
            ) : null}
            {children ?? (
                <input
                    id={name}
                    name={name}
                    type={type}
                    value={value ?? ''}
                    onChange={onChange}
                    required={required}
                    className={inputClassName}
                    {...props}
                />
            )}
            {hint && !fieldError ? <p className="admin-field-hint">{hint}</p> : null}
            {fieldError ? <p className="admin-field-error">{fieldError}</p> : null}
        </div>
    );
}

export function AdminSelectField({ label, name, value, onChange, required, hint, error, errors = {}, options, className = '' }) {
    const fieldError = error ?? firstFieldError(errors, name);

    return (
        <div className={`space-y-1.5 ${className}`}>
            {label ? (
                <label htmlFor={name} className="admin-field-label">
                    {label}
                    {required ? <span className="text-red-600"> *</span> : null}
                </label>
            ) : null}
            <select
                id={name}
                name={name}
                value={value ?? ''}
                onChange={onChange}
                required={required}
                className="admin-select"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {hint && !fieldError ? <p className="admin-field-hint">{hint}</p> : null}
            {fieldError ? <p className="admin-field-error">{fieldError}</p> : null}
        </div>
    );
}

export function AdminTextareaField({ label, name, value, onChange, required, hint, error, errors = {}, rows = 4, className = '' }) {
    const fieldError = error ?? firstFieldError(errors, name);

    return (
        <div className={`space-y-1.5 ${className}`}>
            {label ? (
                <label htmlFor={name} className="admin-field-label">
                    {label}
                    {required ? <span className="text-red-600"> *</span> : null}
                </label>
            ) : null}
            <textarea
                id={name}
                name={name}
                value={value ?? ''}
                onChange={onChange}
                required={required}
                rows={rows}
                className="admin-textarea"
            />
            {hint && !fieldError ? <p className="admin-field-hint">{hint}</p> : null}
            {fieldError ? <p className="admin-field-error">{fieldError}</p> : null}
        </div>
    );
}

export function AdminCheckboxField({ label, name, checked, onChange, hint, className = '' }) {
    return (
        <label className={`flex items-start gap-2.5 text-sm ${className}`}>
            <input
                type="checkbox"
                name={name}
                checked={Boolean(checked)}
                onChange={onChange}
                className="mt-0.5 rounded border-stone-300 text-brand-green focus:ring-brand-orange/30"
            />
            <span>
                <span className="admin-field-label">{label}</span>
                {hint ? <span className="mt-0.5 block admin-field-hint">{hint}</span> : null}
            </span>
        </label>
    );
}

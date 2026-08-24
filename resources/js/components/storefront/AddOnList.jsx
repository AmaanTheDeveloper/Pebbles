function CheckIcon() {
    return (
        <svg viewBox="0 0 20 20" className="size-4" fill="currentColor" aria-hidden="true">
            <path
                fillRule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clipRule="evenodd"
            />
        </svg>
    );
}

export default function AddOnList({ groups, selectedIds, onToggle, heading, description }) {
    if (!groups?.length) {
        return null;
    }

    return (
        <section className="addon-section space-y-4">
            {heading ? (
                <div className="space-y-1">
                    <h2 className="font-display text-lg font-semibold text-brand-ink">{heading}</h2>
                    {description ? <p className="text-sm text-brand-muted">{description}</p> : null}
                </div>
            ) : null}

            <div className="space-y-5">
                {groups.map((group) => (
                    <fieldset key={group.id} className="space-y-3">
                        <legend className="text-sm font-semibold text-brand-ink">
                            {group.name}
                            {group.is_required ? (
                                <span className="ml-2 text-xs font-normal text-brand-orange">Required</span>
                            ) : null}
                        </legend>
                        {group.description ? (
                            <p className="text-xs text-brand-muted">{group.description}</p>
                        ) : null}
                        <div className="grid gap-2 sm:grid-cols-2">
                            {(group.add_ons ?? []).map((addOn) => {
                                const checked = selectedIds.includes(addOn.id);
                                const inputType = group.max_selections === 1 ? 'radio' : 'checkbox';

                                return (
                                    <label
                                        key={addOn.id}
                                        className={[
                                            'addon-option group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border px-3 py-3 text-sm transition',
                                            checked
                                                ? 'border-brand-orange bg-brand-orange/10 shadow-sm shadow-brand-orange/10'
                                                : 'border-brand-green/15 bg-white hover:border-brand-green/30 hover:shadow-sm',
                                        ].join(' ')}
                                    >
                                        <span className="flex min-w-0 items-center gap-3">
                                            <input
                                                type={inputType}
                                                name={`addon-group-${group.id}`}
                                                checked={checked}
                                                onChange={() => onToggle(addOn.id, group)}
                                                className="sr-only"
                                            />
                                            <span
                                                className={[
                                                    'flex size-5 shrink-0 items-center justify-center rounded-full border transition',
                                                    checked
                                                        ? 'border-brand-orange bg-brand-orange text-white'
                                                        : 'border-brand-green/25 bg-white text-transparent group-hover:border-brand-green/40',
                                                ].join(' ')}
                                                aria-hidden="true"
                                            >
                                                <CheckIcon />
                                            </span>
                                            <span className="font-medium text-brand-ink">{addOn.name}</span>
                                        </span>
                                        <span className="shrink-0 font-semibold text-brand-orange">
                                            + Rs {Number(addOn.price).toLocaleString('en-PK')}
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                    </fieldset>
                ))}
            </div>
        </section>
    );
}

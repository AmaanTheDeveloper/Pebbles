export default function VariantSelector({ variants, selectedId, onChange, fallbackImage = null }) {
    if (!variants?.length) {
        return null;
    }

    return (
        <fieldset className="space-y-3">
            <legend className="text-sm font-semibold text-brand-ink">Choose size</legend>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {variants.map((variant) => {
                    const selected = selectedId === variant.id;
                    const imageUrl = variant.image?.url || fallbackImage?.url || null;
                    const imageAlt = variant.image?.alt_text || fallbackImage?.alt_text || variant.name;

                    return (
                        <label
                            key={variant.id}
                            className={[
                                'group relative flex cursor-pointer flex-col overflow-hidden rounded-2xl border bg-white transition duration-300',
                                selected
                                    ? 'border-brand-orange shadow-lg shadow-brand-orange/20 ring-2 ring-brand-orange/40'
                                    : 'border-brand-green/15 hover:-translate-y-0.5 hover:border-brand-orange/40 hover:shadow-md',
                            ].join(' ')}
                        >
                            <input
                                type="radio"
                                name="variant"
                                className="sr-only"
                                checked={selected}
                                onChange={() => onChange(variant.id)}
                            />
                            <div className="aspect-square overflow-hidden bg-brand-cream">
                                {imageUrl ? (
                                    <img
                                        src={imageUrl}
                                        alt={imageAlt}
                                        className={[
                                            'size-full object-cover transition duration-300',
                                            selected ? 'scale-[1.03]' : 'group-hover:scale-[1.04]',
                                        ].join(' ')}
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center bg-gradient-to-br from-brand-green/10 to-brand-orange/10 text-xs text-brand-muted">
                                        No image
                                    </div>
                                )}
                            </div>
                            <div className="flex flex-1 flex-col gap-1 p-3">
                                <span
                                    className={[
                                        'font-display text-sm font-semibold',
                                        selected ? 'text-brand-orange' : 'text-brand-ink',
                                    ].join(' ')}
                                >
                                    {variant.name}
                                </span>
                                <span className="text-sm font-bold text-brand-green">
                                    Rs {Number(variant.price).toLocaleString('en-PK')}
                                </span>
                            </div>
                            {selected ? (
                                <span className="absolute top-2 right-2 rounded-full bg-brand-orange px-2 py-0.5 text-[10px] font-bold tracking-wide text-white uppercase shadow-sm">
                                    Selected
                                </span>
                            ) : null}
                        </label>
                    );
                })}
            </div>
        </fieldset>
    );
}

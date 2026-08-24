import { useState } from 'react';

export default function StarRatingInput({ value, onChange, error = null, id = 'review-rating' }) {
    const [hoverValue, setHoverValue] = useState(0);
    const displayValue = hoverValue || value || 0;

    return (
        <div>
            <div className="flex items-center gap-1" role="radiogroup" aria-label="Rating" id={id}>
                {Array.from({ length: 5 }, (_, index) => {
                    const starValue = index + 1;
                    const filled = starValue <= displayValue;

                    return (
                        <button
                            key={starValue}
                            type="button"
                            role="radio"
                            aria-checked={value === starValue}
                            aria-label={`${starValue} star${starValue === 1 ? '' : 's'}`}
                            className={[
                                'star-rating-input rounded-md px-1 py-0.5 text-2xl transition duration-200',
                                filled ? 'text-brand-orange scale-105' : 'text-brand-muted/40 hover:text-brand-orange/70',
                            ].join(' ')}
                            onMouseEnter={() => setHoverValue(starValue)}
                            onMouseLeave={() => setHoverValue(0)}
                            onFocus={() => setHoverValue(starValue)}
                            onBlur={() => setHoverValue(0)}
                            onClick={() => onChange(starValue)}
                        >
                            {filled ? '★' : '☆'}
                        </button>
                    );
                })}
            </div>
            {error ? <p className="mt-1 text-sm text-red-600">{error}</p> : null}
        </div>
    );
}

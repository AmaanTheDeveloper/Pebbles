export default function StarRating({ rating, size = 'sm', className = '' }) {
    const sizeClass = size === 'lg' ? 'text-base' : 'text-sm';

    return (
        <div
            className={['flex gap-0.5 text-brand-orange', sizeClass, className].filter(Boolean).join(' ')}
            aria-label={`${rating} out of 5 stars`}
            role="img"
        >
            {Array.from({ length: 5 }, (_, index) => (
                <span key={index} aria-hidden="true">
                    {index < rating ? '★' : '☆'}
                </span>
            ))}
        </div>
    );
}

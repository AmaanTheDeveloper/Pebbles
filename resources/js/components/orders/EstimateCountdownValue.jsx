export default function EstimateCountdownValue({ text, isFinalMinute = false }) {
    return (
        <p
            key={text}
            className={[
                'order-estimate-countdown-value font-display text-2xl font-semibold text-brand-orange sm:text-3xl',
                isFinalMinute ? 'is-final-minute' : '',
            ]
                .filter(Boolean)
                .join(' ')}
        >
            {text}
        </p>
    );
}

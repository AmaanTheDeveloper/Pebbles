export default function PaymentStatusBadge({ status }) {
    const styles = {
        pending: 'bg-amber-100 text-amber-900',
        submitted: 'bg-sky-100 text-sky-900',
        verified: 'bg-emerald-100 text-emerald-900',
        rejected: 'bg-red-100 text-red-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-stone-200 text-stone-700',
    };

    const className = styles[status] ?? 'bg-stone-100 text-stone-700';

    return (
        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize ${className}`}>
            {status ? String(status).replaceAll('_', ' ') : 'no payment'}
        </span>
    );
}

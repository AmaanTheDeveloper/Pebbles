const REASONS = [
    ['ordered_by_mistake', 'Ordered by mistake'],
    ['need_to_change_something', 'Need to change something'],
    ['changed_my_mind', 'Changed my mind'],
    ['other', 'Other'],
];

export default function CancelOrderModal({ open, orderNumber, busy, error, onClose, onConfirm }) {
    if (!open) {
        return null;
    }

    const handleSubmit = (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);

        onConfirm({
            cancellation_reason: formData.get('cancellation_reason') || null,
            reason: formData.get('reason') || null,
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-4">
            <div className="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl ring-1 ring-stone-200">
                <h3 className="text-lg font-semibold text-stone-900">Cancel this order?</h3>
                <p className="mt-2 text-sm text-stone-600">
                    Are you sure you want to cancel order {orderNumber}? This action cannot be undone.
                </p>

                <form onSubmit={handleSubmit} className="mt-4 space-y-3">
                    <label className="block space-y-1 text-sm">
                        <span className="font-medium text-stone-700">Reason (optional)</span>
                        <select
                            name="cancellation_reason"
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            disabled={busy}
                        >
                            <option value="">Select a reason</option>
                            {REASONS.map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block space-y-1 text-sm">
                        <span className="font-medium text-stone-700">Additional note (optional)</span>
                        <textarea
                            name="reason"
                            rows={2}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            disabled={busy}
                        />
                    </label>

                    {error ? <p className="text-sm text-red-700">{error}</p> : null}

                    <div className="flex flex-wrap justify-end gap-2 pt-2">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={busy}
                            className="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 ring-1 ring-stone-300 hover:bg-stone-50 disabled:opacity-60"
                        >
                            Keep order
                        </button>
                        <button
                            type="submit"
                            disabled={busy}
                            className="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-600 disabled:opacity-60"
                        >
                            {busy ? 'Cancelling…' : 'Cancel order'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

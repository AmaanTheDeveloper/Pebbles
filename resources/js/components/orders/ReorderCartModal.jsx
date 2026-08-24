export default function ReorderCartModal({
    open,
    busy,
    error,
    onClose,
    onKeepCart,
    onMerge,
    onReplace,
}) {
    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-4">
            <div className="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl ring-1 ring-stone-200">
                <h3 className="text-lg font-semibold text-stone-900">Your cart already has items</h3>
                <p className="mt-2 text-sm text-stone-600">
                    How would you like to add this previous order to your cart?
                </p>

                {error ? <p className="mt-3 text-sm text-red-700">{error}</p> : null}

                <div className="mt-5 space-y-2">
                    <button
                        type="button"
                        onClick={onMerge}
                        disabled={busy}
                        className="w-full rounded-lg bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                    >
                        {busy ? 'Adding to cart…' : 'Add this order to cart'}
                    </button>
                    <button
                        type="button"
                        onClick={onReplace}
                        disabled={busy}
                        className="w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-stone-800 ring-1 ring-stone-300 hover:bg-stone-50 disabled:opacity-60"
                    >
                        Replace cart with this order
                    </button>
                    <button
                        type="button"
                        onClick={onKeepCart}
                        disabled={busy}
                        className="w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-stone-600 hover:bg-stone-50 disabled:opacity-60"
                    >
                        Keep current cart
                    </button>
                </div>

                <button
                    type="button"
                    onClick={onClose}
                    disabled={busy}
                    className="mt-3 w-full text-center text-xs text-stone-500 hover:text-stone-700 disabled:opacity-60"
                >
                    Cancel
                </button>
            </div>
        </div>
    );
}

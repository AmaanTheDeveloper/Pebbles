export default function ErrorState({ message, onRetry }) {
    return (
        <div className="rounded-2xl border border-red-200 bg-red-50 px-6 py-10 text-center">
            <h2 className="text-lg font-semibold text-red-900">Something went wrong</h2>
            <p className="mt-2 text-sm text-red-700">{message || 'Please try again.'}</p>
            {onRetry ? (
                <button
                    type="button"
                    onClick={onRetry}
                    className="mt-5 inline-flex items-center justify-center rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800"
                >
                    Try again
                </button>
            ) : null}
        </div>
    );
}

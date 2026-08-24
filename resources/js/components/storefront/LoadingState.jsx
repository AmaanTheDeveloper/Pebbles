export default function LoadingState({ label = 'Loading…' }) {
    return (
        <div className="flex items-center justify-center py-16" role="status" aria-live="polite">
            <div className="flex items-center gap-3 text-stone-500">
                <span className="size-5 animate-spin rounded-full border-2 border-stone-300 border-t-amber-700" />
                <span className="text-sm">{label}</span>
            </div>
        </div>
    );
}

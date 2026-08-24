export default function OpenStatusBadge({ isOpen }) {
    return (
        <span
            className={[
                'inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold',
                isOpen ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700',
            ].join(' ')}
        >
            <span
                className={['size-2 rounded-full', isOpen ? 'bg-emerald-500' : 'bg-stone-500'].join(' ')}
                aria-hidden="true"
            />
            {isOpen ? 'Open now' : 'Closed now'}
        </span>
    );
}

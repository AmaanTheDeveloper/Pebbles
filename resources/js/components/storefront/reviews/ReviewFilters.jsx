const SORT_OPTIONS = [
    { value: 'newest', label: 'Newest' },
    { value: 'highest', label: 'Highest Rated' },
    { value: 'lowest', label: 'Lowest Rated' },
];

const RATING_OPTIONS = [
    { value: '', label: 'All ratings' },
    { value: '5', label: '5 Stars' },
    { value: '4', label: '4 Stars' },
    { value: '3', label: '3 Stars' },
    { value: '2', label: '2 Stars' },
    { value: '1', label: '1 Star' },
];

export default function ReviewFilters({ sort, rating, onSortChange, onRatingChange }) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
            <div>
                <label htmlFor="review-sort" className="mb-1 block text-xs font-semibold tracking-wide text-brand-green uppercase">
                    Sort by
                </label>
                <select
                    id="review-sort"
                    value={sort}
                    onChange={(event) => onSortChange(event.target.value)}
                    className="rounded-xl border border-brand-green/15 bg-white px-3 py-2.5 text-sm font-semibold text-brand-ink outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/15"
                >
                    {SORT_OPTIONS.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </div>

            <div>
                <span className="mb-2 block text-xs font-semibold tracking-wide text-brand-green uppercase">Filter rating</span>
                <div className="gallery-filters flex flex-wrap gap-2">
                    {RATING_OPTIONS.map((option) => {
                        const isActive = rating === option.value;

                        return (
                            <button
                                key={option.value || 'all'}
                                type="button"
                                className={['gallery-filter-chip', isActive ? 'is-active' : ''].filter(Boolean).join(' ')}
                                onClick={() => onRatingChange(option.value)}
                            >
                                {option.label}
                            </button>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

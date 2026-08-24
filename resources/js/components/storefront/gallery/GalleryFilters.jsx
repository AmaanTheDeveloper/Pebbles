import { GALLERY_CATEGORIES } from '../../../lib/galleryContent';

export default function GalleryFilters({ activeCategory, onChange }) {
    return (
        <div
            className="gallery-filters -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:justify-center sm:overflow-visible sm:px-0"
            role="tablist"
            aria-label="Gallery categories"
        >
            {GALLERY_CATEGORIES.map((category) => {
                const isActive = activeCategory === category;

                return (
                    <button
                        key={category}
                        type="button"
                        role="tab"
                        aria-selected={isActive}
                        className={['gallery-filter-chip', isActive ? 'is-active' : ''].filter(Boolean).join(' ')}
                        onClick={() => onChange(category)}
                    >
                        {category}
                    </button>
                );
            })}
        </div>
    );
}

import EmptyState from '../EmptyState';
import GalleryItem from './GalleryItem';
import { GALLERY_LAYOUT_CLASSES, getGalleryLayoutVariant } from '../../../lib/galleryContent';

export default function GalleryGrid({ items, filterKey, isFiltering, onOpenItem }) {
    if (!items.length) {
        return (
            <div className="gallery-grid-empty rounded-3xl bg-white/80 px-6 py-16 ring-1 ring-brand-green/10">
                <EmptyState
                    title="No gallery moments yet"
                    description="No gallery moments available in this category yet."
                />
            </div>
        );
    }

    return (
        <div
            key={filterKey}
            className={[
                'gallery-mosaic-grid grid grid-cols-2 gap-3 md:grid-cols-4 md:auto-rows-[minmax(11rem,auto)] md:gap-4',
                isFiltering ? 'is-filtering' : 'gallery-grid-animate-in',
            ]
                .filter(Boolean)
                .join(' ')}
        >
            {items.map((item, index) => {
                const layoutKey = getGalleryLayoutVariant(index);

                return (
                    <div
                        key={item.id}
                        className={['gallery-item-wrap min-h-0', GALLERY_LAYOUT_CLASSES[layoutKey]].filter(Boolean).join(' ')}
                        style={{ animationDelay: `${index * 60}ms` }}
                    >
                        <GalleryItem
                            item={item}
                            layoutClass={GALLERY_LAYOUT_CLASSES[layoutKey]}
                            animationDelay={index * 60}
                            onOpen={onOpenItem}
                        />
                    </div>
                );
            })}
        </div>
    );
}

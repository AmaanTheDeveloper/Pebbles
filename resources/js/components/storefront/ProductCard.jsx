import { Link } from 'react-router-dom';
import AppImage from '../performance/AppImage';

function formatPrice(value) {
    if (value === null || value === undefined) {
        return null;
    }

    return `Rs ${Number(value).toLocaleString('en-PK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export default function ProductCard({ product, featured = false }) {
    const imageUrl = product.image?.url;
    const hasPromo = product.promotional_price != null && product.compare_at_price != null;
    const price = formatPrice(hasPromo ? product.promotional_price : product.starting_price);
    const compareAt = hasPromo ? formatPrice(product.compare_at_price) : null;
    const showFeatured = featured || product.is_featured;

    return (
        <Link to={`/product/${product.slug}`} className="product-card group relative z-0 h-full">
            <div className="product-card-media">
                {imageUrl ? (
                    <AppImage
                        src={imageUrl}
                        alt={product.image?.alt_text || product.name}
                        className="size-full object-cover"
                        wrapperClassName="size-full"
                        width={600}
                        height={600}
                    />
                ) : (
                    <div className="flex size-full items-center justify-center bg-gradient-to-br from-brand-green/10 to-brand-orange/10 text-sm text-brand-muted">
                        No image
                    </div>
                )}
                {showFeatured ? (
                    <span className="product-card-badge absolute top-3 left-3 z-10 rounded-full bg-gradient-to-r from-brand-orange to-[#ea580c] px-2.5 py-1 text-[11px] font-semibold tracking-wide text-white uppercase shadow-md shadow-brand-orange/40">
                        Featured
                    </span>
                ) : null}
                {product.category?.name ? (
                    <span className="product-card-badge absolute top-3 right-3 z-10 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-brand-green shadow-sm backdrop-blur-sm">
                        {product.category.name}
                    </span>
                ) : null}
            </div>
            <div className="relative z-[1] flex flex-1 flex-col gap-2 p-4 sm:p-5">
                <div className="flex items-start justify-between gap-2">
                    <h3 className="font-display text-lg font-semibold text-brand-ink transition duration-300 group-hover:text-brand-green">
                        {product.name}
                    </h3>
                    {!product.available || product.stock_status === 'out_of_stock' ? (
                        <span className="shrink-0 rounded-full bg-stone-200 px-2 py-0.5 text-xs font-medium text-stone-700">
                            {product.stock_status === 'out_of_stock' ? 'Out of stock' : 'Unavailable'}
                        </span>
                    ) : null}
                </div>
                {product.short_description ? (
                    <p className="line-clamp-2 text-sm leading-relaxed text-brand-muted">{product.short_description}</p>
                ) : null}
                <div className="mt-auto flex items-center justify-between gap-3 pt-2">
                    {price ? (
                        <p className="text-sm font-bold text-brand-orange transition group-hover:text-[#ea580c]">
                            {hasPromo ? (
                                <>
                                    <span className="mr-2 text-xs font-medium text-brand-muted line-through">{compareAt}</span>
                                    From {price}
                                </>
                            ) : (
                                <>From {price}</>
                            )}
                        </p>
                    ) : (
                        <span />
                    )}
                    <span className="product-card-cta">
                        View <span className="btn-arrow" aria-hidden="true">→</span>
                    </span>
                </div>
            </div>
        </Link>
    );
}

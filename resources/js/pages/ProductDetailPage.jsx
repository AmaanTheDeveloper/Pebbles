import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import AppImage from '../components/performance/AppImage';
import { fetchProduct } from '../api/catalog';
import AddOnList from '../components/storefront/AddOnList';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import VariantSelector from '../components/storefront/VariantSelector';
import SeoHead from '../components/seo/SeoHead';
import { useCart } from '../contexts/CartContext';
import { useCatalogQuery } from '../hooks/useCatalog';
import { addOnSectionDescription, addOnSectionHeading } from '../lib/productAddOns';
import { flyProductToCart } from '../lib/cartAddAnimation';

export default function ProductDetailPage() {
    const { slug } = useParams();
    const { addItem, mutating, itemCount } = useCart();
    const { data: product, loading, error, reload } = useCatalogQuery(() => fetchProduct(slug), [slug]);

    const addButtonRef = useRef(null);

    const [selectedVariantId, setSelectedVariantId] = useState(null);
    const [selectedAddOnIds, setSelectedAddOnIds] = useState([]);
    const [quantity, setQuantity] = useState(1);
    const [actionError, setActionError] = useState(null);
    const [actionSuccess, setActionSuccess] = useState(null);

    useEffect(() => {
        if (!product) {
            return;
        }

        setSelectedVariantId(product.variants?.[0]?.id ?? null);
        setSelectedAddOnIds([]);
        setQuantity(1);
        setActionError(null);
        setActionSuccess(null);
    }, [product]);

    const selectedVariant = useMemo(
        () => (product?.variants ?? []).find((variant) => variant.id === selectedVariantId) ?? null,
        [product, selectedVariantId],
    );

    const primaryImage = useMemo(() => {
        if (!product) {
            return null;
        }

        return product.images?.find((image) => image.is_primary) ?? product.images?.[0] ?? null;
    }, [product]);

    const displayImage = useMemo(() => {
        if (selectedVariant?.image?.url) {
            return selectedVariant.image;
        }

        return primaryImage;
    }, [selectedVariant, primaryImage]);

    const selectedAddOnTotal = useMemo(() => {
        return (product?.add_on_groups ?? []).reduce((total, group) => {
            const groupTotal = (group.add_ons ?? []).reduce((groupSum, addOn) => {
                if (!selectedAddOnIds.includes(addOn.id)) {
                    return groupSum;
                }

                return groupSum + Number(addOn.price ?? 0);
            }, 0);

            return total + groupTotal;
        }, 0);
    }, [product, selectedAddOnIds]);

    const handleAddOnToggle = (addOnId, group) => {
        setSelectedAddOnIds((current) => {
            const isSelected = current.includes(addOnId);

            if (isSelected) {
                return current.filter((id) => id !== addOnId);
            }

            if (group.max_selections === 1) {
                const groupAddOnIds = new Set((group.add_ons ?? []).map((addOn) => addOn.id));
                const withoutGroup = current.filter((id) => !groupAddOnIds.has(id));

                return [...withoutGroup, addOnId];
            }

            if (
                group.max_selections &&
                current.filter((id) => (group.add_ons ?? []).some((addOn) => addOn.id === id)).length >=
                    group.max_selections
            ) {
                return current;
            }

            return [...current, addOnId];
        });
    };

    const validateSelections = () => {
        if ((product.variants ?? []).length > 0 && !selectedVariantId) {
            return 'Please select a variant.';
        }

        for (const group of product.add_on_groups ?? []) {
            const groupAddOnIds = new Set((group.add_ons ?? []).map((addOn) => addOn.id));
            const selectedCount = selectedAddOnIds.filter((id) => groupAddOnIds.has(id)).length;
            const minimum = group.is_required ? Math.max(1, group.min_selections || 1) : group.min_selections || 0;

            if (selectedCount < minimum) {
                return `Please select options for ${group.name}.`;
            }
        }

        if (quantity < 1 || quantity > 99) {
            return 'Quantity must be between 1 and 99.';
        }

        if (product.stock_quantity != null && quantity > product.stock_quantity) {
            return `Only ${product.stock_quantity} left in stock.`;
        }

        return null;
    };

    const handleAddToCart = async () => {
        setActionError(null);
        setActionSuccess(null);

        const validationMessage = validateSelections();
        if (validationMessage) {
            setActionError(validationMessage);
            return;
        }

        const wasEmptyCart = itemCount === 0;

        try {
            await addItem({
                product_id: product.id,
                product_variant_id: selectedVariantId,
                add_on_ids: selectedAddOnIds,
                quantity,
            });
            setActionSuccess('Added to cart.');

            if (wasEmptyCart) {
                void flyProductToCart({
                    sourceElement: addButtonRef.current,
                    imageUrl: displayImage?.url ?? null,
                });
            }
        } catch (err) {
            setActionError(err.message || 'Unable to add this item to your cart.');
        }
    };

    if (loading) {
        return <LoadingState label="Loading product…" />;
    }

    if (error) {
        if (error.status === 404) {
            return (
                <EmptyState
                    title="Product not found"
                    description="This item may be unavailable or no longer on the menu."
                />
            );
        }

        return <ErrorState message={error.message} onRetry={reload} />;
    }

    if (!product) {
        return (
            <EmptyState
                title="Product not found"
                description="This item may be unavailable or no longer on the menu."
            />
        );
    }

    const displayPrice = selectedVariant?.price ?? product.starting_price;
    const hasPromo = !selectedVariant && product.promotional_price != null && product.compare_at_price != null;
    const promoDisplayPrice = hasPromo ? product.promotional_price : displayPrice;
    const configuredTotal = Number(promoDisplayPrice) + selectedAddOnTotal;
    const addOnHeading = addOnSectionHeading(product.category?.slug);
    const addOnDescription = addOnSectionDescription(product.category?.slug);
    const seoDescription =
        product.short_description ||
        product.description ||
        `Order ${product.name} from Pebbles — ice cream, shakes, faluda & kulfi.`;
    const productJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
        description: seoDescription,
        image: displayImage?.url ? [displayImage.url] : undefined,
        offers: {
            '@type': 'Offer',
            priceCurrency: 'PKR',
            price: String(promoDisplayPrice ?? product.starting_price ?? ''),
            availability:
                !product.available || product.stock_status === 'out_of_stock'
                    ? 'https://schema.org/OutOfStock'
                    : 'https://schema.org/InStock',
        },
    };

    return (
        <div className="space-y-6">
            <SeoHead
                title={product.name}
                description={seoDescription}
                path={`/product/${product.slug}`}
                image={displayImage?.url}
                type="product"
                jsonLd={productJsonLd}
            />
            <Link to="/menu" className="text-sm font-medium text-brand-green transition hover:text-brand-orange">
                ← Back to menu
            </Link>

            <div className="grid gap-8 lg:grid-cols-2">
                <div className="w-full self-start overflow-hidden rounded-3xl bg-brand-cream shadow-lg shadow-brand-green/10 ring-1 ring-brand-green/10">
                    {displayImage?.url ? (
                        <AppImage
                            key={displayImage.url}
                            src={displayImage.url}
                            alt={displayImage.alt_text || product.name}
                            className="aspect-square w-full object-cover transition duration-300"
                            wrapperClassName="aspect-square w-full"
                            eager
                            highPriority
                            width={800}
                            height={800}
                        />
                    ) : (
                        <div className="flex aspect-square items-center justify-center text-brand-muted">No image</div>
                    )}
                </div>

                <div className="space-y-6">
                    <div className="space-y-2">
                        <p className="text-sm text-brand-muted">{product.category?.name}</p>
                        <h1 className="font-display text-3xl font-semibold tracking-tight text-brand-ink">
                            {product.name}
                        </h1>
                        {!product.available ? (
                            <p className="text-sm font-medium text-brand-muted">
                                {product.stock_status === 'out_of_stock'
                                    ? 'Out of stock'
                                    : 'Currently unavailable'}
                            </p>
                        ) : product.stock_status === 'low_stock' ? (
                            <p className="text-sm font-medium text-amber-800">
                                Only a few left
                                {product.stock_quantity != null ? ` (${product.stock_quantity})` : ''}
                            </p>
                        ) : null}
                        {product.short_description ? (
                            <p className="text-brand-muted">{product.short_description}</p>
                        ) : null}
                        {product.description ? (
                            <p className="text-sm leading-relaxed text-brand-muted">{product.description}</p>
                        ) : null}
                        <p className="text-2xl font-bold text-brand-orange">
                            {hasPromo ? (
                                <>
                                    <span className="mr-2 text-base font-medium text-brand-muted line-through">
                                        Rs {Number(product.compare_at_price).toLocaleString('en-PK')}
                                    </span>
                                    Rs {Number(product.promotional_price).toLocaleString('en-PK')}
                                </>
                            ) : (
                                <>Rs {Number(displayPrice).toLocaleString('en-PK')}</>
                            )}
                        </p>
                        {selectedAddOnTotal > 0 ? (
                            <p className="text-sm text-brand-muted">
                                With toppings:{' '}
                                <span className="font-semibold text-brand-ink">
                                    Rs {configuredTotal.toLocaleString('en-PK')}
                                </span>
                            </p>
                        ) : null}
                        {selectedVariant ? (
                            <p className="text-sm text-brand-muted">
                                Selected: <span className="font-semibold text-brand-green">{selectedVariant.name}</span>
                            </p>
                        ) : null}
                    </div>

                    <VariantSelector
                        variants={product.variants ?? []}
                        selectedId={selectedVariantId}
                        onChange={setSelectedVariantId}
                        fallbackImage={primaryImage}
                    />

                    <AddOnList
                        groups={product.add_on_groups ?? []}
                        selectedIds={selectedAddOnIds}
                        onToggle={handleAddOnToggle}
                        heading={addOnHeading}
                        description={addOnDescription}
                    />

                    <label className="flex items-center gap-2 text-sm text-brand-ink">
                        Quantity
                        <input
                            type="number"
                            min={1}
                            max={
                                product.stock_quantity != null
                                    ? Math.min(99, Math.max(1, product.stock_quantity))
                                    : 99
                            }
                            value={quantity}
                            onChange={(event) => setQuantity(Number(event.target.value))}
                            disabled={!product.available}
                            className="w-20 rounded-lg border border-brand-green/20 px-2 py-1 focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/30 disabled:opacity-50"
                        />
                    </label>

                    {actionError ? <p className="text-sm text-red-700">{actionError}</p> : null}
                    {actionSuccess ? (
                        <p className="text-sm text-emerald-700">
                            {actionSuccess}{' '}
                            <Link to="/cart" className="font-medium underline">
                                View cart
                            </Link>
                        </p>
                    ) : null}

                    <button
                        ref={addButtonRef}
                        type="button"
                        disabled={mutating || !product.available}
                        onClick={handleAddToCart}
                        className="btn-orange btn-shine w-full px-4 py-3 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {mutating
                            ? 'Adding…'
                            : product.stock_status === 'out_of_stock'
                              ? 'Out of stock'
                              : !product.available
                                ? 'Unavailable'
                                : 'Add to cart'}
                        {!mutating && product.available ? (
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        ) : null}
                    </button>
                </div>
            </div>
        </div>
    );
}

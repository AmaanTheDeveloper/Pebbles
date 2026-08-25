import { Link } from 'react-router-dom';
import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { fetchCategories, fetchProducts } from '../api/catalog';
import CategoryNav from '../components/storefront/CategoryNav';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import ProductCard from '../components/storefront/ProductCard';
import SeoHead from '../components/seo/SeoHead';
import { useCatalogQuery } from '../hooks/useCatalog';

export default function MenuPage() {
    const { categorySlug } = useParams();
    const [searchInput, setSearchInput] = useState('');
    const [searchQuery, setSearchQuery] = useState('');

    const {
        data: categories,
        loading: categoriesLoading,
        error: categoriesError,
        reload: reloadCategories,
    } = useCatalogQuery(fetchCategories, []);

    const {
        data: products,
        loading: productsLoading,
        error: productsError,
        reload: reloadProducts,
    } = useCatalogQuery(
        () =>
            fetchProducts({
                category: categorySlug,
                search: searchQuery || undefined,
            }),
        [categorySlug, searchQuery],
    );

    const loading = categoriesLoading || productsLoading;
    const error = categoriesError || productsError;
    const activeCategory = (categories ?? []).find((category) => category.slug === categorySlug);

    const handleSearchSubmit = (event) => {
        event.preventDefault();
        setSearchQuery(searchInput.trim());
    };

    const handleClearSearch = () => {
        setSearchInput('');
        setSearchQuery('');
    };

    if (loading) {
        return <LoadingState label="Loading menu…" />;
    }

    if (error) {
        return (
            <ErrorState
                message={error.message}
                onRetry={() => {
                    reloadCategories();
                    reloadProducts();
                }}
            />
        );
    }

    const emptyDescription = searchQuery
        ? 'No products match your search. Try a different term or clear the search.'
        : categorySlug
          ? 'This category has no available items right now.'
          : 'Products will appear here once the catalog is published.';

    return (
        <div className="space-y-6">
            <SeoHead
                title={activeCategory ? `${activeCategory.name} Menu` : 'Menu'}
                description={
                    activeCategory?.description ||
                    'Browse the Pebbles digital menu — ice cream, shakes, faluda, kulfi, and more. Order online for takeaway or delivery.'
                }
                path={categorySlug ? `/menu/${categorySlug}` : '/menu'}
            />
            <div className="rounded-xl border border-brand-green/15 bg-gradient-to-r from-brand-cream/80 to-white p-4 sm:p-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold tracking-wide text-brand-green uppercase">Menu</p>
                        <p className="text-sm text-brand-muted">
                            Browse products and order online, or view our full restaurant menu.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <span className="inline-flex items-center justify-center rounded-lg border border-brand-green/20 bg-white px-4 py-2 text-sm font-medium text-brand-ink">
                            Digital Menu
                        </span>
                        <Link
                            to="/menu-book"
                            className="inline-flex items-center justify-center rounded-lg border border-brand-orange/30 bg-brand-orange/10 px-4 py-2 text-sm font-semibold text-brand-orange transition hover:bg-brand-orange hover:text-white"
                        >
                            View Restaurant Menu
                        </Link>
                    </div>
                </div>
            </div>

            <div className="space-y-2">
                <h1 className="text-2xl font-semibold tracking-tight text-stone-900 sm:text-3xl">
                    {activeCategory ? activeCategory.name : 'Full menu'}
                </h1>
                <p className="text-sm text-stone-600">
                    {activeCategory?.description || 'Shakes, ice cream, Limca, and more — browse everything available today.'}
                </p>
            </div>

            <CategoryNav categories={categories ?? []} />

            <form onSubmit={handleSearchSubmit} className="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label className="sr-only" htmlFor="menu-search">
                    Search menu
                </label>
                <input
                    id="menu-search"
                    type="search"
                    value={searchInput}
                    onChange={(event) => setSearchInput(event.target.value)}
                    placeholder="Search products…"
                    className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus:border-stone-500 focus:outline-none focus:ring-2 focus:ring-stone-300 sm:max-w-sm"
                />
                <div className="flex gap-2">
                    <button
                        type="submit"
                        className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800"
                    >
                        Search
                    </button>
                    {searchQuery ? (
                        <button
                            type="button"
                            onClick={handleClearSearch}
                            className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                        >
                            Clear
                        </button>
                    ) : null}
                </div>
            </form>

            {(products ?? []).length === 0 ? (
                <EmptyState title="No products found" description={emptyDescription} />
            ) : (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {products.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            )}
        </div>
    );
}

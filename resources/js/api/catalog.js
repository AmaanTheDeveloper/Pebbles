import api from './client';
import { cachedCatalogFetch } from '../lib/catalogCache';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchCategories() {
    return cachedCatalogFetch('categories', async () => {
        const response = await api.get('/api/v1/catalog/categories');

        return unwrap(response);
    });
}

export async function fetchProducts({ category, search, featured } = {}) {
    return cachedCatalogFetch('products', async () => {
        const response = await api.get('/api/v1/catalog/products', {
            params: {
                ...(category ? { category } : {}),
                ...(search ? { search } : {}),
                ...(featured ? { featured: 1 } : {}),
            },
        });

        return unwrap(response);
    }, [{ category: category ?? null, search: search ?? null, featured: featured ?? null }], 2 * 60 * 1000);
}

export async function fetchProduct(slug) {
    const response = await api.get(`/api/v1/catalog/products/${slug}`);

    return unwrap(response);
}

export async function fetchBranches() {
    return cachedCatalogFetch('branches', async () => {
        const response = await api.get('/api/v1/catalog/branches');

        return unwrap(response);
    });
}

export async function fetchDeliveryZones(branchSlug) {
    const response = await api.get('/api/v1/catalog/delivery-zones', {
        params: {
            ...(branchSlug ? { branch: branchSlug } : {}),
        },
    });

    return unwrap(response);
}

export async function fetchOffers() {
    const response = await api.get('/api/v1/catalog/offers');

    return unwrap(response);
}

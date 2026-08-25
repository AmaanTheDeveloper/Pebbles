import api from './client';
import { cachedCatalogFetch } from '../lib/catalogCache';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchHomepageContent() {
    return cachedCatalogFetch('homepage', async () => {
        const response = await api.get('/api/v1/catalog/homepage');

        return (
            unwrap(response) ?? {
                sections: [],
                features: [],
                cta_cards: [],
                showcase_items: [],
            }
        );
    }, [], 2 * 60 * 1000);
}

export function sectionSettings(sections, key) {
    return sections.find((section) => section.key === key)?.settings ?? {};
}

export function activeSections(sections) {
    return [...(sections ?? [])].sort((a, b) => a.display_order - b.display_order);
}

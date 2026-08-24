import api from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchHomepageContent() {
    const response = await api.get('/api/v1/catalog/homepage');

    return unwrap(response) ?? {
        sections: [],
        features: [],
        cta_cards: [],
        showcase_items: [],
    };
}

export function sectionSettings(sections, key) {
    return sections.find((section) => section.key === key)?.settings ?? {};
}

export function activeSections(sections) {
    return [...(sections ?? [])].sort((a, b) => a.display_order - b.display_order);
}

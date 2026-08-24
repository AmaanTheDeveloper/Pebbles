const STORAGE_KEY = 'pebbles.selectedBranch';

/**
 * @typedef {{ id: number, name: string, slug: string }} SelectedBranch
 */

/**
 * @returns {SelectedBranch|null}
 */
export function readSelectedBranch() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);

        if (!parsed?.id || !parsed?.slug || !parsed?.name) {
            return null;
        }

        return {
            id: parsed.id,
            name: parsed.name,
            slug: parsed.slug,
        };
    } catch {
        return null;
    }
}

/**
 * @param {SelectedBranch|null} branch
 */
export function writeSelectedBranch(branch) {
    if (!branch) {
        localStorage.removeItem(STORAGE_KEY);

        return;
    }

    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
            id: branch.id,
            name: branch.name,
            slug: branch.slug,
        }),
    );
}

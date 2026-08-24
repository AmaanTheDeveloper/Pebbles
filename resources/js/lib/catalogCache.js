const DEFAULT_TTL_MS = 5 * 60 * 1000;

/** @type {Map<string, { expiresAt: number, value: unknown, promise: Promise<unknown> | null }>} */
const cache = new Map();

function cacheKey(name, args) {
    return `${name}:${JSON.stringify(args ?? [])}`;
}

/**
 * Cache read-mostly catalog API responses in-memory for the session.
 *
 * @template T
 * @param {string} name
 * @param {() => Promise<T>} loader
 * @param {unknown[]} [args]
 * @param {number} [ttlMs]
 * @returns {Promise<T>}
 */
export function cachedCatalogFetch(name, loader, args = [], ttlMs = DEFAULT_TTL_MS) {
    const key = cacheKey(name, args);
    const existing = cache.get(key);
    const now = Date.now();

    if (existing && existing.expiresAt > now && existing.value !== undefined) {
        return Promise.resolve(existing.value);
    }

    if (existing?.promise) {
        return existing.promise;
    }

    const promise = loader()
        .then((value) => {
            cache.set(key, {
                expiresAt: Date.now() + ttlMs,
                value,
                promise: null,
            });

            return value;
        })
        .catch((error) => {
            cache.delete(key);

            throw error;
        });

    cache.set(key, {
        expiresAt: now + ttlMs,
        value: undefined,
        promise,
    });

    return promise;
}

export function invalidateCatalogCache(namePrefix = '') {
    for (const key of cache.keys()) {
        if (!namePrefix || key.startsWith(`${namePrefix}:`)) {
            cache.delete(key);
        }
    }
}

import { useCallback, useEffect, useState } from 'react';
import { normalizeApiError } from '../lib/errors';

/**
 * Thin async loader for catalog API calls.
 *
 * @template T
 * @param {() => Promise<T>} loader
 * @param {unknown[]} [deps]
 */
export function useCatalogQuery(loader, deps = []) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const run = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await loader();
            setData(result);
            return result;
        } catch (err) {
            const normalized = normalizeApiError(err);
            setError(normalized);
            setData(null);
            return null;
        } finally {
            setLoading(false);
        }
    }, deps);

    useEffect(() => {
        run();
    }, [run]);

    return { data, loading, error, reload: run };
}

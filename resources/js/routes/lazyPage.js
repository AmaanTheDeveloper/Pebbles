import { lazy } from 'react';

export function lazyPage(importer) {
    return lazy(importer);
}

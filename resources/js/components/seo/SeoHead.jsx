import { useEffect } from 'react';
import { appName } from '../../api/config';

const MANAGED_ATTR = 'data-pebbles-seo';

function absoluteUrl(pathOrUrl) {
    if (!pathOrUrl) {
        return undefined;
    }

    if (/^https?:\/\//i.test(pathOrUrl)) {
        return pathOrUrl;
    }

    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const path = pathOrUrl.startsWith('/') ? pathOrUrl : `/${pathOrUrl}`;

    return `${origin}${path}`;
}

function setMetaByName(name, content, tracked) {
    if (content == null || content === '') {
        return;
    }

    let el = document.querySelector(`meta[name="${name}"]`);
    if (!el) {
        el = document.createElement('meta');
        el.setAttribute('name', name);
        el.setAttribute(MANAGED_ATTR, '1');
        document.head.appendChild(el);
        tracked.push(el);
    } else if (!el.hasAttribute(MANAGED_ATTR)) {
        el.setAttribute(MANAGED_ATTR, '1');
        tracked.push(el);
    }

    el.setAttribute('content', content);
}

function setMetaByProperty(property, content, tracked) {
    if (content == null || content === '') {
        return;
    }

    let el = document.querySelector(`meta[property="${property}"]`);
    if (!el) {
        el = document.createElement('meta');
        el.setAttribute('property', property);
        el.setAttribute(MANAGED_ATTR, '1');
        document.head.appendChild(el);
        tracked.push(el);
    } else if (!el.hasAttribute(MANAGED_ATTR)) {
        el.setAttribute(MANAGED_ATTR, '1');
        tracked.push(el);
    }

    el.setAttribute('content', content);
}

function setLinkRel(rel, href, tracked) {
    if (!href) {
        return;
    }

    let el = document.querySelector(`link[rel="${rel}"]`);
    if (!el) {
        el = document.createElement('link');
        el.setAttribute('rel', rel);
        el.setAttribute(MANAGED_ATTR, '1');
        document.head.appendChild(el);
        tracked.push(el);
    } else if (!el.hasAttribute(MANAGED_ATTR)) {
        el.setAttribute(MANAGED_ATTR, '1');
        tracked.push(el);
    }

    el.setAttribute('href', href);
}

function setJsonLd(data, tracked) {
    if (!data) {
        return;
    }

    const el = document.createElement('script');
    el.type = 'application/ld+json';
    el.setAttribute(MANAGED_ATTR, '1');
    el.text = JSON.stringify(data);
    document.head.appendChild(el);
    tracked.push(el);
}

/**
 * Lightweight SPA SEO head manager (no external deps).
 * Cleans up managed tags on unmount / dependency change.
 */
export default function SeoHead({
    title,
    description,
    path,
    image,
    type = 'website',
    robots,
    noindex = false,
    jsonLd,
}) {
    const jsonLdKey = jsonLd ? JSON.stringify(jsonLd) : '';

    useEffect(() => {
        const tracked = [];
        const previousTitle = document.title;
        const resolvedTitle = title ? (title.includes(appName) ? title : `${title} — ${appName}`) : appName;
        const robotsContent = noindex ? 'noindex, nofollow' : robots || 'index, follow';
        const canonical = absoluteUrl(path ?? (typeof window !== 'undefined' ? window.location.pathname : '/'));
        const imageUrl = absoluteUrl(image);
        const parsedJsonLd = jsonLdKey ? JSON.parse(jsonLdKey) : null;

        document.title = resolvedTitle;

        setMetaByName('description', description, tracked);
        setMetaByName('robots', robotsContent, tracked);
        setLinkRel('canonical', canonical, tracked);

        setMetaByProperty('og:title', resolvedTitle, tracked);
        setMetaByProperty('og:description', description, tracked);
        setMetaByProperty('og:type', type, tracked);
        setMetaByProperty('og:url', canonical, tracked);
        setMetaByProperty('og:site_name', appName, tracked);
        if (imageUrl) {
            setMetaByProperty('og:image', imageUrl, tracked);
        }

        setMetaByName('twitter:card', imageUrl ? 'summary_large_image' : 'summary', tracked);
        setMetaByName('twitter:title', resolvedTitle, tracked);
        setMetaByName('twitter:description', description, tracked);
        if (imageUrl) {
            setMetaByName('twitter:image', imageUrl, tracked);
        }

        setJsonLd(parsedJsonLd, tracked);

        return () => {
            document.title = previousTitle;
            tracked.forEach((el) => {
                if (el.getAttribute(MANAGED_ATTR) === '1') {
                    el.remove();
                }
            });
        };
    }, [title, description, path, image, type, robots, noindex, jsonLdKey]);

    return null;
}

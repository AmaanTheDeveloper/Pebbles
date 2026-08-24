import { useState } from 'react';

export default function AppImage({
    src,
    alt = '',
    className = '',
    wrapperClassName = '',
    eager = false,
    highPriority = false,
    aspectRatio,
    ...props
}) {
    const [loaded, setLoaded] = useState(false);
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return (
            <div
                className={`app-image-fallback ${wrapperClassName}`.trim()}
                style={aspectRatio ? { aspectRatio } : undefined}
                aria-hidden={alt ? undefined : true}
            >
                {alt ? <span className="sr-only">{alt}</span> : null}
            </div>
        );
    }

    return (
        <div
            className={`app-image-shell ${loaded ? 'is-loaded' : ''} ${wrapperClassName}`.trim()}
            style={aspectRatio ? { aspectRatio } : undefined}
        >
            {!loaded ? <span className="app-image-shimmer" aria-hidden="true" /> : null}
            <img
                src={src}
                alt={alt}
                className={className}
                loading={eager || highPriority ? 'eager' : 'lazy'}
                decoding="async"
                fetchPriority={highPriority ? 'high' : undefined}
                onLoad={() => setLoaded(true)}
                onError={() => setFailed(true)}
                {...props}
            />
        </div>
    );
}

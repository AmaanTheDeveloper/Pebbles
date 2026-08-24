import { Link } from 'react-router-dom';
import { appName } from '../../api/config';
import { readBootData } from '../../lib/bootData';

/**
 * Shared Pebbles logo used across navbar, footer, and auth screens.
 * Falls back to the existing text mark when no admin logo is configured.
 */
export default function SiteLogo({ logo, variant = 'header', className = '', linkTo = '/' }) {
    const bootLogoUrl = readBootData()?.logoUrl ?? null;
    const resolvedLogo = logo ?? (bootLogoUrl ? { url: bootLogoUrl } : null);
    const alt = `${appName} logo`;
    const linked = linkTo !== null && linkTo !== false;

    const shellClass = [
        variant === 'header' ? 'site-logo-shell site-logo-shell--header' : '',
        variant === 'footer' ? 'site-logo-shell site-logo-shell--footer' : '',
        variant === 'auth' ? 'site-logo-shell site-logo-shell--auth' : '',
    ]
        .filter(Boolean)
        .join(' ');

    const content = (() => {
        if (resolvedLogo?.url) {
            if (variant === 'footer') {
                return (
                    <img
                        src={resolvedLogo.url}
                        alt={alt}
                        width={200}
                        height={48}
                        className="site-logo-image site-logo-image--footer"
                    />
                );
            }

            if (variant === 'auth') {
                return (
                    <img
                        src={resolvedLogo.url}
                        alt={alt}
                        width={220}
                        height={56}
                        className="site-logo-image site-logo-image--auth"
                    />
                );
            }

            return (
                <img
                    src={resolvedLogo.url}
                    alt={alt}
                    width={180}
                    height={48}
                    className="site-logo-image site-logo-image--header"
                />
            );
        }

        if (variant === 'footer') {
            return <p className="font-display text-2xl font-semibold">{appName}</p>;
        }

        if (variant === 'auth') {
            return <p className="text-sm font-medium tracking-wide text-amber-900/80 uppercase">{appName}</p>;
        }

        return (
            <>
                <span className="flex size-9 items-center justify-center rounded-full bg-gradient-to-br from-brand-green-light to-brand-green text-sm font-bold text-white shadow-md shadow-brand-green/30 transition duration-300 group-hover:scale-105 group-hover:shadow-lg group-hover:shadow-brand-green/40">
                    {String(appName).slice(0, 1).toUpperCase()}
                </span>
                <span className="font-display text-xl font-semibold tracking-tight text-brand-green transition group-hover:text-brand-green-light">
                    {appName}
                </span>
            </>
        );
    })();

    const wrapperClass = [
        variant === 'header' ? 'group flex shrink-0 items-center gap-2' : '',
        variant === 'footer' ? 'inline-flex items-center' : '',
        variant === 'auth' ? 'inline-flex items-center justify-center' : '',
        className,
    ]
        .filter(Boolean)
        .join(' ');

    if (!linked) {
        return (
            <div className={[wrapperClass, shellClass].filter(Boolean).join(' ')}>
                {content}
            </div>
        );
    }

    return (
        <Link to={linkTo} className={[wrapperClass, shellClass].filter(Boolean).join(' ')}>
            {content}
        </Link>
    );
}

import { Link } from 'react-router-dom';
import { appName } from '../../api/config';
import SiteLogo from './SiteLogo';

export default function StorefrontFooter({ branch, logo = null }) {
    const year = new Date().getFullYear();

    return (
        <footer className="mt-auto bg-gradient-to-br from-brand-green to-[#0f4d2a] text-white">
            <div className="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-4">
                <div className="space-y-4 lg:col-span-2">
                    <SiteLogo logo={logo} variant="footer" linkTo="/" />
                    <p className="max-w-md text-sm leading-relaxed text-white/80">
                        Ice cream, shakes, faluda, and kulfi — order takeaway or delivery from your nearest Pebbles
                        branch.
                    </p>
                    <Link to="/order" className="btn-orange btn-shine px-5 py-2.5 text-sm">
                        Order Online
                        <span className="btn-arrow" aria-hidden="true">
                            →
                        </span>
                    </Link>
                </div>

                <div className="space-y-3">
                    <p className="text-xs font-semibold tracking-widest text-brand-orange-soft uppercase">Explore</p>
                    <ul className="space-y-2 text-sm text-white/85">
                        {[
                            ['/', 'Home'],
                            ['/menu', 'Menu'],
                            ['/about', 'About'],
                            ['/gallery', 'Gallery'],
                            ['/contact', 'Contact'],
                            ['/reviews', 'Reviews'],
                            ['/offers', 'Offers'],
                            ['/locations', 'Locations'],
                            ['/order', 'Order Online'],
                            ['/orders', 'My Orders'],
                            ['/login', 'Sign in'],
                        ].map(([to, label]) => (
                            <li key={to}>
                                <Link
                                    to={to}
                                    className="group inline-flex items-center gap-1 transition hover:text-brand-orange-soft"
                                >
                                    {label}
                                    <span
                                        className="inline-block translate-x-0 opacity-0 transition group-hover:translate-x-1 group-hover:opacity-100"
                                        aria-hidden="true"
                                    >
                                        →
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="space-y-3">
                    <p className="text-xs font-semibold tracking-widest text-brand-orange-soft uppercase">Visit</p>
                    {branch ? (
                        <ul className="space-y-2 text-sm text-white/85">
                            <li className="font-medium text-white">{branch.name}</li>
                            {branch.address ? <li>{branch.address}</li> : null}
                            {branch.phone ? (
                                <li>
                                    <a href={`tel:${branch.phone}`} className="transition hover:text-brand-orange-soft">
                                        {branch.phone}
                                    </a>
                                </li>
                            ) : null}
                            {branch.opens_at && branch.closes_at ? (
                                <li>
                                    {String(branch.opens_at).slice(0, 5)} – {String(branch.closes_at).slice(0, 5)}
                                </li>
                            ) : null}
                        </ul>
                    ) : (
                        <p className="text-sm text-white/70">Select a branch above to see local details.</p>
                    )}
                </div>
            </div>
            <div className="border-t border-white/10">
                <p className="mx-auto max-w-6xl px-4 py-4 text-xs text-white/60 sm:px-6">
                    © {year} {appName}. All rights reserved.
                </p>
            </div>
        </footer>
    );
}

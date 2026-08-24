import { useEffect, useState } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import BranchSelector from './BranchSelector';
import ProfileMenu from '../account/ProfileMenu';
import NavbarCartLink from './NavbarCartLink';
import NavbarMyOrdersLink from './NavbarMyOrdersLink';
import SiteLogo from './SiteLogo';

const primaryLinks = [
    { to: '/', label: 'Home', end: true },
    { to: '/menu', label: 'Menu' },
    { to: '/about', label: 'About' },
    { to: '/gallery', label: 'Gallery' },
    { to: '/contact', label: 'Contact' },
];

export default function Header({ branches, selectedBranch, onBranchChange, branchesLoading, logo = null }) {
    const location = useLocation();
    const [menuOpen, setMenuOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        setMenuOpen(false);
    }, [location.pathname]);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 12);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const desktopLink = ({ isActive }) => ['nav-link-premium', isActive ? 'is-active' : ''].filter(Boolean).join(' ');

    const mobileLink = ({ isActive }) =>
        [
            'rounded-xl px-3 py-2.5 text-sm font-semibold transition duration-200',
            isActive
                ? 'bg-brand-green text-white shadow-md shadow-brand-green/25'
                : 'text-brand-ink/80 hover:bg-brand-orange/10 hover:text-brand-orange',
        ].join(' ');

    return (
        <header
            className={[
                'sticky top-0 z-30 border-b transition duration-300',
                scrolled
                    ? 'border-brand-green/10 bg-white/90 shadow-lg shadow-brand-green/5 backdrop-blur-md'
                    : 'border-transparent bg-brand-cream/85 backdrop-blur-sm',
            ].join(' ')}
        >
            <div className="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-3 sm:px-6">
                <div className="flex items-center justify-between gap-3">
                    <SiteLogo logo={logo} variant="header" />

                    <div className="flex items-center gap-2">
                        <nav className="hidden items-center gap-0.5 md:flex" aria-label="Primary">
                            {primaryLinks.map(({ to, label, end }) => (
                                <NavLink key={to} to={to} end={end} className={desktopLink}>
                                    {label}
                                </NavLink>
                            ))}
                            <NavbarMyOrdersLink variant="desktop" />
                            <ProfileMenu variant="desktop" />
                            <Link to="/order" className="btn-orange btn-shine nav-order-cta ml-2 px-4 py-2 text-sm">
                                Order Online
                                <span className="btn-arrow" aria-hidden="true">
                                    →
                                </span>
                            </Link>
                        </nav>

                        <NavbarCartLink />

                        <button
                            type="button"
                            className="inline-flex size-10 items-center justify-center rounded-xl border border-brand-green/20 text-brand-green transition duration-200 hover:-translate-y-0.5 hover:border-brand-orange/40 hover:text-brand-orange hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-green md:hidden"
                            aria-expanded={menuOpen}
                            aria-controls="mobile-nav"
                            aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                            onClick={() => setMenuOpen((open) => !open)}
                        >
                            <span className="sr-only">Menu</span>
                            <span className="flex flex-col gap-1.5">
                                <span
                                    className={`block h-0.5 w-5 bg-current transition ${menuOpen ? 'translate-y-2 rotate-45' : ''}`}
                                />
                                <span className={`block h-0.5 w-5 bg-current transition ${menuOpen ? 'opacity-0' : ''}`} />
                                <span
                                    className={`block h-0.5 w-5 bg-current transition ${menuOpen ? '-translate-y-2 -rotate-45' : ''}`}
                                />
                            </span>
                        </button>
                    </div>
                </div>

                {menuOpen ? (
                    <nav
                        id="mobile-nav"
                        className="flex flex-col gap-1 rounded-2xl border border-brand-green/10 bg-white/95 p-3 shadow-xl shadow-brand-green/10 backdrop-blur-md md:hidden"
                        aria-label="Mobile"
                    >
                        {primaryLinks.map(({ to, label, end }) => (
                            <NavLink key={to} to={to} end={end} className={mobileLink}>
                                {label}
                            </NavLink>
                        ))}
                        <NavbarMyOrdersLink variant="mobile" />
                        <ProfileMenu variant="mobile" />
                        <Link to="/order" className="btn-orange btn-shine nav-order-cta mt-2 px-4 py-2.5 text-center text-sm">
                            Order Online
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                    </nav>
                ) : null}

                <BranchSelector
                    branches={branches}
                    selectedBranch={selectedBranch}
                    onBranchChange={onBranchChange}
                    loading={branchesLoading}
                />
            </div>
        </header>
    );
}

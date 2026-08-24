import { useEffect, useRef, useState } from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useCart } from '../../contexts/CartContext';
import { playCartAddSound } from '../../lib/cartAddAnimation';

const EXIT_DURATION_MS = 250;
const ENTER_DURATION_MS = 320;

function CartIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path
                d="M3 3h2l1.4 10.2a2 2 0 0 0 2 1.8h9.2a2 2 0 0 0 2-1.7l1.1-6.5H6.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <circle cx="10" cy="19.5" r="1.2" fill="currentColor" stroke="none" />
            <circle cx="17" cy="19.5" r="1.2" fill="currentColor" stroke="none" />
        </svg>
    );
}

export default function NavbarCartLink() {
    const { isAuthenticated } = useAuth();
    const { itemCount, loading } = useCart();
    const previousCountRef = useRef(itemCount);
    const countElementRef = useRef(null);
    const hasHydratedRef = useRef(false);
    const [visible, setVisible] = useState(false);
    const [isEntering, setIsEntering] = useState(false);
    const [isExiting, setIsExiting] = useState(false);
    const [badgeEnter, setBadgeEnter] = useState(false);
    const [displayCount, setDisplayCount] = useState(0);

    useEffect(() => {
        if (!isAuthenticated || loading) {
            return;
        }

        if (!hasHydratedRef.current) {
            hasHydratedRef.current = true;
            setVisible(itemCount > 0);
            setDisplayCount(itemCount);
            previousCountRef.current = itemCount;
            return;
        }

        const previousCount = previousCountRef.current;

        if (itemCount === previousCount) {
            return;
        }

        if (itemCount > 0) {
            setDisplayCount(itemCount);
        }

        if (itemCount > 0 && previousCount === 0) {
            setVisible(true);
            setIsExiting(false);
            setIsEntering(true);
            setBadgeEnter(true);

            const enterTimer = window.setTimeout(() => {
                setIsEntering(false);
            }, ENTER_DURATION_MS);

            previousCountRef.current = itemCount;

            return () => {
                window.clearTimeout(enterTimer);
            };
        }

        if (itemCount === 0 && previousCount > 0) {
            setBadgeEnter(false);
            setIsEntering(false);
            setIsExiting(true);

            const exitTimer = window.setTimeout(() => {
                setVisible(false);
                setIsExiting(false);
                setDisplayCount(0);
            }, EXIT_DURATION_MS);

            previousCountRef.current = itemCount;

            return () => {
                window.clearTimeout(exitTimer);
            };
        }

        if (itemCount === 0) {
            setBadgeEnter(false);
        }

        if (itemCount > previousCount && previousCount >= 1) {
            playCartAddSound();
        }

        if (itemCount > previousCount && countElementRef.current) {
            countElementRef.current.classList.remove('nav-cart-count-pop');
            void countElementRef.current.offsetWidth;
            countElementRef.current.classList.add('nav-cart-count-pop');
        }

        previousCountRef.current = itemCount;

        return undefined;
    }, [isAuthenticated, itemCount, loading]);

    if (!isAuthenticated || !visible) {
        return null;
    }

    const linkClass = ({ isActive }) =>
        [
            'nav-link-cart relative inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-brand-green/20 text-brand-green transition duration-200 hover:-translate-y-0.5 hover:border-brand-orange/40 hover:text-brand-orange hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-green',
            'has-items',
            isEntering ? 'nav-cart-link-enter' : '',
            isExiting ? 'nav-cart-link-exit' : '',
            isActive ? 'is-active border-brand-orange/40 text-brand-orange' : '',
        ]
            .filter(Boolean)
            .join(' ');

    return (
        <NavLink
            to="/cart"
            data-cart-target="navbar"
            aria-label={`Cart, ${displayCount} items`}
            className={linkClass}
        >
            <CartIcon />
            <span
                ref={countElementRef}
                className={[
                    'nav-cart-count absolute -top-1.5 -right-1.5 min-w-[1.15rem] rounded-full bg-brand-orange px-1 py-0.5 text-center text-[10px] font-bold leading-none text-white shadow-sm shadow-brand-orange/30',
                    badgeEnter ? 'nav-cart-count-enter' : '',
                ].join(' ')}
            >
                {displayCount}
            </span>
        </NavLink>
    );
}

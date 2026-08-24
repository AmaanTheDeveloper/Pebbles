import { useEffect, useRef, useState } from 'react';
import { NavLink } from 'react-router-dom';
import { useActiveOrders } from '../../contexts/ActiveOrdersContext';
import { useAuth } from '../../contexts/AuthContext';

const EXIT_DURATION_MS = 240;
const ENTER_DURATION_MS = 280;

export default function NavbarMyOrdersLink({ variant = 'desktop' }) {
    const { isAuthenticated } = useAuth();
    const { hasActiveOrders, hydrated } = useActiveOrders();
    const previousValueRef = useRef(hasActiveOrders);
    const hasHydratedRef = useRef(false);
    const [visible, setVisible] = useState(false);
    const [isEntering, setIsEntering] = useState(false);
    const [isExiting, setIsExiting] = useState(false);

    useEffect(() => {
        if (!isAuthenticated || !hydrated) {
            return;
        }

        if (!hasHydratedRef.current) {
            hasHydratedRef.current = true;
            setVisible(hasActiveOrders);
            previousValueRef.current = hasActiveOrders;
            return;
        }

        const previousValue = previousValueRef.current;

        if (hasActiveOrders === previousValue) {
            return;
        }

        if (hasActiveOrders) {
            setVisible(true);
            setIsExiting(false);
            setIsEntering(true);

            const enterTimer = window.setTimeout(() => {
                setIsEntering(false);
            }, ENTER_DURATION_MS);

            previousValueRef.current = hasActiveOrders;

            return () => {
                window.clearTimeout(enterTimer);
            };
        }

        setIsEntering(false);
        setIsExiting(true);

        const exitTimer = window.setTimeout(() => {
            setVisible(false);
            setIsExiting(false);
        }, EXIT_DURATION_MS);

        previousValueRef.current = hasActiveOrders;

        return () => {
            window.clearTimeout(exitTimer);
        };
    }, [hasActiveOrders, hydrated, isAuthenticated]);

    if (!isAuthenticated || !visible) {
        return null;
    }

    if (variant === 'mobile') {
        return (
            <NavLink
                to="/orders"
                className={({ isActive }) =>
                    [
                        'rounded-xl px-3 py-2.5 text-sm font-semibold transition duration-200',
                        isEntering ? 'nav-my-orders-enter' : '',
                        isExiting ? 'nav-my-orders-exit' : '',
                        isActive
                            ? 'bg-brand-green text-white shadow-md shadow-brand-green/25'
                            : 'text-brand-ink/80 hover:bg-brand-orange/10 hover:text-brand-orange',
                    ]
                        .filter(Boolean)
                        .join(' ')
                }
            >
                My Orders
            </NavLink>
        );
    }

    return (
        <NavLink
            to="/orders"
            className={({ isActive }) =>
                [
                    'nav-link-premium',
                    isEntering ? 'nav-my-orders-enter' : '',
                    isExiting ? 'nav-my-orders-exit' : '',
                    isActive ? 'is-active' : '',
                ]
                    .filter(Boolean)
                    .join(' ')
            }
        >
            My Orders
        </NavLink>
    );
}

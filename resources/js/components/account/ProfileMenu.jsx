import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import UserAvatar from './UserAvatar';

export default function ProfileMenu({ variant = 'desktop' }) {
    const { user, logout, canAccessAdmin } = useAuth();
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);
    const rootRef = useRef(null);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onPointerDown = (event) => {
            if (!rootRef.current?.contains(event.target)) {
                setOpen(false);
            }
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!user) {
        return (
            <div className={variant === 'mobile' ? 'mt-2 flex flex-col gap-2' : 'hidden items-center gap-2 md:flex'}>
                <Link
                    to="/login"
                    className="rounded-lg px-3 py-2 text-sm font-semibold text-brand-green hover:bg-brand-green/10"
                >
                    Login
                </Link>
                <Link to="/register" className="btn-green px-3 py-2 text-sm">
                    Register
                </Link>
            </div>
        );
    }

    const handleLogout = async () => {
        setLoggingOut(true);

        try {
            await logout();
            navigate('/', { replace: true });
        } catch {
            setLoggingOut(false);
        }
    };

    return (
        <div ref={rootRef} className={variant === 'mobile' ? 'relative mt-2' : 'relative hidden md:block'}>
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="inline-flex items-center gap-2 rounded-xl border border-brand-green/15 bg-white/80 px-2 py-1.5 text-left shadow-sm transition hover:border-brand-orange/30 hover:shadow-md"
                aria-expanded={open}
                aria-haspopup="menu"
            >
                <UserAvatar user={user} size="sm" />
                <span className="max-w-[120px] truncate text-sm font-semibold text-brand-ink">{user.name}</span>
                <span className="text-xs text-brand-muted" aria-hidden="true">
                    ▾
                </span>
            </button>

            {open ? (
                <div
                    role="menu"
                    className="absolute right-0 z-40 mt-2 w-64 origin-top-right rounded-2xl border border-brand-green/10 bg-white p-2 shadow-xl shadow-brand-green/10"
                >
                    <div className="flex items-center gap-3 rounded-xl bg-brand-cream/80 px-3 py-3">
                        <UserAvatar user={user} />
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-brand-ink">{user.name}</p>
                            <p className="truncate text-xs text-brand-muted">{user.email}</p>
                        </div>
                    </div>
                    <div className="my-2 border-t border-stone-100" />
                    <Link
                        to="/profile"
                        role="menuitem"
                        className="block rounded-lg px-3 py-2 text-sm font-medium text-brand-ink hover:bg-brand-green/10"
                        onClick={() => setOpen(false)}
                    >
                        My Profile
                    </Link>
                    <Link
                        to="/orders"
                        role="menuitem"
                        className="block rounded-lg px-3 py-2 text-sm font-medium text-brand-ink hover:bg-brand-green/10"
                        onClick={() => setOpen(false)}
                    >
                        My Orders
                    </Link>
                    {canAccessAdmin ? (
                        <Link
                            to="/admin/dashboard"
                            role="menuitem"
                            className="block rounded-lg px-3 py-2 text-sm font-medium text-brand-ink hover:bg-brand-green/10"
                            onClick={() => setOpen(false)}
                        >
                            Admin Dashboard
                        </Link>
                    ) : null}
                    <div className="my-2 border-t border-stone-100" />
                    <button
                        type="button"
                        role="menuitem"
                        disabled={loggingOut}
                        onClick={handleLogout}
                        className="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-60"
                    >
                        {loggingOut ? 'Signing out…' : 'Logout'}
                    </button>
                </div>
            ) : null}
        </div>
    );
}

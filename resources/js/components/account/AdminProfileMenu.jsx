import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import UserAvatar from './UserAvatar';

export default function AdminProfileMenu() {
    const { user, logout } = useAuth();
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
        return null;
    }

    const handleLogout = async () => {
        setLoggingOut(true);

        try {
            await logout();
            navigate('/login', { replace: true });
        } catch {
            setLoggingOut(false);
        }
    };

    return (
        <div ref={rootRef} className="relative">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="inline-flex items-center gap-2 rounded-xl border border-brand-green/10 bg-white/95 px-2 py-1.5 shadow-sm transition duration-200 hover:-translate-y-px hover:border-brand-orange/25 hover:shadow-md"
                aria-expanded={open}
                aria-haspopup="menu"
            >
                <UserAvatar user={user} size="sm" />
                <span className="hidden max-w-[120px] truncate text-sm font-semibold text-brand-ink sm:inline">
                    {user.name}
                </span>
                <svg className="hidden size-4 text-brand-muted sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            {open ? (
                <div className="admin-dropdown absolute right-0 z-40 mt-2 w-60 p-2" role="menu">
                    <div className="rounded-xl bg-brand-green/5 px-3 py-2.5">
                        <p className="truncate text-sm font-semibold text-brand-ink">{user.name}</p>
                        <p className="truncate text-xs text-brand-muted">{user.email}</p>
                        {user.access_label ? (
                            <p className="mt-1 text-[11px] font-semibold uppercase tracking-wide text-brand-green">
                                {user.access_label}
                            </p>
                        ) : null}
                    </div>
                    <div className="my-2 border-t border-brand-green/10" />
                    <Link
                        to="/admin/profile"
                        className="admin-dropdown-item"
                        onClick={() => setOpen(false)}
                    >
                        Profile
                    </Link>
                    <button
                        type="button"
                        disabled={loggingOut}
                        onClick={handleLogout}
                        className="admin-dropdown-item admin-dropdown-item-danger"
                    >
                        {loggingOut ? 'Signing out…' : 'Logout'}
                    </button>
                </div>
            ) : null}
        </div>
    );
}

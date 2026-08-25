import { useState } from 'react';
import { Link, Navigate, useOutletContext } from 'react-router-dom';
import SiteLogo from '../components/storefront/SiteLogo';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { useAuth } from '../contexts/AuthContext';
import { resendVerificationEmail } from '../lib/emailVerification';
import { canAccessAdminRole, postLoginPath } from '../lib/roles';

export default function VerifyEmailPromptPage() {
    const { logo } = useOutletContext() ?? {};
    const { user, initializing, isAuthenticated, refreshUser } = useAuth();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: '/verify-email' }} />;
    }

    if (canAccessAdminRole(user?.role) || user?.email_verified_at) {
        return <Navigate to={postLoginPath(user)} replace />;
    }

    const handleResend = async () => {
        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const result = await resendVerificationEmail();
            setSuccess(result.message);

            if (result.alreadyVerified) {
                await refreshUser();
            }
        } catch (err) {
            setError(err);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-md space-y-6">
            <div className="space-y-2 text-center">
                <SiteLogo logo={logo} variant="auth" linkTo="/" />
                <h1 className="text-2xl font-semibold text-stone-900">Verify your email</h1>
                <p className="text-sm text-stone-600">
                    We sent a verification link to <span className="font-medium text-stone-800">{user?.email}</span>.
                    Confirm your email before using cart, checkout, or orders.
                </p>
            </div>

            {success ? (
                <div className="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200" role="status">
                    {success}
                </div>
            ) : null}

            {error?.message ? <ErrorState message={error.message} /> : null}

            <div className="space-y-4 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                <p className="text-sm text-stone-600">
                    Didn&apos;t get the email? Check spam, or request another link below.
                </p>
                <button
                    type="button"
                    disabled={loading}
                    onClick={handleResend}
                    className="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {loading ? 'Sending…' : 'Resend verification email'}
                </button>
            </div>

            <p className="text-center text-sm text-stone-600">
                <Link to="/menu" className="font-medium text-amber-900 hover:underline">
                    Browse the menu
                </Link>
                {' · '}
                <Link to="/profile" className="font-medium text-amber-900 hover:underline">
                    Profile
                </Link>
            </p>
        </div>
    );
}

import { useMemo, useState } from 'react';
import { Link, Navigate, useNavigate, useOutletContext, useSearchParams } from 'react-router-dom';
import api from '../api/client';
import SiteLogo from '../components/storefront/SiteLogo';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { useAuth } from '../contexts/AuthContext';
import { firstFieldError, normalizeApiError } from '../lib/errors';
import { postLoginPath } from '../lib/roles';

export default function ResetPasswordPage() {
    const { logo } = useOutletContext() ?? {};
    const { isAuthenticated, initializing, user } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const token = searchParams.get('token') ?? '';
    const emailFromQuery = searchParams.get('email') ?? '';

    const [email, setEmail] = useState(emailFromQuery);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const missingToken = useMemo(() => !token, [token]);

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (isAuthenticated) {
        return <Navigate to={postLoginPath(user)} replace />;
    }

    const fieldError = (field) => firstFieldError(error?.errors ?? {}, field);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const { data } = await api.post('/api/reset-password', {
                token,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            setSuccess(data.message || 'Your password has been reset.');
            window.setTimeout(() => navigate('/login', { replace: true }), 1200);
        } catch (err) {
            setError(normalizeApiError(err));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-md space-y-6">
            <div className="space-y-2 text-center">
                <SiteLogo logo={logo} variant="auth" linkTo="/" />
                <h1 className="text-2xl font-semibold text-stone-900">Choose a new password</h1>
                <p className="text-sm text-stone-600">Enter a strong password for your Pebbles account.</p>
            </div>

            {missingToken ? (
                <ErrorState message="This reset link is incomplete or invalid. Request a new password reset email." />
            ) : null}

            {success ? (
                <div className="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200" role="status">
                    {success}
                </div>
            ) : null}

            {(error?.message && !fieldError('email') && !fieldError('password') && !fieldError('token')) ? (
                <ErrorState message={error.message} />
            ) : null}

            {!missingToken ? (
                <form onSubmit={handleSubmit} className="space-y-4 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                    <label className="block space-y-1 text-sm">
                        <span className="font-medium text-stone-700">Email</span>
                        <input
                            type="email"
                            required
                            autoComplete="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        />
                        {fieldError('email') ? <span className="text-xs text-red-600">{fieldError('email')}</span> : null}
                    </label>
                    <label className="block space-y-1 text-sm">
                        <span className="font-medium text-stone-700">New password</span>
                        <input
                            type="password"
                            required
                            autoComplete="new-password"
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        />
                        {fieldError('password') ? (
                            <span className="text-xs text-red-600">{fieldError('password')}</span>
                        ) : null}
                    </label>
                    <label className="block space-y-1 text-sm">
                        <span className="font-medium text-stone-700">Confirm password</span>
                        <input
                            type="password"
                            required
                            autoComplete="new-password"
                            value={passwordConfirmation}
                            onChange={(event) => setPasswordConfirmation(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        />
                    </label>
                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                    >
                        {loading ? 'Saving…' : 'Reset password'}
                    </button>
                </form>
            ) : null}

            <p className="text-center text-sm text-stone-600">
                <Link to="/forgot-password" className="font-medium text-amber-900 hover:underline">
                    Request a new reset link
                </Link>
                {' · '}
                <Link to="/login" className="font-medium text-amber-900 hover:underline">
                    Sign in
                </Link>
            </p>
        </div>
    );
}

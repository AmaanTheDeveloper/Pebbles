import { useState } from 'react';
import { Link, Navigate, useOutletContext } from 'react-router-dom';
import api from '../api/client';
import SiteLogo from '../components/storefront/SiteLogo';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import SeoHead from '../components/seo/SeoHead';
import { useAuth } from '../contexts/AuthContext';
import { firstFieldError, normalizeApiError } from '../lib/errors';
import { postLoginPath } from '../lib/roles';

export default function ForgotPasswordPage() {
    const { logo } = useOutletContext() ?? {};
    const { isAuthenticated, initializing, user } = useAuth();
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

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
            const { data } = await api.post('/api/forgot-password', { email });
            setSuccess(data.message || 'If that email is registered, we sent reset instructions.');
        } catch (err) {
            setError(normalizeApiError(err));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-md space-y-6">
            <SeoHead
                title="Forgot password"
                description="Reset your Pebbles account password."
                path="/forgot-password"
                noindex
            />
            <div className="space-y-2 text-center">
                <SiteLogo logo={logo} variant="auth" linkTo="/" />
                <h1 className="text-2xl font-semibold text-stone-900">Forgot your password?</h1>
                <p className="text-sm text-stone-600">
                    Enter your email and we&apos;ll send reset instructions if an account exists.
                </p>
            </div>

            {success ? (
                <div className="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200" role="status">
                    {success}
                </div>
            ) : null}

            {(error?.message && !fieldError('email')) ? <ErrorState message={error.message} /> : null}

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
                <button
                    type="submit"
                    disabled={loading}
                    className="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {loading ? 'Sending…' : 'Send reset link'}
                </button>
            </form>

            <p className="text-center text-sm text-stone-600">
                <Link to="/login" className="font-medium text-amber-900 hover:underline">
                    Back to sign in
                </Link>
            </p>
        </div>
    );
}

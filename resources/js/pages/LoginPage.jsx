import { useState } from 'react';
import { Link, Navigate, useLocation, useNavigate, useOutletContext } from 'react-router-dom';
import SiteLogo from '../components/storefront/SiteLogo';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { useAuth } from '../contexts/AuthContext';
import { firstFieldError } from '../lib/errors';
import { postLoginPath } from '../lib/roles';

export default function LoginPage() {
    const { logo } = useOutletContext() ?? {};
    const { login, loading, error, clearError, isAuthenticated, initializing, user } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [formError, setFormError] = useState(null);

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (isAuthenticated) {
        const from = location.state?.from;

        if (from) {
            return <Navigate to={from} replace />;
        }

        return <Navigate to={postLoginPath(user)} replace />;
    }

    const handleSubmit = async (event) => {
        event.preventDefault();
        clearError();
        setFormError(null);

        try {
            const nextUser = await login({ email, password });
            const from = location.state?.from;

            if (from) {
                navigate(from, { replace: true });
            } else {
                navigate(postLoginPath(nextUser, '/cart'), { replace: true });
            }
        } catch (err) {
            setFormError(err.message || 'Unable to sign in.');
        }
    };

    const fieldError = (field) => firstFieldError(error?.errors ?? {}, field);

    return (
        <div className="mx-auto max-w-md space-y-6">
            <div className="space-y-2 text-center">
                <SiteLogo logo={logo} variant="auth" linkTo="/" />
                <h1 className="text-2xl font-semibold text-stone-900">Sign in to continue</h1>
                <p className="text-sm text-stone-600">Cart and checkout require an account.</p>
            </div>

            {(formError || error?.message) && !fieldError('email') && !fieldError('password') ? (
                <ErrorState message={formError || error.message} />
            ) : null}

            <form onSubmit={handleSubmit} className="space-y-4 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                <label className="block space-y-1 text-sm">
                    <span className="font-medium text-stone-700">Email</span>
                    <input
                        type="email"
                        required
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        className="w-full rounded-lg border border-stone-300 px-3 py-2"
                    />
                    {fieldError('email') ? <span className="text-xs text-red-600">{fieldError('email')}</span> : null}
                </label>
                <label className="block space-y-1 text-sm">
                    <span className="font-medium text-stone-700">Password</span>
                    <input
                        type="password"
                        required
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        className="w-full rounded-lg border border-stone-300 px-3 py-2"
                    />
                    {fieldError('password') ? (
                        <span className="text-xs text-red-600">{fieldError('password')}</span>
                    ) : null}
                </label>
                <button
                    type="submit"
                    disabled={loading}
                    className="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {loading ? 'Signing in…' : 'Sign in'}
                </button>
            </form>

            <p className="text-center text-sm text-stone-600">
                Don&apos;t have an account?{' '}
                <Link to="/register" className="font-medium text-amber-900 hover:underline">
                    Create account
                </Link>
            </p>

            <p className="text-center text-sm text-stone-600">
                <Link to="/menu" className="font-medium text-amber-900 hover:underline">
                    Back to menu
                </Link>
            </p>
        </div>
    );
}

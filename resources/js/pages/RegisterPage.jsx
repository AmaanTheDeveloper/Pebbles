import { useState } from 'react';
import { Link, Navigate, useNavigate, useOutletContext } from 'react-router-dom';
import SiteLogo from '../components/storefront/SiteLogo';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import SeoHead from '../components/seo/SeoHead';
import { useAuth } from '../contexts/AuthContext';
import { useEmailVerificationReminder } from '../contexts/EmailVerificationReminderContext';
import { firstFieldError } from '../lib/errors';
import { postLoginPath } from '../lib/roles';

export default function RegisterPage() {
    const { logo } = useOutletContext() ?? {};
    const { register, loading, error, clearError, isAuthenticated, initializing, user } = useAuth();
    const { showReminder } = useEmailVerificationReminder();
    const navigate = useNavigate();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [formError, setFormError] = useState(null);

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (isAuthenticated) {
        return <Navigate to={postLoginPath(user)} replace />;
    }

    const handleSubmit = async (event) => {
        event.preventDefault();
        clearError();
        setFormError(null);

        try {
            const nextUser = await register({
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            showReminder(nextUser, { reason: 'register' });
            navigate('/menu', { replace: true });
        } catch (err) {
            setFormError(err.message || 'Unable to create an account.');
        }
    };

    const fieldError = (field) => firstFieldError(error?.errors ?? {}, field);

    return (
        <div className="mx-auto max-w-md space-y-6">
            <SeoHead
                title="Create account"
                description="Create a Pebbles account to save your cart and place takeaway or delivery orders."
                path="/register"
            />
            <div className="space-y-2 text-center">
                <SiteLogo logo={logo} variant="auth" linkTo="/" />
                <h1 className="text-2xl font-semibold text-stone-900">Create an account</h1>
                <p className="text-sm text-stone-600">Register to save your cart and place orders.</p>
            </div>

            {(formError || error?.message) &&
            !fieldError('name') &&
            !fieldError('email') &&
            !fieldError('password') ? (
                <ErrorState message={formError || error.message} />
            ) : null}

            <form onSubmit={handleSubmit} className="space-y-4 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                <label className="block space-y-1 text-sm">
                    <span className="font-medium text-stone-700">Name</span>
                    <input
                        type="text"
                        required
                        autoComplete="name"
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        className="w-full rounded-lg border border-stone-300 px-3 py-2"
                    />
                    {fieldError('name') ? <span className="text-xs text-red-600">{fieldError('name')}</span> : null}
                </label>
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
                    <span className="font-medium text-stone-700">Password</span>
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
                    {loading ? 'Creating account…' : 'Create account'}
                </button>
            </form>

            <p className="text-center text-sm text-stone-600">
                Already have an account?{' '}
                <Link to="/login" className="font-medium text-amber-900 hover:underline">
                    Sign in
                </Link>
            </p>
        </div>
    );
}

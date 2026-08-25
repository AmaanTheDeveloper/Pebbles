import { useEffect, useState } from 'react';
import { Link, Navigate, useNavigate, useOutletContext, useParams, useSearchParams } from 'react-router-dom';
import api from '../api/client';
import SiteLogo from '../components/storefront/SiteLogo';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { useAuth } from '../contexts/AuthContext';
import { normalizeApiError } from '../lib/errors';
import { postLoginPath } from '../lib/roles';

export default function VerifyEmailPage() {
    const { logo } = useOutletContext() ?? {};
    const { id, hash } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const { isAuthenticated, initializing, refreshUser } = useAuth();
    const [error, setError] = useState(null);
    const [verifying, setVerifying] = useState(true);

    const expires = searchParams.get('expires');
    const signature = searchParams.get('signature');
    const returnPath = `/verify-email/${id}/${hash}?${searchParams.toString()}`;

    useEffect(() => {
        if (initializing || !isAuthenticated) {
            return undefined;
        }

        let cancelled = false;

        const verify = async () => {
            setVerifying(true);
            setError(null);

            if (!id || !hash || !expires || !signature) {
                if (!cancelled) {
                    setError({ message: 'This verification link is incomplete or invalid.' });
                    setVerifying(false);
                }
                return;
            }

            try {
                const query = new URLSearchParams({ expires, signature }).toString();
                await api.get(`/api/email/verify/${id}/${hash}?${query}`);
                const nextUser = await refreshUser();

                if (!cancelled) {
                    navigate(postLoginPath(nextUser, '/menu'), { replace: true });
                }
            } catch (err) {
                if (!cancelled) {
                    setError(normalizeApiError(err));
                    setVerifying(false);
                }
            }
        };

        verify();

        return () => {
            cancelled = true;
        };
    }, [expires, hash, id, initializing, isAuthenticated, navigate, refreshUser, signature]);

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: returnPath }} />;
    }

    return (
        <div className="mx-auto max-w-md space-y-6">
            <div className="space-y-2 text-center">
                <SiteLogo logo={logo} variant="auth" linkTo="/" />
                <h1 className="text-2xl font-semibold text-stone-900">Email verification</h1>
                <p className="text-sm text-stone-600">
                    {verifying ? 'Confirming your email address…' : 'We could not verify this link.'}
                </p>
            </div>

            {verifying ? <LoadingState label="Verifying email…" /> : null}

            {!verifying && error?.message ? <ErrorState message={error.message} /> : null}

            {!verifying ? (
                <p className="text-center text-sm text-stone-600">
                    <Link to="/verify-email" className="font-medium text-amber-900 hover:underline">
                        Request a new verification email
                    </Link>
                    {' · '}
                    <Link to="/menu" className="font-medium text-amber-900 hover:underline">
                        Back to menu
                    </Link>
                </p>
            ) : null}
        </div>
    );
}

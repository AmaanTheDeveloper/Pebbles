import { Navigate, useLocation } from 'react-router-dom';
import LoadingState from '../storefront/LoadingState';
import { useAuth } from '../../contexts/AuthContext';

/** Allows Super Admin and Manager into the shared Admin Dashboard. */
export default function RequireAdmin({ children }) {
    const { isAuthenticated, canAccessAdmin, initializing } = useAuth();
    const location = useLocation();

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    if (!canAccessAdmin) {
        return <Navigate to="/" replace />;
    }

    return children;
}

/** @deprecated Use RequireAdmin */
export function RequireSuperadmin({ children }) {
    return <RequireAdmin>{children}</RequireAdmin>;
}

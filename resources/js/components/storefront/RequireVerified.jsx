import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { canAccessAdminRole } from '../../lib/roles';
import LoadingState from './LoadingState';

export default function RequireVerified({ children }) {
    const { user, initializing } = useAuth();
    const location = useLocation();

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (canAccessAdminRole(user?.role)) {
        return children;
    }

    if (!user?.email_verified_at) {
        return <Navigate to="/verify-email" replace state={{ from: location.pathname + location.search }} />;
    }

    return children;
}

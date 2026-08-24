import { Navigate, useLocation } from 'react-router-dom';
import LoadingState from '../storefront/LoadingState';
import { useAuth } from '../../contexts/AuthContext';

/** Super Admin only routes (Users, Settings, CMS, etc.). */
export default function RequireSuperAdmin({ children }) {
    const { isAuthenticated, isSuperAdmin, initializing } = useAuth();
    const location = useLocation();

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    if (!isSuperAdmin) {
        return <Navigate to="/admin/dashboard" replace />;
    }

    return children;
}

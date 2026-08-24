import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import LoadingState from './LoadingState';

export default function RequireAuth({ children }) {
    const { isAuthenticated, initializing } = useAuth();
    const location = useLocation();

    if (initializing) {
        return <LoadingState label="Checking session…" />;
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    return children;
}

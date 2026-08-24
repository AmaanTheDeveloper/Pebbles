import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchDashboard } from '../../api/admin/dashboard';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminKpiCard, AdminPageHeader, AdminPanel, AdminPanelBody, AdminPanelHeading } from '../../layouts/AdminLayout';
import { formatMoney } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';

const quickLinks = [
    { to: '/admin/orders?status=pending', label: 'Pending orders' },
    { to: '/admin/orders?status=preparing', label: 'Preparing orders' },
    { to: '/admin/orders?status=ready', label: 'Ready orders' },
    { to: '/admin/payments?status=submitted', label: 'Payment verification' },
    { to: '/admin/products', label: 'Manage products' },
];

function KpiIcon({ children }) {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            {children}
        </svg>
    );
}

export default function DashboardPage() {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await fetchDashboard();
            setStats(data);
        } catch (err) {
            setError(normalizeApiError(err));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    if (loading) {
        return (
            <div className="admin-loading">
                <LoadingState label="Loading dashboard…" />
            </div>
        );
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={load} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader title="Dashboard" description="Today's operations at a glance." />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <AdminKpiCard
                    label="Orders today"
                    value={stats.orders_today}
                    icon={
                        <KpiIcon>
                            <path strokeLinecap="round" d="M7 8h10l-1 11H8L7 8z" />
                            <path strokeLinecap="round" d="M9 8V7a3 3 0 0 1 6 0v1" />
                        </KpiIcon>
                    }
                />
                <AdminKpiCard
                    label="Pending"
                    value={stats.pending_orders}
                    icon={
                        <KpiIcon>
                            <circle cx="12" cy="12" r="8" />
                            <path strokeLinecap="round" d="M12 8v4l2.5 2.5" />
                        </KpiIcon>
                    }
                />
                <AdminKpiCard
                    label="Preparing"
                    value={stats.preparing_orders}
                    icon={
                        <KpiIcon>
                            <path strokeLinecap="round" d="M8 12h8M12 8v8" />
                        </KpiIcon>
                    }
                />
                <AdminKpiCard
                    label="Ready"
                    value={stats.ready_orders}
                    icon={
                        <KpiIcon>
                            <path strokeLinecap="round" d="M5 12l4 4 10-10" />
                        </KpiIcon>
                    }
                />
                <AdminKpiCard
                    label="Payment verification"
                    value={stats.pending_payment_verification}
                    accent
                    icon={
                        <KpiIcon>
                            <rect x="3" y="6" width="18" height="12" rx="2" />
                            <path strokeLinecap="round" d="M3 10h18" />
                        </KpiIcon>
                    }
                />
                <AdminKpiCard
                    label="Sales today"
                    value={formatMoney(stats.sales_today)}
                    accent
                    icon={
                        <KpiIcon>
                            <path strokeLinecap="round" d="M4 18V8" />
                            <path strokeLinecap="round" d="M10 18V5" />
                            <path strokeLinecap="round" d="M16 18v-7" />
                            <path strokeLinecap="round" d="M22 18V11" />
                        </KpiIcon>
                    }
                />
            </div>

            <AdminPanel>
                <AdminPanelBody>
                    <AdminPanelHeading title="Quick links" description="Jump to the queues that need attention." />
                    <ul className="grid gap-2 sm:grid-cols-2">
                        {quickLinks.map((link) => (
                            <li key={link.to}>
                                <Link to={link.to} className="admin-quick-link">
                                    <span>{link.label}</span>
                                    <span aria-hidden="true">→</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </AdminPanelBody>
            </AdminPanel>
        </div>
    );
}

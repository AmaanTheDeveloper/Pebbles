import { useMemo, useState } from 'react';
import { Link, NavLink, Outlet } from 'react-router-dom';
import { appName } from '../api/config';
import AdminNotificationBell from '../components/admin/AdminNotificationBell';
import AdminNavIcon from '../components/admin/AdminNavIcon';
import AdminProfileMenu from '../components/account/AdminProfileMenu';
import { AdminNotificationsProvider } from '../contexts/AdminNotificationsContext';
import { useAuth } from '../contexts/AuthContext';

const allNavItems = [
    { to: '/admin/dashboard', label: 'Dashboard', end: true, icon: 'dashboard', roles: ['super_admin', 'manager'] },
    { to: '/admin/analytics', label: 'Analytics', icon: 'analytics', roles: ['super_admin', 'manager'] },
    { to: '/admin/orders', label: 'Orders', icon: 'orders', roles: ['super_admin', 'manager'] },
    { to: '/admin/payments', label: 'Payments', icon: 'payments', roles: ['super_admin', 'manager'] },
    { to: '/admin/notifications', label: 'Notifications', icon: 'notifications', roles: ['super_admin', 'manager'] },
    { to: '/admin/users', label: 'Users', icon: 'users', roles: ['super_admin'] },
    { to: '/admin/products', label: 'Products', icon: 'products', roles: ['super_admin'] },
    { to: '/admin/categories', label: 'Categories', icon: 'categories', roles: ['super_admin'] },
    { to: '/admin/offers', label: 'Offers', icon: 'offers', roles: ['super_admin'] },
    { to: '/admin/gallery', label: 'Gallery', icon: 'gallery', roles: ['super_admin'] },
    { to: '/admin/menu-book', label: 'Menu Book', icon: 'menuBook', roles: ['super_admin'] },
    { to: '/admin/homepage', label: 'Homepage', icon: 'homepage', roles: ['super_admin'] },
    { to: '/admin/reviews', label: 'Reviews', icon: 'reviews', roles: ['super_admin'] },
    { to: '/admin/contact-messages', label: 'Contact Messages', icon: 'contact', roles: ['super_admin'] },
    { to: '/admin/add-ons', label: 'Add-ons', icon: 'addOns', roles: ['super_admin'] },
    { to: '/admin/branches', label: 'Branches', icon: 'branches', roles: ['super_admin'] },
    { to: '/admin/delivery-zones', label: 'Delivery Zones', icon: 'deliveryZones', roles: ['super_admin'] },
    { to: '/admin/settings', label: 'Settings', icon: 'settings', roles: ['super_admin'] },
];

export default function AdminLayout() {
    return (
        <AdminNotificationsProvider>
            <AdminLayoutShell />
        </AdminNotificationsProvider>
    );
}

function AdminLayoutShell() {
    const { user, isSuperAdmin, isManager, branchName } = useAuth();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const navItems = useMemo(
        () => allNavItems.filter((item) => item.roles.includes(user?.role)),
        [user?.role],
    );

    return (
        <div className="admin-shell min-h-screen text-brand-ink">
            <div className="flex min-h-screen">
                <aside
                    className={`admin-sidebar fixed inset-y-0 left-0 z-40 flex w-[17.5rem] flex-col transition-transform duration-300 ease-out lg:static lg:translate-x-0 ${
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <div className="admin-sidebar-brand">
                        <p className="admin-sidebar-kicker">{appName}</p>
                        <p className="admin-sidebar-title">Admin Console</p>
                        <p className="admin-sidebar-meta">
                            {isSuperAdmin ? 'Super Admin' : isManager ? 'Manager' : 'Staff'}
                            {isManager && branchName ? ` · ${branchName}` : ''}
                        </p>
                    </div>

                    <nav className="admin-sidebar-nav">
                        {navItems.map((item) => (
                            <NavLink
                                key={item.to}
                                to={item.to}
                                end={item.end}
                                onClick={() => setSidebarOpen(false)}
                                className={({ isActive }) =>
                                    `admin-nav-link${isActive ? ' is-active' : ''}`
                                }
                            >
                                <span className="admin-nav-link-icon">
                                    <AdminNavIcon name={item.icon} />
                                </span>
                                <span className="truncate">{item.label}</span>
                            </NavLink>
                        ))}
                    </nav>

                    <div className="admin-sidebar-footer">
                        <Link
                            to="/"
                            className="admin-sidebar-store-link"
                            onClick={() => setSidebarOpen(false)}
                        >
                            View storefront
                        </Link>
                    </div>
                </aside>

                {sidebarOpen ? (
                    <button
                        type="button"
                        className="admin-sidebar-backdrop fixed inset-0 z-30 lg:hidden"
                        aria-label="Close sidebar"
                        onClick={() => setSidebarOpen(false)}
                    />
                ) : null}

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="admin-header sticky top-0 z-20">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                className="admin-icon-btn lg:hidden"
                                onClick={() => setSidebarOpen(true)}
                                aria-label="Open navigation menu"
                            >
                                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                                    <path strokeLinecap="round" d="M4 7h16M4 12h16M4 17h16" />
                                </svg>
                            </button>

                            {isManager && branchName ? (
                                <div className="admin-branch-chip">
                                    <span className="admin-branch-chip-label">Branch</span>
                                    <span className="admin-branch-chip-value">{branchName}</span>
                                </div>
                            ) : null}

                            {isSuperAdmin ? (
                                <div className="admin-branch-chip hidden sm:inline-flex">
                                    <span className="admin-branch-chip-label">Access</span>
                                    <span className="admin-branch-chip-value">All Branches</span>
                                </div>
                            ) : null}
                        </div>

                        <div className="flex items-center gap-2 sm:gap-3">
                            <AdminNotificationBell />
                            <AdminProfileMenu />
                        </div>
                    </header>

                    <main className="admin-main flex-1">
                        <div className="admin-page-enter">
                            <Outlet />
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}

export function AdminPageHeader({ title, description, actions }) {
    return (
        <div className="admin-page-header">
            <div>
                <h1 className="admin-page-title">{title}</h1>
                {description ? <p className="admin-page-description">{description}</p> : null}
            </div>
            {actions ? <div className="admin-page-actions">{actions}</div> : null}
        </div>
    );
}

export function AdminPanel({ children, className = '' }) {
    return <section className={`admin-panel ${className}`.trim()}>{children}</section>;
}

export function AdminPanelBody({ children, className = '' }) {
    return <div className={`admin-panel-body ${className}`.trim()}>{children}</div>;
}

export function AdminPanelHeading({ title, description, actions }) {
    return (
        <div className="admin-panel-heading">
            <div>
                <h2 className="admin-panel-title">{title}</h2>
                {description ? <p className="admin-panel-description">{description}</p> : null}
            </div>
            {actions ? <div className="admin-page-actions">{actions}</div> : null}
        </div>
    );
}

export function AdminKpiCard({ label, value, icon, accent = false, footer = null }) {
    return (
        <div className={`admin-kpi-card${accent ? ' is-accent' : ''}`}>
            <div className="admin-kpi-card-top">
                <p className="admin-kpi-label">{label}</p>
                {icon ? <span className="admin-kpi-icon">{icon}</span> : null}
            </div>
            <p className="admin-kpi-value">{value}</p>
            {footer}
        </div>
    );
}

const buttonVariants = {
    primary: 'admin-btn admin-btn-primary',
    secondary: 'admin-btn admin-btn-secondary',
    danger: 'admin-btn admin-btn-danger',
    ghost: 'admin-btn admin-btn-ghost',
    success: 'admin-btn admin-btn-success',
};

export function AdminButton({ variant = 'secondary', className = '', type = 'button', ...props }) {
    return (
        <button
            type={type}
            className={`${buttonVariants[variant] ?? buttonVariants.secondary} ${className}`.trim()}
            {...props}
        />
    );
}

export function adminLinkClassName(className = '') {
    return `admin-link ${className}`.trim();
}

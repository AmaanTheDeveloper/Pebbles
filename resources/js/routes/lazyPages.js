import { lazy } from 'react';

function lazyPage(importer) {
    return lazy(importer);
}

// Storefront — secondary routes
export const AboutPage = lazyPage(() => import('../pages/AboutPage'));
export const GalleryPage = lazyPage(() => import('../pages/GalleryPage'));
export const ReviewsPage = lazyPage(() => import('../pages/ReviewsPage'));
export const ContactPage = lazyPage(() => import('../pages/ContactPage'));
export const LocationsPage = lazyPage(() => import('../pages/LocationsPage'));
export const MenuBookPage = lazyPage(() => import('../pages/MenuBookPage'));
export const OffersPage = lazyPage(() => import('../pages/OffersPage'));
export const ModifyOrderPage = lazyPage(() => import('../pages/ModifyOrderPage'));
export const ProfilePage = lazyPage(() => import('../pages/ProfilePage'));

// Admin — manager + super admin
export const DashboardPage = lazyPage(() => import('../pages/admin/DashboardPage'));
export const AdminProfilePage = lazyPage(() => import('../pages/admin/AdminProfilePage'));
export const AnalyticsPage = lazyPage(() => import('../pages/admin/AnalyticsPage'));
export const AdminOrdersPage = lazyPage(() => import('../pages/admin/OrdersPage'));
export const AdminOrderDetailPage = lazyPage(() => import('../pages/admin/OrderDetailPage'));
export const PaymentsPage = lazyPage(() => import('../pages/admin/PaymentsPage'));
export const PaymentDetailPage = lazyPage(() => import('../pages/admin/PaymentDetailPage'));
export const AdminNotificationsPage = lazyPage(() => import('../pages/admin/AdminNotificationsPage'));

// Admin — super admin only
export const AdminUsersPage = lazyPage(() => import('../pages/admin/AdminUsersPage'));
export const ProductsPage = lazyPage(() => import('../pages/admin/ProductsPage'));
export const ProductFormPage = lazyPage(() => import('../pages/admin/ProductFormPage'));
export const CategoriesPage = lazyPage(() => import('../pages/admin/CategoriesPage'));
export const CategoryFormPage = lazyPage(() => import('../pages/admin/CategoryFormPage'));
export const AdminOffersPage = lazyPage(() => import('../pages/admin/OffersPage'));
export const OfferFormPage = lazyPage(() => import('../pages/admin/OfferFormPage'));
export const AdminGalleryPage = lazyPage(() => import('../pages/admin/AdminGalleryPage'));
export const GalleryFormPage = lazyPage(() => import('../pages/admin/GalleryFormPage'));
export const AdminMenuBookPage = lazyPage(() => import('../pages/admin/AdminMenuBookPage'));
export const MenuBookFormPage = lazyPage(() => import('../pages/admin/MenuBookFormPage'));
export const AdminHomepagePage = lazyPage(() => import('../pages/admin/AdminHomepagePage'));
export const AdminReviewsPage = lazyPage(() => import('../pages/admin/AdminReviewsPage'));
export const AdminContactMessagesPage = lazyPage(() => import('../pages/admin/AdminContactMessagesPage'));
export const AddOnsPage = lazyPage(() => import('../pages/admin/AddOnsPage'));
export const AddOnGroupFormPage = lazyPage(() => import('../pages/admin/AddOnGroupFormPage'));
export const BranchesPage = lazyPage(() => import('../pages/admin/BranchesPage'));
export const BranchFormPage = lazyPage(() => import('../pages/admin/BranchFormPage'));
export const DeliveryZonesPage = lazyPage(() => import('../pages/admin/DeliveryZonesPage'));
export const DeliveryZoneFormPage = lazyPage(() => import('../pages/admin/DeliveryZoneFormPage'));
export const SettingsPage = lazyPage(() => import('../pages/admin/SettingsPage'));

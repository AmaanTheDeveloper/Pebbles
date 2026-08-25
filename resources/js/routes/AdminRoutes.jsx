import { Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import RequireAdmin from '../components/admin/RequireAdmin';
import RequireSuperAdmin from '../components/admin/RequireSuperAdmin';
import LoadingState from '../components/storefront/LoadingState';
import AdminLayout from '../layouts/AdminLayout';
import * as Lazy from './lazyAdminPages';

function AdminFallback() {
    return (
        <div className="admin-loading">
            <LoadingState label="Loading dashboard…" />
        </div>
    );
}

export default function AdminRoutes() {
    return (
        <Routes>
            <Route
                element={
                    <RequireAdmin>
                        <AdminLayout />
                    </RequireAdmin>
                }
            >
                <Route index element={<Navigate to="dashboard" replace />} />
                <Route
                    path="dashboard"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.DashboardPage />
                        </Suspense>
                    }
                />
                <Route
                    path="profile"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.AdminProfilePage />
                        </Suspense>
                    }
                />
                <Route
                    path="analytics"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.AnalyticsPage />
                        </Suspense>
                    }
                />
                <Route
                    path="orders"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.AdminOrdersPage />
                        </Suspense>
                    }
                />
                <Route
                    path="orders/:orderNumber"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.AdminOrderDetailPage />
                        </Suspense>
                    }
                />
                <Route
                    path="payments"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.PaymentsPage />
                        </Suspense>
                    }
                />
                <Route
                    path="payments/:id"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.PaymentDetailPage />
                        </Suspense>
                    }
                />
                <Route
                    path="notifications"
                    element={
                        <Suspense fallback={<AdminFallback />}>
                            <Lazy.AdminNotificationsPage />
                        </Suspense>
                    }
                />
                <Route
                    path="users"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminUsersPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="products"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.ProductsPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="products/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.ProductFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="products/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.ProductFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="categories"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.CategoriesPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="categories/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.CategoryFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="categories/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.CategoryFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="offers"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminOffersPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="offers/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.OfferFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="offers/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.OfferFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="coupons"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminCouponsPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="coupons/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.CouponFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="coupons/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.CouponFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="gallery"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminGalleryPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="gallery/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.GalleryFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="gallery/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.GalleryFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="menu-book"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminMenuBookPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="menu-book/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.MenuBookFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="menu-book/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.MenuBookFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="homepage"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminHomepagePage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="about"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminAboutPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="reviews"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminReviewsPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="contact-messages"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AdminContactMessagesPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="add-ons"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AddOnsPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="add-ons/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AddOnGroupFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="add-ons/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.AddOnGroupFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="branches"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.BranchesPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="branches/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.BranchFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="branches/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.BranchFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="delivery-zones"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.DeliveryZonesPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="delivery-zones/new"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.DeliveryZoneFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="delivery-zones/:id/edit"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.DeliveryZoneFormPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route
                    path="settings"
                    element={
                        <RequireSuperAdmin>
                            <Suspense fallback={<AdminFallback />}>
                                <Lazy.SettingsPage />
                            </Suspense>
                        </RequireSuperAdmin>
                    }
                />
                <Route path="*" element={<Navigate to="dashboard" replace />} />
            </Route>
        </Routes>
    );
}

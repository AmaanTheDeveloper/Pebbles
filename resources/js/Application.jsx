import { Suspense } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import AppBootLoaderGate from './components/performance/AppBootLoaderGate';
import RouteProgressBar from './components/performance/RouteProgressBar';
import RequireAdmin from './components/admin/RequireAdmin';
import RequireSuperAdmin from './components/admin/RequireSuperAdmin';
import RequireAuth from './components/storefront/RequireAuth';
import LoadingState from './components/storefront/LoadingState';
import { ActiveOrdersProvider } from './contexts/ActiveOrdersContext';
import AdminLayout from './layouts/AdminLayout';
import StorefrontLayout from './layouts/StorefrontLayout';
import * as Lazy from './routes/lazyPages';
import CartPage from './pages/CartPage';
import CheckoutPage from './pages/CheckoutPage';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import MenuPage from './pages/MenuPage';
import OrderConfirmationPage from './pages/OrderConfirmationPage';
import OrderDetailPage from './pages/OrderDetailPage';
import OrderOnlinePage from './pages/OrderOnlinePage';
import OrderTrackingPage from './pages/OrderTrackingPage';
import OrdersPage from './pages/OrdersPage';
import ProductDetailPage from './pages/ProductDetailPage';
import RegisterPage from './pages/RegisterPage';

function StorefrontFallback() {
    return (
        <div className="flex min-h-[40vh] items-center justify-center">
            <LoadingState label="Loading page…" />
        </div>
    );
}

function AdminFallback() {
    return (
        <div className="admin-loading">
            <LoadingState label="Loading dashboard…" />
        </div>
    );
}

export default function Application() {
    return (
        <BrowserRouter>
            <AppBootLoaderGate />
            <ActiveOrdersProvider>
                <RouteProgressBar />
                <Suspense fallback={<StorefrontFallback />}>
                <Routes>
                    <Route element={<StorefrontLayout />}>
                        <Route index element={<HomePage />} />
                        <Route path="about" element={<Lazy.AboutPage />} />
                        <Route path="gallery" element={<Lazy.GalleryPage />} />
                        <Route path="reviews" element={<Lazy.ReviewsPage />} />
                        <Route path="contact" element={<Lazy.ContactPage />} />
                        <Route path="locations" element={<Lazy.LocationsPage />} />
                        <Route path="order" element={<OrderOnlinePage />} />
                        <Route path="menu" element={<MenuPage />} />
                        <Route path="menu/:categorySlug" element={<MenuPage />} />
                        <Route path="menu-book" element={<Lazy.MenuBookPage />} />
                        <Route path="product/:slug" element={<ProductDetailPage />} />
                        <Route path="offers" element={<Lazy.OffersPage />} />
                        <Route path="login" element={<LoginPage />} />
                        <Route path="register" element={<RegisterPage />} />
                        <Route
                            path="cart"
                            element={
                                <RequireAuth>
                                    <CartPage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="checkout"
                            element={
                                <RequireAuth>
                                    <CheckoutPage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="orders/:orderNumber/track"
                            element={
                                <RequireAuth>
                                    <OrderTrackingPage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="orders"
                            element={
                                <RequireAuth>
                                    <OrdersPage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="order/:orderNumber"
                            element={
                                <RequireAuth>
                                    <OrderDetailPage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="order/:orderNumber/modify"
                            element={
                                <RequireAuth>
                                    <Lazy.ModifyOrderPage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="profile"
                            element={
                                <RequireAuth>
                                    <Lazy.ProfilePage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="order-confirmation/:orderNumber"
                            element={
                                <RequireAuth>
                                    <OrderConfirmationPage />
                                </RequireAuth>
                            }
                        />
                    </Route>

                    <Route
                        path="admin"
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
                    </Route>

                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </Suspense>
            </ActiveOrdersProvider>
        </BrowserRouter>
    );
}

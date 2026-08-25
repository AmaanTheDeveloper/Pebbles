import { Suspense, lazy } from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import AppBootLoaderGate from './components/performance/AppBootLoaderGate';
import RouteProgressBar from './components/performance/RouteProgressBar';
import RequireAuth from './components/storefront/RequireAuth';
import RequireVerified from './components/storefront/RequireVerified';
import LoadingState from './components/storefront/LoadingState';
import { ActiveOrdersProvider } from './contexts/ActiveOrdersContext';
import StorefrontLayout from './layouts/StorefrontLayout';
import * as Lazy from './routes/lazyStorefrontPages';
import CartPage from './pages/CartPage';
import CheckoutPage from './pages/CheckoutPage';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import MenuPage from './pages/MenuPage';
import NotFoundPage from './pages/NotFoundPage';
import OrderOnlinePage from './pages/OrderOnlinePage';
import ProductDetailPage from './pages/ProductDetailPage';
import RegisterPage from './pages/RegisterPage';
import NetworkStatusProvider from './components/status/NetworkStatusProvider';
import EmailVerificationToast from './components/account/EmailVerificationToast';

const AdminRoutes = lazy(() => import('./routes/AdminRoutes'));

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
            <NetworkStatusProvider>
            <AppBootLoaderGate />
            <EmailVerificationToast />
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
                        <Route path="forgot-password" element={<Lazy.ForgotPasswordPage />} />
                        <Route path="reset-password" element={<Lazy.ResetPasswordPage />} />
                        <Route
                            path="verify-email"
                            element={
                                <RequireAuth>
                                    <Lazy.VerifyEmailPromptPage />
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="verify-email/:id/:hash"
                            element={<Lazy.VerifyEmailPage />}
                        />
                        <Route
                            path="cart"
                            element={<CartPage />}
                        />
                        <Route
                            path="checkout"
                            element={
                                <RequireAuth>
                                    <RequireVerified>
                                        <CheckoutPage />
                                    </RequireVerified>
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="orders/:orderNumber/track"
                            element={
                                <RequireAuth>
                                    <RequireVerified>
                                        <Lazy.OrderTrackingPage />
                                    </RequireVerified>
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="orders"
                            element={
                                <RequireAuth>
                                    <RequireVerified>
                                        <Lazy.OrdersPage />
                                    </RequireVerified>
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="order/:orderNumber"
                            element={
                                <RequireAuth>
                                    <RequireVerified>
                                        <Lazy.OrderDetailPage />
                                    </RequireVerified>
                                </RequireAuth>
                            }
                        />
                        <Route
                            path="order/:orderNumber/modify"
                            element={
                                <RequireAuth>
                                    <RequireVerified>
                                        <Lazy.ModifyOrderPage />
                                    </RequireVerified>
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
                                    <RequireVerified>
                                        <Lazy.OrderConfirmationPage />
                                    </RequireVerified>
                                </RequireAuth>
                            }
                        />
                        <Route path="*" element={<NotFoundPage />} />
                    </Route>

                    <Route
                        path="admin/*"
                        element={
                            <Suspense fallback={<AdminFallback />}>
                                <AdminRoutes />
                            </Suspense>
                        }
                    />

                    <Route path="*" element={<NotFoundPage />} />
                </Routes>
            </Suspense>
            </ActiveOrdersProvider>
            </NetworkStatusProvider>
        </BrowserRouter>
    );
}

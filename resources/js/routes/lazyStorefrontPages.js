import { lazyPage } from './lazyPage';

export const AboutPage = lazyPage(() => import('../pages/AboutPage'));
export const GalleryPage = lazyPage(() => import('../pages/GalleryPage'));
export const ReviewsPage = lazyPage(() => import('../pages/ReviewsPage'));
export const ContactPage = lazyPage(() => import('../pages/ContactPage'));
export const LocationsPage = lazyPage(() => import('../pages/LocationsPage'));
export const MenuBookPage = lazyPage(() => import('../pages/MenuBookPage'));
export const OffersPage = lazyPage(() => import('../pages/OffersPage'));
export const ModifyOrderPage = lazyPage(() => import('../pages/ModifyOrderPage'));
export const ProfilePage = lazyPage(() => import('../pages/ProfilePage'));
export const ForgotPasswordPage = lazyPage(() => import('../pages/ForgotPasswordPage'));
export const ResetPasswordPage = lazyPage(() => import('../pages/ResetPasswordPage'));
export const VerifyEmailPage = lazyPage(() => import('../pages/VerifyEmailPage'));
export const VerifyEmailPromptPage = lazyPage(() => import('../pages/VerifyEmailPromptPage'));
export const OrderConfirmationPage = lazyPage(() => import('../pages/OrderConfirmationPage'));
export const OrderDetailPage = lazyPage(() => import('../pages/OrderDetailPage'));
export const OrderTrackingPage = lazyPage(() => import('../pages/OrderTrackingPage'));
export const OrdersPage = lazyPage(() => import('../pages/OrdersPage'));

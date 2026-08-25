import '../css/app.css';
import { createRoot } from 'react-dom/client';
import Application from './Application';
import { AuthProvider } from './contexts/AuthContext';
import { CartProvider } from './contexts/CartContext';
import { EmailVerificationReminderProvider } from './contexts/EmailVerificationReminderContext';
import { registerServiceWorker } from './lib/registerServiceWorker';
import './bootstrap';

const container = document.getElementById('root');

if (container) {
    createRoot(container).render(
        <AuthProvider>
            <CartProvider>
                <EmailVerificationReminderProvider>
                    <Application />
                </EmailVerificationReminderProvider>
            </CartProvider>
        </AuthProvider>,
    );
}

registerServiceWorker();

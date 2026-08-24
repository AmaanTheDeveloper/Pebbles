import '../css/app.css';
import { createRoot } from 'react-dom/client';
import Application from './Application';
import { AuthProvider } from './contexts/AuthContext';
import { CartProvider } from './contexts/CartContext';
import './bootstrap';

const container = document.getElementById('root');

if (container) {
    createRoot(container).render(
        <AuthProvider>
            <CartProvider>
                <Application />
            </CartProvider>
        </AuthProvider>,
    );
}

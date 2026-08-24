import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import {
    addCartItem as addCartItemRequest,
    clearCart as clearCartRequest,
    getCart,
    removeCartItem as removeCartItemRequest,
    updateCartItem as updateCartItemRequest,
} from '../api/cart';
import { useAuth } from './AuthContext';
import { normalizeApiError } from '../lib/errors';

const CartContext = createContext(null);

const emptyCart = {
    id: null,
    items: [],
    item_count: 0,
    subtotal: '0.00',
    delivery_fee: '0.00',
    total: '0.00',
    branch_id: null,
};

export function CartProvider({ children }) {
    const { isAuthenticated } = useAuth();
    const [cart, setCart] = useState(emptyCart);
    const [loading, setLoading] = useState(false);
    const [mutating, setMutating] = useState(false);
    const [error, setError] = useState(null);

    const refreshCart = useCallback(async () => {
        if (!isAuthenticated) {
            setCart(emptyCart);
            setError(null);
            return null;
        }

        setLoading(true);
        setError(null);

        try {
            const next = await getCart();
            setCart(next ?? emptyCart);
            return next;
        } catch (err) {
            const normalized = normalizeApiError(err);
            setError(normalized);
            setCart(emptyCart);
            return null;
        } finally {
            setLoading(false);
        }
    }, [isAuthenticated]);

    useEffect(() => {
        refreshCart();
    }, [refreshCart]);

    const runMutation = useCallback(async (operation) => {
        setMutating(true);
        setError(null);

        try {
            const next = await operation();
            if (next) {
                setCart(next);
            } else {
                await refreshCart();
            }
            return next;
        } catch (err) {
            const normalized = normalizeApiError(err);
            setError(normalized);
            throw normalized;
        } finally {
            setMutating(false);
        }
    }, [refreshCart]);

    const addItem = useCallback(
        (payload) => runMutation(() => addCartItemRequest(payload)),
        [runMutation],
    );

    const updateItem = useCallback(
        (itemId, payload) => runMutation(() => updateCartItemRequest(itemId, payload)),
        [runMutation],
    );

    const removeItem = useCallback(
        (itemId) => runMutation(() => removeCartItemRequest(itemId)),
        [runMutation],
    );

    const clear = useCallback(
        () =>
            runMutation(async () => {
                await clearCartRequest();
                return emptyCart;
            }),
        [runMutation],
    );

    const value = useMemo(
        () => ({
            cart,
            loading,
            mutating,
            error,
            itemCount: cart?.item_count ?? 0,
            refreshCart,
            addItem,
            updateItem,
            removeItem,
            clear,
            clearError: () => setError(null),
        }),
        [cart, loading, mutating, error, refreshCart, addItem, updateItem, removeItem, clear],
    );

    return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() {
    const context = useContext(CartContext);

    if (!context) {
        throw new Error('useCart must be used within a CartProvider.');
    }

    return context;
}

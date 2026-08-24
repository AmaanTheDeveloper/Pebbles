import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { reorderOrder } from '../../api/orders';
import { useCart } from '../../contexts/CartContext';
import { normalizeApiError } from '../../lib/errors';
import ReorderCartModal from './ReorderCartModal';

export default function ReorderOrderButton({
    orderNumber,
    className = '',
    variant = 'primary',
    label = 'Reorder',
}) {
    const navigate = useNavigate();
    const { cart, refreshCart } = useCart();
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);

    const hasCartItems = (cart?.items?.length ?? 0) > 0;

    const runReorder = async (mode) => {
        setBusy(true);
        setError(null);

        try {
            const response = await reorderOrder(orderNumber, { mode });
            await refreshCart();

            navigate('/cart', {
                state: {
                    flash: response.message ?? 'Items added to your cart.',
                    reorderSummary: response.summary ?? null,
                },
            });
        } catch (err) {
            setError(normalizeApiError(err).message);
        } finally {
            setBusy(false);
            setModalOpen(false);
        }
    };

    const handleClick = () => {
        if (busy) {
            return;
        }

        setError(null);

        if (hasCartItems) {
            setModalOpen(true);
            return;
        }

        runReorder('merge');
    };

    const buttonClass =
        variant === 'secondary'
            ? 'rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 ring-1 ring-stone-300 transition hover:-translate-y-0.5 hover:bg-stone-50 hover:shadow-sm disabled:opacity-60'
            : 'btn-orange btn-shine inline-flex items-center justify-center gap-1 px-4 py-2.5 text-sm disabled:opacity-60';

    return (
        <>
            <button
                type="button"
                onClick={handleClick}
                disabled={busy}
                className={[buttonClass, className].filter(Boolean).join(' ')}
            >
                {busy ? 'Adding to cart…' : label}
                {!busy && variant === 'primary' ? (
                    <span className="btn-arrow transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                        →
                    </span>
                ) : null}
            </button>

            {error ? <p className="mt-2 text-sm text-red-700">{error}</p> : null}

            <ReorderCartModal
                open={modalOpen}
                busy={busy}
                error={error}
                onClose={() => {
                    if (!busy) {
                        setModalOpen(false);
                        setError(null);
                    }
                }}
                onKeepCart={() => setModalOpen(false)}
                onMerge={() => runReorder('merge')}
                onReplace={() => runReorder('replace')}
            />
        </>
    );
}

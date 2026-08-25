import { useRef, useState } from 'react';
import { extractCouponError } from '../../lib/errors';
import { useCart } from '../../contexts/CartContext';

function formatMoney(value) {
    return `Rs ${Number(value ?? 0).toLocaleString('en-PK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export default function CartCouponPanel({ branchId = null }) {
    const { cart, applyCoupon, removeCoupon, mutating } = useCart();
    const [couponCode, setCouponCode] = useState('');
    const [couponBusy, setCouponBusy] = useState(false);
    const [couponError, setCouponError] = useState(null);
    const couponBusyRef = useRef(false);

    const appliedCoupon = cart?.applied_coupon ?? null;
    const couponDiscount = Number(cart?.coupon_discount_amount ?? 0);

    const handleApply = async (event) => {
        event.preventDefault();

        if (couponBusyRef.current) {
            return;
        }

        const code = couponCode.trim();
        if (!code) {
            setCouponError('Enter a coupon code.');
            return;
        }

        couponBusyRef.current = true;
        setCouponBusy(true);
        setCouponError(null);

        try {
            await applyCoupon({
                coupon_code: code,
                ...(branchId ? { branch_id: Number(branchId) } : {}),
            });
            setCouponCode('');
        } catch (err) {
            setCouponError(extractCouponError(err, 'apply'));
        } finally {
            couponBusyRef.current = false;
            setCouponBusy(false);
        }
    };

    const handleRemove = async () => {
        if (couponBusyRef.current) {
            return;
        }

        couponBusyRef.current = true;
        setCouponBusy(true);
        setCouponError(null);

        try {
            await removeCoupon();
        } catch (err) {
            setCouponError(extractCouponError(err, 'remove'));
        } finally {
            couponBusyRef.current = false;
            setCouponBusy(false);
        }
    };

    return (
        <div className="border-t border-dashed border-stone-200 pt-3">
            <p className="text-sm font-medium text-stone-900">Have a coupon?</p>
            <p className="mt-0.5 text-xs text-stone-500">Apply a coupon code to your cart.</p>

            {appliedCoupon ? (
                <div className="mt-3 rounded-xl bg-emerald-50 px-3 py-3 text-sm text-emerald-900">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="font-semibold">✓ Coupon applied</p>
                            <p className="mt-0.5">{appliedCoupon.code}</p>
                            {couponDiscount > 0 ? (
                                <p className="mt-1 text-xs">You saved {formatMoney(couponDiscount)}</p>
                            ) : null}
                        </div>
                        <button
                            type="button"
                            disabled={couponBusy || mutating}
                            onClick={handleRemove}
                            className="shrink-0 text-sm font-medium text-emerald-900 underline disabled:opacity-60"
                        >
                            {couponBusy ? 'Removing…' : 'Remove'}
                        </button>
                    </div>
                </div>
            ) : (
                <div className="mt-3 flex flex-col gap-2 sm:flex-row">
                    <label className="sr-only" htmlFor="cart-coupon-code">
                        Coupon code
                    </label>
                    <input
                        id="cart-coupon-code"
                        name="coupon_code"
                        value={couponCode}
                        onChange={(event) => {
                            setCouponCode(event.target.value);
                            setCouponError(null);
                        }}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                handleApply(event);
                            }
                        }}
                        disabled={couponBusy || mutating}
                        placeholder="Enter coupon code"
                        autoComplete="off"
                        aria-invalid={Boolean(couponError)}
                        aria-describedby={couponError ? 'cart-coupon-error' : undefined}
                        className="w-full flex-1 rounded-xl border border-stone-300 px-3 py-2 text-sm disabled:opacity-60"
                    />
                    <button
                        type="button"
                        disabled={couponBusy || mutating}
                        onClick={handleApply}
                        className="rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                    >
                        {couponBusy ? 'Applying…' : 'Apply'}
                    </button>
                </div>
            )}

            {couponError ? (
                <p id="cart-coupon-error" className="mt-2 text-xs text-red-600" role="alert">
                    {couponError}
                </p>
            ) : null}
        </div>
    );
}

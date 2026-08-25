import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useOutletContext } from 'react-router-dom';
import { fetchBranches, fetchDeliveryZones } from '../api/catalog';
import { createOrder } from '../api/checkout';
import { fetchPaymentMethods, uploadPaymentProof } from '../api/orders';
import { PAYMENT_PROOF_ACCEPTED } from '../components/orders/PaymentProofUpload';
import PaymentMethodDetails from '../components/orders/PaymentMethodDetails';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { useCart } from '../contexts/CartContext';
import { useActiveOrders } from '../contexts/ActiveOrdersContext';
import { useAuth } from '../contexts/AuthContext';
import { extractCouponError, firstFieldError, normalizeApiError } from '../lib/errors';
import { readOrderType, writeOrderType } from '../lib/orderTypeStorage';

function formatMoney(value) {
    return `Rs ${Number(value ?? 0).toLocaleString('en-PK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

function isImageFile(file) {
    return file?.type?.startsWith('image/') ?? false;
}

export default function CheckoutPage() {
    const navigate = useNavigate();
    const { selectedBranch, branches: outletBranches = [] } = useOutletContext() ?? {};
    const { cart, loading: cartLoading, refreshCart, applyCoupon, removeCoupon } = useCart();
    const { notifyActiveOrderCreated } = useActiveOrders();
    const { user } = useAuth();

    const [branches, setBranches] = useState([]);
    const [zones, setZones] = useState([]);
    const [paymentMethods, setPaymentMethods] = useState([]);
    const [loadingMeta, setLoadingMeta] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const submittingRef = useRef(false);
    const profilePrefillApplied = useRef(false);
    const couponBusyRef = useRef(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});

    const [type, setType] = useState(() => readOrderType() ?? 'delivery');
    const [branchId, setBranchId] = useState('');
    const [deliveryZoneId, setDeliveryZoneId] = useState('');
    const [deliveryRecipientName, setDeliveryRecipientName] = useState('');
    const [deliveryPhone, setDeliveryPhone] = useState('');
    const [deliveryLine1, setDeliveryLine1] = useState('');
    const [deliveryLine2, setDeliveryLine2] = useState('');
    const [deliveryArea, setDeliveryArea] = useState('');
    const [deliveryCity, setDeliveryCity] = useState('Karachi');
    const [deliveryInstructions, setDeliveryInstructions] = useState('');
    const [pickupNotes, setPickupNotes] = useState('');
    const [tableNumber, setTableNumber] = useState('');
    const [customerNotes, setCustomerNotes] = useState('');
    const [paymentMethod, setPaymentMethod] = useState('');
    const [proofFile, setProofFile] = useState(null);
    const [proofPreviewUrl, setProofPreviewUrl] = useState(null);
    const [proofError, setProofError] = useState(null);
    const [couponCode, setCouponCode] = useState('');
    const [couponBusy, setCouponBusy] = useState(false);
    const [couponError, setCouponError] = useState(null);

    const handleTypeChange = (nextType) => {
        setType(nextType);

        if (nextType === 'delivery' || nextType === 'takeaway' || nextType === 'dine_in') {
            writeOrderType(nextType);
        }
    };

    useEffect(() => {
        let cancelled = false;

        (async () => {
            setLoadingMeta(true);
            try {
                const nextBranches = outletBranches.length ? outletBranches : await fetchBranches();
                const nextMethods = await fetchPaymentMethods();
                if (!cancelled) {
                    setBranches(nextBranches ?? []);
                    setPaymentMethods(nextMethods ?? []);
                    setPaymentMethod(nextMethods?.[0]?.id ?? '');

                    const preferred =
                        nextBranches?.find((branch) => branch.slug === selectedBranch?.slug) ??
                        nextBranches?.[0];
                    if (preferred) {
                        setBranchId(String(preferred.id));
                    }
                }
            } catch (err) {
                if (!cancelled) {
                    setError(normalizeApiError(err));
                }
            } finally {
                if (!cancelled) {
                    setLoadingMeta(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [selectedBranch, outletBranches]);

    useEffect(() => {
        if (!branchId) {
            return;
        }

        refreshCart({ branch_id: branchId }).catch(() => {});
    }, [branchId, refreshCart]);

    useEffect(() => {
        if (!branchId || type !== 'delivery') {
            setZones([]);
            setDeliveryZoneId('');
            return;
        }

        const branch = branches.find((item) => String(item.id) === String(branchId));
        if (!branch) {
            return;
        }

        let cancelled = false;

        (async () => {
            try {
                const nextZones = await fetchDeliveryZones(branch.slug);
                if (!cancelled) {
                    setZones(nextZones ?? []);
                    setDeliveryZoneId(nextZones?.[0] ? String(nextZones[0].id) : '');
                }
            } catch (err) {
                if (!cancelled) {
                    setError(normalizeApiError(err));
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [branchId, type, branches]);

    useEffect(() => {
        setProofFile(null);
        setProofError(null);
    }, [paymentMethod]);

    useEffect(() => {
        if (!proofFile || !isImageFile(proofFile)) {
            setProofPreviewUrl(null);
            return undefined;
        }

        const url = URL.createObjectURL(proofFile);
        setProofPreviewUrl(url);

        return () => {
            URL.revokeObjectURL(url);
        };
    }, [proofFile]);

    useEffect(() => {
        if (!user || profilePrefillApplied.current) {
            return;
        }

        const address = user.default_address;
        profilePrefillApplied.current = true;

        if (user.name) {
            setDeliveryRecipientName(user.name);
        }

        if (user.phone || address?.phone) {
            setDeliveryPhone(user.phone || address?.phone || '');
        }

        if (type !== 'delivery' || !address) {
            return;
        }

        if (address.line1) {
            setDeliveryLine1(address.line1);
        }

        if (address.line2) {
            setDeliveryLine2(address.line2);
        }

        if (address.area) {
            setDeliveryArea(address.area);
        }

        if (address.city) {
            setDeliveryCity(address.city);
        }

        if (address.instructions) {
            setDeliveryInstructions(address.instructions);
        }

        if (address.delivery_zone_id) {
            setDeliveryZoneId(String(address.delivery_zone_id));
        }
    }, [user, type]);

    useEffect(() => {
        if (cart?.coupon_message) {
            setCouponError(cart.coupon_message);
            setCouponCode('');
        }
    }, [cart?.coupon_message]);

    const selectedZone = useMemo(
        () => zones.find((zone) => String(zone.id) === String(deliveryZoneId)) ?? null,
        [zones, deliveryZoneId],
    );

    const selectedPayment = useMemo(
        () => paymentMethods.find((method) => method.id === paymentMethod) ?? null,
        [paymentMethods, paymentMethod],
    );

    const estimatedDeliveryFee = type === 'delivery' ? selectedZone?.delivery_fee ?? 0 : 0;
    const offerDiscount = Number(cart?.offer_discount_amount ?? 0);
    const couponDiscount = Number(cart?.coupon_discount_amount ?? 0);
    const estimatedTotal =
        Number(cart?.subtotal ?? 0) - offerDiscount - couponDiscount + Number(estimatedDeliveryFee);
    const appliedCoupon = cart?.applied_coupon ?? null;

    const handleApplyCoupon = async (event) => {
        event.preventDefault();

        if (couponBusyRef.current) {
            return;
        }

        const code = couponCode.trim();
        if (!code) {
            setCouponError('Invalid coupon code.');
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
            setCouponError(null);
        } catch (err) {
            setCouponError(extractCouponError(err, 'apply'));
        } finally {
            couponBusyRef.current = false;
            setCouponBusy(false);
        }
    };

    const handleRemoveCoupon = async () => {
        if (couponBusyRef.current) {
            return;
        }

        couponBusyRef.current = true;
        setCouponBusy(true);
        setCouponError(null);

        try {
            await removeCoupon();
            setCouponError(null);
        } catch (err) {
            setCouponError(extractCouponError(err, 'remove'));
        } finally {
            couponBusyRef.current = false;
            setCouponBusy(false);
        }
    };

    if (cartLoading || loadingMeta) {
        return <LoadingState label="Preparing checkout…" />;
    }

    if (!cart?.items?.length) {
        return (
            <div className="space-y-4">
                <EmptyState
                    title="Your cart is empty"
                    description="Add items from the menu before checking out."
                />
                <Link to="/menu" className="inline-flex text-sm font-medium text-amber-900 hover:underline">
                    Browse menu
                </Link>
            </div>
        );
    }

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (submittingRef.current) {
            return;
        }

        if (selectedPayment?.requires_proof && !proofFile) {
            setProofError('Please upload your payment screenshot before placing the order.');
            return;
        }

        submittingRef.current = true;
        setSubmitting(true);
        setError(null);
        setFieldErrors({});
        setProofError(null);

        const payload = {
            branch_id: Number(branchId),
            type,
            customer_notes: customerNotes || null,
            payment_method: paymentMethod || undefined,
        };

        if (type === 'delivery') {
            payload.delivery_zone_id = Number(deliveryZoneId);
            payload.delivery_recipient_name = deliveryRecipientName;
            payload.delivery_phone = deliveryPhone;
            payload.delivery_line1 = deliveryLine1;
            payload.delivery_line2 = deliveryLine2 || null;
            payload.delivery_area = deliveryArea || null;
            payload.delivery_city = deliveryCity;
            payload.delivery_instructions = deliveryInstructions || null;
        }

        if (type === 'takeaway') {
            payload.pickup_notes = pickupNotes || null;
        }

        if (type === 'dine_in') {
            payload.table_number = tableNumber;
        }

        let createdOrder = null;

        try {
            createdOrder = await createOrder(payload);

            if (selectedPayment?.requires_proof && proofFile) {
                try {
                    await uploadPaymentProof(createdOrder.order_number, { file: proofFile });
                } catch (proofErr) {
                    await refreshCart();
                    notifyActiveOrderCreated();
                    navigate(`/order/${createdOrder.order_number}`, {
                        replace: true,
                        state: {
                            order: createdOrder,
                            flash:
                                normalizeApiError(proofErr).message ||
                                'Your order was placed, but the payment proof could not be uploaded. Please upload it from this page.',
                            proofUploadFailed: true,
                        },
                    });
                    return;
                }
            }

            await refreshCart();
            notifyActiveOrderCreated();
            navigate(`/order-confirmation/${createdOrder.order_number}`, {
                replace: true,
                state: {
                    order: createdOrder,
                    celebrate: true,
                    branchName:
                        branches.find((branch) => String(branch.id) === String(branchId))?.name ?? null,
                },
            });
        } catch (err) {
            const normalized = normalizeApiError(err);
            setError(normalized);
            setFieldErrors(normalized.errors ?? {});
            const couponMessage = firstFieldError(normalized.errors, 'coupon_code');
            if (couponMessage) {
                setCouponError(couponMessage);
                await refreshCart(branchId ? { branch_id: branchId } : {});
            }
        } finally {
            submittingRef.current = false;
            setSubmitting(false);
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-semibold text-stone-900">Checkout</h1>
                <p className="text-sm text-stone-600">Review your order, choose payment, and place it.</p>
            </div>

            {error?.message ? <ErrorState message={error.message} /> : null}

            <form onSubmit={handleSubmit} className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <div className="space-y-4 rounded-2xl bg-white p-5 ring-1 ring-stone-200">
                    <fieldset className="space-y-2">
                        <legend className="text-sm font-semibold text-stone-900">Fulfillment</legend>
                        <div className="flex flex-wrap gap-2">
                            {[
                                ['delivery', 'Delivery'],
                                ['takeaway', 'Takeaway'],
                                ['dine_in', 'Dine-In'],
                            ].map(([value, label]) => (
                                <label
                                    key={value}
                                    className={[
                                        'cursor-pointer rounded-lg px-3 py-2 text-sm ring-1',
                                        type === value
                                            ? 'bg-amber-50 text-amber-950 ring-amber-800'
                                            : 'bg-white text-stone-700 ring-stone-200',
                                    ].join(' ')}
                                >
                                    <input
                                        type="radio"
                                        name="type"
                                        value={value}
                                        checked={type === value}
                                        onChange={() => handleTypeChange(value)}
                                        className="sr-only"
                                    />
                                    {label}
                                </label>
                            ))}
                        </div>
                    </fieldset>

                    <label className="block space-y-1 text-sm">
                        <span className="font-medium text-stone-700">Branch</span>
                        <select
                            required
                            value={branchId}
                            onChange={(event) => setBranchId(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        >
                            <option value="">Select branch</option>
                            {branches.map((branch) => (
                                <option key={branch.id} value={branch.id}>
                                    {branch.name}
                                </option>
                            ))}
                        </select>
                        {firstFieldError(fieldErrors, 'branch_id') ? (
                            <span className="text-xs text-red-600">
                                {firstFieldError(fieldErrors, 'branch_id')}
                            </span>
                        ) : null}
                    </label>

                    {type === 'delivery' ? (
                        <div className="space-y-3">
                            <label className="block space-y-1 text-sm">
                                <span className="font-medium text-stone-700">Delivery zone</span>
                                <select
                                    required
                                    value={deliveryZoneId}
                                    onChange={(event) => setDeliveryZoneId(event.target.value)}
                                    className="w-full rounded-lg border border-stone-300 px-3 py-2"
                                >
                                    <option value="">Select zone</option>
                                    {zones.map((zone) => (
                                        <option key={zone.id} value={zone.id}>
                                            {zone.name} ({formatMoney(zone.delivery_fee)})
                                        </option>
                                    ))}
                                </select>
                            </label>
                            {[
                                ['deliveryRecipientName', 'Name', deliveryRecipientName, setDeliveryRecipientName, 'delivery_recipient_name'],
                                ['deliveryPhone', 'Phone', deliveryPhone, setDeliveryPhone, 'delivery_phone'],
                                ['deliveryLine1', 'Address line 1', deliveryLine1, setDeliveryLine1, 'delivery_line1'],
                                ['deliveryLine2', 'Address line 2', deliveryLine2, setDeliveryLine2, 'delivery_line2'],
                                ['deliveryArea', 'Area', deliveryArea, setDeliveryArea, 'delivery_area'],
                                ['deliveryCity', 'City', deliveryCity, setDeliveryCity, 'delivery_city'],
                            ].map(([key, label, value, setter, errorKey]) => (
                                <label key={key} className="block space-y-1 text-sm">
                                    <span className="font-medium text-stone-700">{label}</span>
                                    <input
                                        type="text"
                                        required={['deliveryRecipientName', 'deliveryPhone', 'deliveryLine1', 'deliveryCity'].includes(key)}
                                        value={value}
                                        onChange={(event) => setter(event.target.value)}
                                        className="w-full rounded-lg border border-stone-300 px-3 py-2"
                                    />
                                    {firstFieldError(fieldErrors, errorKey) ? (
                                        <span className="text-xs text-red-600">
                                            {firstFieldError(fieldErrors, errorKey)}
                                        </span>
                                    ) : null}
                                </label>
                            ))}
                            <label className="block space-y-1 text-sm">
                                <span className="font-medium text-stone-700">Delivery instructions</span>
                                <textarea
                                    value={deliveryInstructions}
                                    onChange={(event) => setDeliveryInstructions(event.target.value)}
                                    className="w-full rounded-lg border border-stone-300 px-3 py-2"
                                    rows={2}
                                />
                            </label>
                        </div>
                    ) : null}

                    {type === 'takeaway' ? (
                        <label className="block space-y-1 text-sm">
                            <span className="font-medium text-stone-700">Pickup notes</span>
                            <textarea
                                value={pickupNotes}
                                onChange={(event) => setPickupNotes(event.target.value)}
                                className="w-full rounded-lg border border-stone-300 px-3 py-2"
                                rows={2}
                            />
                        </label>
                    ) : null}

                    {type === 'dine_in' ? (
                        <label className="block space-y-1 text-sm">
                            <span className="font-medium text-stone-700">Table number</span>
                            <input
                                type="text"
                                required
                                value={tableNumber}
                                onChange={(event) => setTableNumber(event.target.value)}
                                className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            />
                            {firstFieldError(fieldErrors, 'table_number') ? (
                                <span className="text-xs text-red-600">
                                    {firstFieldError(fieldErrors, 'table_number')}
                                </span>
                            ) : null}
                        </label>
                    ) : null}

                    <label className="block space-y-1 text-sm">
                        <span className="font-medium text-stone-700">Order notes</span>
                        <textarea
                            value={customerNotes}
                            onChange={(event) => setCustomerNotes(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            rows={2}
                        />
                    </label>

                    {paymentMethods.length > 0 ? (
                        <fieldset className="space-y-3 border-t border-stone-100 pt-4">
                            <legend className="text-sm font-semibold text-stone-900">Payment method</legend>
                            <div className="space-y-2">
                                {paymentMethods.map((method) => (
                                    <label
                                        key={method.id}
                                        className={[
                                            'flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2 ring-1',
                                            paymentMethod === method.id
                                                ? 'bg-amber-50 ring-amber-800'
                                                : 'bg-white ring-stone-200',
                                        ].join(' ')}
                                    >
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value={method.id}
                                            checked={paymentMethod === method.id}
                                            onChange={() => setPaymentMethod(method.id)}
                                            className="mt-1"
                                        />
                                        <span className="text-sm font-medium text-stone-900">{method.label}</span>
                                    </label>
                                ))}
                            </div>
                            {firstFieldError(fieldErrors, 'payment_method') ? (
                                <p className="text-xs text-red-600">
                                    {firstFieldError(fieldErrors, 'payment_method')}
                                </p>
                            ) : null}

                            {selectedPayment && !selectedPayment.requires_proof ? null : selectedPayment ? (
                                <>
                                    <PaymentMethodDetails
                                        method={selectedPayment}
                                        details={selectedPayment.details}
                                        instructions={selectedPayment.instructions}
                                        amountLabel={formatMoney(estimatedTotal)}
                                        qrImage={selectedPayment.qr_image}
                                    />
                                    <div className="space-y-2 rounded-xl border border-dashed border-stone-300 p-4">
                                        <p className="text-sm font-medium text-stone-900">
                                            Upload payment screenshot
                                        </p>
                                        <p className="text-xs text-stone-500">
                                            Upload a clear screenshot showing your successful payment.
                                        </p>
                                        <input
                                            type="file"
                                            accept={PAYMENT_PROOF_ACCEPTED}
                                            disabled={submitting}
                                            onChange={(event) => {
                                                setProofFile(event.target.files?.[0] ?? null);
                                                setProofError(null);
                                            }}
                                            className="block w-full text-sm text-stone-700 file:mr-3 file:rounded-md file:border-0 file:bg-stone-200 file:px-3 file:py-1.5 file:text-sm file:font-medium"
                                        />
                                        {proofPreviewUrl ? (
                                            <img
                                                src={proofPreviewUrl}
                                                alt="Payment proof preview"
                                                className="max-h-40 rounded-lg border border-stone-200 object-contain"
                                            />
                                        ) : null}
                                        {proofFile && !proofPreviewUrl ? (
                                            <p className="text-sm text-stone-700">{proofFile.name}</p>
                                        ) : null}
                                        {proofError ? (
                                            <p className="text-sm text-red-700">{proofError}</p>
                                        ) : null}
                                    </div>
                                </>
                            ) : null}
                        </fieldset>
                    ) : null}
                </div>

                <div className="space-y-4">
                    <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200">
                        <h2 className="font-semibold text-stone-900">Order summary</h2>
                        <ul className="mt-3 space-y-2 text-sm text-stone-600">
                            {cart.items.map((item) => (
                                <li key={item.id} className="flex justify-between gap-3">
                                    <span>
                                        {item.quantity}× {item.product?.name}
                                        {item.variant ? ` (${item.variant.name})` : ''}
                                    </span>
                                    <span>{item.line_total !== null ? formatMoney(item.line_total) : '—'}</span>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 space-y-1 border-t border-stone-200 pt-3 text-sm">
                            <div className="flex justify-between text-stone-600">
                                <span>Subtotal</span>
                                <span>{formatMoney(cart.subtotal)}</span>
                            </div>

                            <div className="border-t border-dashed border-stone-200 py-3">
                                <p className="font-medium text-stone-900">Have a coupon?</p>
                                <p className="mt-0.5 text-xs text-stone-500">
                                    Enter your coupon code to apply your discount.
                                </p>

                                {appliedCoupon ? (
                                    <div className="mt-3 rounded-xl bg-emerald-50 px-3 py-3 text-sm text-emerald-900">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-semibold">✓ Coupon applied</p>
                                                <p className="mt-0.5">{appliedCoupon.code}</p>
                                                <p className="mt-1 text-xs">
                                                    You saved {formatMoney(appliedCoupon.discount_amount)}
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                disabled={couponBusy || submitting}
                                                onClick={handleRemoveCoupon}
                                                className="shrink-0 text-sm font-medium text-emerald-900 underline disabled:opacity-60"
                                            >
                                                {couponBusy ? 'Removing…' : 'Remove'}
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="mt-3 flex flex-col gap-2 sm:flex-row">
                                        <label className="sr-only" htmlFor="checkout-coupon-code">
                                            Coupon code
                                        </label>
                                        <input
                                            id="checkout-coupon-code"
                                            name="coupon_code"
                                            value={couponCode}
                                            onChange={(event) => {
                                                setCouponCode(event.target.value);
                                                setCouponError(null);
                                            }}
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter') {
                                                    handleApplyCoupon(event);
                                                }
                                            }}
                                            disabled={couponBusy || submitting}
                                            placeholder="Enter coupon code"
                                            autoComplete="off"
                                            aria-invalid={Boolean(couponError)}
                                            aria-describedby={couponError ? 'checkout-coupon-error' : undefined}
                                            className="w-full flex-1 rounded-xl border border-stone-300 px-3 py-2 text-sm disabled:opacity-60"
                                        />
                                        <button
                                            type="button"
                                            disabled={couponBusy || submitting}
                                            onClick={handleApplyCoupon}
                                            className="rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                                        >
                                            {couponBusy ? 'Applying…' : 'Apply'}
                                        </button>
                                    </div>
                                )}

                                {couponError ? (
                                    <p id="checkout-coupon-error" className="mt-2 text-xs text-red-600" role="alert">
                                        {couponError}
                                    </p>
                                ) : null}
                            </div>

                            {offerDiscount > 0 ? (
                                <div className="flex justify-between text-stone-600">
                                    <span>
                                        {cart.discount_label
                                            ? `Discount (${cart.discount_label})`
                                            : 'Discount'}
                                    </span>
                                    <span>-{formatMoney(offerDiscount)}</span>
                                </div>
                            ) : null}
                            {couponDiscount > 0 && appliedCoupon ? (
                                <div className="flex justify-between text-stone-600">
                                    <span>Coupon - {appliedCoupon.code}</span>
                                    <span>-{formatMoney(couponDiscount)}</span>
                                </div>
                            ) : null}
                            <div className="flex justify-between text-stone-600">
                                <span>Delivery fee</span>
                                <span>{formatMoney(estimatedDeliveryFee)}</span>
                            </div>
                            <div className="flex justify-between font-semibold text-stone-900">
                                <span>Estimated total</span>
                                <span>{formatMoney(estimatedTotal)}</span>
                            </div>
                        </div>
                        <p className="mt-2 text-xs text-stone-500">
                            Final totals are calculated on the server when you place the order.
                        </p>
                    </div>

                    <button
                        type="submit"
                        disabled={submitting || !paymentMethod}
                        className="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                    >
                        {submitting ? 'Placing order…' : 'Place order'}
                    </button>
                    <Link to="/cart" className="block text-center text-sm font-medium text-amber-900 hover:underline">
                        Back to cart
                    </Link>
                </div>
            </form>
        </div>
    );
}

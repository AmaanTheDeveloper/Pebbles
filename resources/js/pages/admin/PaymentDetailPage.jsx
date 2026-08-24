import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { fetchAdminPayment } from '../../api/admin/payments';
import { fetchPaymentProof, rejectOrderPayment, verifyOrderPayment } from '../../api/admin/orders';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminTextareaField } from '../../components/admin/AdminFormField';
import StatusBadge from '../../components/admin/StatusBadge';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { formatDate, formatMoney, humanizeStatus } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';

export default function PaymentDetailPage() {
    const { id } = useParams();
    const [payment, setPayment] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const [flash, setFlash] = useState(null);
    const [note, setNote] = useState('');
    const [reason, setReason] = useState('');
    const [proofUrl, setProofUrl] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await fetchAdminPayment(id);
            setPayment(data);
        } catch (err) {
            setError(normalizeApiError(err));
            setPayment(null);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [id]);

    useEffect(() => {
        let objectUrl;

        const loadProof = async () => {
            if (!payment?.proof?.id || !payment?.order_number) {
                setProofUrl(null);
                return;
            }

            try {
                const blob = await fetchPaymentProof(payment.order_number, payment.proof.id);
                objectUrl = URL.createObjectURL(blob);
                setProofUrl(objectUrl);
            } catch {
                setProofUrl(null);
            }
        };

        loadProof();

        return () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        };
    }, [payment?.proof?.id, payment?.order_number]);

    const handleVerify = async () => {
        if (!payment?.order_number) {
            return;
        }

        setSaving(true);
        setFlash(null);

        try {
            await verifyOrderPayment(payment.order_number, { note: note || undefined });
            setFlash({ type: 'success', message: 'Payment verified.' });
            await load();
            setNote('');
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const handleReject = async () => {
        if (!payment?.order_number) {
            return;
        }

        setSaving(true);
        setFlash(null);

        try {
            await rejectOrderPayment(payment.order_number, { reason: reason || undefined });
            setFlash({ type: 'success', message: 'Payment rejected.' });
            await load();
            setReason('');
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading payment…" />;
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={load} />;
    }

    if (!payment) {
        return null;
    }

    const canVerify =
        payment.status === 'submitted' ||
        (payment.method === 'cod' && payment.status === 'pending');
    const canReject = payment.status === 'submitted';

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={`Payment #${payment.id}`}
                description={payment.order_number ? `Order ${payment.order_number}` : 'Payment details'}
                actions={
                    <Link to="/admin/payments" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to payments
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <div className="grid gap-6 lg:grid-cols-2">
                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <div className="flex flex-wrap gap-2">
                            <StatusBadge status={payment.status} kind="payment" />
                            {payment.order_status ? <StatusBadge status={payment.order_status} /> : null}
                        </div>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-stone-600">Method</dt>
                                <dd>{humanizeStatus(payment.method)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-stone-600">Amount</dt>
                                <dd className="font-medium">{formatMoney(payment.amount)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-stone-600">Created</dt>
                                <dd>{formatDate(payment.created_at)}</dd>
                            </div>
                            {payment.transaction_reference ? (
                                <div className="flex justify-between">
                                    <dt className="text-stone-600">Reference</dt>
                                    <dd>{payment.transaction_reference}</dd>
                                </div>
                            ) : null}
                            {payment.method === 'jazzcash' && payment.method_details ? (
                                <>
                                    {payment.method_details.business_name ? (
                                        <div className="flex justify-between">
                                            <dt className="text-stone-600">Business</dt>
                                            <dd>{payment.method_details.business_name}</dd>
                                        </div>
                                    ) : null}
                                    {payment.method_details.till_id ? (
                                        <div className="flex justify-between">
                                            <dt className="text-stone-600">Till ID</dt>
                                            <dd>{payment.method_details.till_id}</dd>
                                        </div>
                                    ) : null}
                                </>
                            ) : null}
                        </dl>
                        {payment.order_number ? (
                            <Link
                                to={`/admin/orders/${payment.order_number}`}
                                className="inline-flex text-sm font-medium text-amber-900 hover:underline"
                            >
                                View full order
                            </Link>
                        ) : null}
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-sm font-semibold text-stone-900">Payment proof</h2>
                        {payment.proof ? (
                            <>
                                <p className="text-sm">{payment.proof.original_filename}</p>
                                <p className="text-xs text-stone-500">Uploaded {formatDate(payment.proof.uploaded_at)}</p>
                                {proofUrl && payment.proof.mime_type?.startsWith('image/') ? (
                                    <img src={proofUrl} alt="Payment proof" className="max-h-64 rounded-md border" />
                                ) : null}
                                {proofUrl ? (
                                    <a
                                        href={proofUrl}
                                        download={payment.proof.original_filename || 'payment-proof'}
                                        className="text-sm font-medium text-amber-900 hover:underline"
                                    >
                                        Download proof
                                    </a>
                                ) : (
                                    <p className="text-sm text-stone-500">Unable to load proof preview.</p>
                                )}
                            </>
                        ) : (
                            <p className="text-sm text-stone-500">No proof uploaded.</p>
                        )}

                        {canVerify || canReject ? (
                            <div className="space-y-3 border-t border-stone-100 pt-4">
                                {canVerify ? (
                                    <>
                                        <AdminTextareaField
                                            label="Verification note"
                                            name="note"
                                            value={note}
                                            onChange={(event) => setNote(event.target.value)}
                                            rows={2}
                                        />
                                        <button
                                            type="button"
                                            onClick={handleVerify}
                                            disabled={saving}
                                            className="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600 disabled:opacity-60"
                                        >
                                            Approve payment
                                        </button>
                                    </>
                                ) : null}
                                {canReject ? (
                                    <>
                                        <AdminTextareaField
                                            label="Rejection reason"
                                            name="reason"
                                            value={reason}
                                            onChange={(event) => setReason(event.target.value)}
                                            rows={2}
                                        />
                                        <button
                                            type="button"
                                            onClick={handleReject}
                                            disabled={saving}
                                            className="w-full rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50 disabled:opacity-60"
                                        >
                                            Reject payment
                                        </button>
                                    </>
                                ) : null}
                            </div>
                        ) : null}
                    </AdminPanelBody>
                </AdminPanel>
            </div>
        </div>
    );
}

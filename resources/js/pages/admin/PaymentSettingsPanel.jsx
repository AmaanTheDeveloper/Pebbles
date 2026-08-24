import { useEffect, useState } from 'react';
import {
    removeAdminJazzcashQrImage,
    updateAdminPaymentSettings,
    uploadAdminJazzcashQrImage,
} from '../../api/admin/settings';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminTextareaField } from '../../components/admin/AdminFormField';
import { AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

function ToggleField({ label, checked, onChange, hint }) {
    return (
        <label className="flex items-start gap-3 rounded-lg border border-stone-200 px-3 py-3">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="mt-1"
            />
            <span className="space-y-1">
                <span className="block text-sm font-medium text-stone-900">{label}</span>
                {hint ? <span className="block text-xs text-stone-500">{hint}</span> : null}
            </span>
        </label>
    );
}

export default function PaymentSettingsPanel({ initialPayment, onUpdated, setFlash }) {
    const [form, setForm] = useState(initialPayment);
    const [saving, setSaving] = useState(false);
    const [localFlash, setLocalFlash] = useState(null);
    const [qrPreview, setQrPreview] = useState(null);
    const [qrFile, setQrFile] = useState(null);
    const [qrSaving, setQrSaving] = useState(false);
    const [qrRemoving, setQrRemoving] = useState(false);

    useEffect(() => {
        setForm(initialPayment);
    }, [initialPayment]);

    useEffect(() => {
        if (!qrFile) {
            setQrPreview(null);
            return undefined;
        }

        const url = URL.createObjectURL(qrFile);
        setQrPreview(url);

        return () => {
            URL.revokeObjectURL(url);
        };
    }, [qrFile]);

    const updateMethod = (method, enabled) => {
        setForm((current) => ({
            ...current,
            methods: {
                ...current.methods,
                [method]: { ...current.methods?.[method], enabled },
            },
        }));
    };

    const updateSection = (section, field, value) => {
        setForm((current) => ({
            ...current,
            [section]: {
                ...current[section],
                [field]: value,
            },
        }));
    };

    const handleSave = async (event) => {
        event.preventDefault();
        setSaving(true);
        setLocalFlash(null);

        try {
            const response = await updateAdminPaymentSettings(form);
            const next = response.data?.payment ?? response.payment ?? form;
            setForm(next);
            onUpdated?.(next);
            const message = response.message ?? 'Payment settings saved.';
            setLocalFlash({ type: 'success', message });
            setFlash?.({ type: 'success', message });
        } catch (err) {
            const message = normalizeApiError(err).message;
            setLocalFlash({ type: 'error', message });
            setFlash?.({ type: 'error', message });
        } finally {
            setSaving(false);
        }
    };

    const handleQrUpload = async () => {
        if (!qrFile || qrSaving) {
            return;
        }

        setQrSaving(true);
        setLocalFlash(null);

        try {
            const response = await uploadAdminJazzcashQrImage(qrFile);
            const next = response.data?.payment ?? form;
            setForm(next);
            onUpdated?.(next);
            setQrFile(null);
            setQrPreview(null);
            const message = response.message ?? 'JazzCash QR image updated.';
            setLocalFlash({ type: 'success', message });
            setFlash?.({ type: 'success', message });
        } catch (err) {
            const message = normalizeApiError(err).message;
            setLocalFlash({ type: 'error', message });
            setFlash?.({ type: 'error', message });
        } finally {
            setQrSaving(false);
        }
    };

    const handleQrRemove = async () => {
        if (qrRemoving) {
            return;
        }

        setQrRemoving(true);
        setLocalFlash(null);

        try {
            const response = await removeAdminJazzcashQrImage();
            const next = response.data?.payment ?? form;
            setForm(next);
            onUpdated?.(next);
            setQrFile(null);
            setQrPreview(null);
            const message = response.message ?? 'JazzCash QR image removed.';
            setLocalFlash({ type: 'success', message });
            setFlash?.({ type: 'success', message });
        } catch (err) {
            const message = normalizeApiError(err).message;
            setLocalFlash({ type: 'error', message });
            setFlash?.({ type: 'error', message });
        } finally {
            setQrRemoving(false);
        }
    };

    return (
        <form onSubmit={handleSave} className="space-y-6">
            {localFlash ? (
                <FlashMessage
                    type={localFlash.type}
                    message={localFlash.message}
                    onDismiss={() => setLocalFlash(null)}
                />
            ) : null}

            <AdminPanel>
                <AdminPanelBody className="space-y-4">
                    <div>
                        <h2 className="text-lg font-semibold text-stone-900">Payments</h2>
                        <p className="text-sm text-stone-500">
                            Enable payment methods and configure JazzCash and bank transfer details shown at checkout.
                        </p>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <ToggleField
                            label="Cash on Delivery"
                            checked={form.methods?.cod?.enabled ?? true}
                            onChange={(enabled) => updateMethod('cod', enabled)}
                            hint="Pay cash on delivery. No proof required."
                        />
                        <ToggleField
                            label="JazzCash"
                            checked={form.methods?.jazzcash?.enabled ?? true}
                            onChange={(enabled) => updateMethod('jazzcash', enabled)}
                            hint="Manual JazzCash business account with proof upload."
                        />
                        <ToggleField
                            label="Bank Transfer"
                            checked={form.methods?.bank_transfer?.enabled ?? true}
                            onChange={(enabled) => updateMethod('bank_transfer', enabled)}
                            hint="Manual bank transfer with proof upload."
                        />
                    </div>
                </AdminPanelBody>
            </AdminPanel>

            <AdminPanel>
                <AdminPanelBody className="space-y-4">
                    <h3 className="text-sm font-semibold text-stone-900">JazzCash business account</h3>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField
                            label="Business name"
                            name="jazzcash_business_name"
                            value={form.jazzcash?.business_name ?? ''}
                            onChange={(event) => updateSection('jazzcash', 'business_name', event.target.value)}
                        />
                        <AdminFormField
                            label="Till ID"
                            name="jazzcash_till_id"
                            value={form.jazzcash?.till_id ?? ''}
                            onChange={(event) => updateSection('jazzcash', 'till_id', event.target.value)}
                            hint="Business Till ID, not a mobile number."
                        />
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-stone-700">Current QR image</p>
                            {form.jazzcash?.qr_image?.url ? (
                                <img
                                    src={form.jazzcash.qr_image.url}
                                    alt="Current JazzCash QR"
                                    className="max-h-64 w-full max-w-sm rounded-lg border border-stone-200 object-contain"
                                />
                            ) : (
                                <p className="text-sm text-stone-500">No QR image uploaded yet.</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-stone-700">New QR image</p>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp"
                                onChange={(event) => setQrFile(event.target.files?.[0] ?? null)}
                                className="block w-full text-sm text-stone-700"
                            />
                            {qrPreview ? (
                                <img
                                    src={qrPreview}
                                    alt="New JazzCash QR preview"
                                    className="max-h-64 w-full max-w-sm rounded-lg border border-stone-200 object-contain"
                                />
                            ) : null}
                            <div className="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    onClick={handleQrUpload}
                                    disabled={!qrFile || qrSaving}
                                    className="rounded-md bg-stone-900 px-3 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                                >
                                    {qrSaving ? 'Uploading…' : 'Upload QR image'}
                                </button>
                                {form.jazzcash?.qr_image?.url ? (
                                    <button
                                        type="button"
                                        onClick={handleQrRemove}
                                        disabled={qrRemoving}
                                        className="rounded-md border border-stone-300 px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-60"
                                    >
                                        {qrRemoving ? 'Removing…' : 'Remove QR image'}
                                    </button>
                                ) : null}
                            </div>
                        </div>
                    </div>

                    <AdminTextareaField
                        label="Instructions"
                        name="jazzcash_instructions"
                        rows={3}
                        value={form.jazzcash?.instructions ?? ''}
                        onChange={(event) => updateSection('jazzcash', 'instructions', event.target.value)}
                    />
                </AdminPanelBody>
            </AdminPanel>

            <AdminPanel>
                <AdminPanelBody className="space-y-4">
                    <h3 className="text-sm font-semibold text-stone-900">Bank transfer details</h3>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField
                            label="Bank name"
                            name="bank_name"
                            value={form.bank_transfer?.bank_name ?? ''}
                            onChange={(event) => updateSection('bank_transfer', 'bank_name', event.target.value)}
                        />
                        <AdminFormField
                            label="Account title"
                            name="bank_account_title"
                            value={form.bank_transfer?.account_title ?? ''}
                            onChange={(event) => updateSection('bank_transfer', 'account_title', event.target.value)}
                        />
                        <AdminFormField
                            label="Account number"
                            name="bank_account_number"
                            value={form.bank_transfer?.account_number ?? ''}
                            onChange={(event) => updateSection('bank_transfer', 'account_number', event.target.value)}
                        />
                        <AdminFormField
                            label="IBAN"
                            name="bank_iban"
                            value={form.bank_transfer?.iban ?? ''}
                            onChange={(event) => updateSection('bank_transfer', 'iban', event.target.value)}
                        />
                        <AdminFormField
                            label="Branch / bank code"
                            name="bank_branch_code"
                            value={form.bank_transfer?.branch_code ?? ''}
                            onChange={(event) => updateSection('bank_transfer', 'branch_code', event.target.value)}
                        />
                    </div>
                    <AdminTextareaField
                        label="Instructions"
                        name="bank_instructions"
                        rows={3}
                        value={form.bank_transfer?.instructions ?? ''}
                        onChange={(event) => updateSection('bank_transfer', 'instructions', event.target.value)}
                    />
                </AdminPanelBody>
            </AdminPanel>

            <button
                type="submit"
                disabled={saving}
                className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
            >
                {saving ? 'Saving…' : 'Save payment settings'}
            </button>
        </form>
    );
}

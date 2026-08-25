import { useEffect, useState } from 'react';
import {
    fetchAdminSettings,
    removeAdminFavicon,
    removeAdminLogo,
    updateAdminSettings,
    uploadAdminFavicon,
    uploadAdminLogo,
} from '../../api/admin/settings';
import OpeningHoursEditor from '../../components/admin/OpeningHoursEditor';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField } from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { defaultOpeningHours, normalizeOpeningHours } from '../../lib/contactHelpers';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import PopupSettingsPanel from './PopupSettingsPanel';
import PaymentSettingsPanel from './PaymentSettingsPanel';
import { normalizeApiError } from '../../lib/errors';

const emptyContactForm = {
    phone: '',
    whatsapp: '',
    email: '',
    notification_email: '',
    address: '',
    latitude: '',
    longitude: '',
    map_url: '',
    timezone: '',
    social_instagram: '',
    social_facebook: '',
    social_tiktok: '',
    social_youtube: '',
    opening_hours: defaultOpeningHours(),
};

export default function SettingsPage() {
    const [orderControls, setOrderControls] = useState({
        customer_order_cancellation_enabled: true,
        customer_order_modification_enabled: true,
        order_estimated_minutes: '30',
    });
    const [contact, setContact] = useState(emptyContactForm);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});
    const [flash, setFlash] = useState(null);
    const [logo, setLogo] = useState(null);
    const [logoPreview, setLogoPreview] = useState(null);
    const [logoFile, setLogoFile] = useState(null);
    const [logoSaving, setLogoSaving] = useState(false);
    const [logoRemoving, setLogoRemoving] = useState(false);
    const [favicon, setFavicon] = useState(null);
    const [faviconPreview, setFaviconPreview] = useState(null);
    const [faviconFile, setFaviconFile] = useState(null);
    const [faviconSaving, setFaviconSaving] = useState(false);
    const [faviconRemoving, setFaviconRemoving] = useState(false);
    const [popup, setPopup] = useState(null);
    const [payment, setPayment] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const settings = await fetchAdminSettings();
            setOrderControls({
                customer_order_cancellation_enabled: settings.customer_order_cancellation_enabled ?? true,
                customer_order_modification_enabled: settings.customer_order_modification_enabled ?? true,
                order_estimated_minutes: String(settings.order_estimated_minutes ?? '30'),
            });
            setContact({
                phone: settings.phone ?? '',
                whatsapp: settings.whatsapp ?? '',
                email: settings.email ?? '',
                notification_email: settings.notification_email ?? '',
                address: settings.address ?? '',
                latitude: settings.latitude ?? '',
                longitude: settings.longitude ?? '',
                map_url: settings.map_url ?? '',
                timezone: settings.timezone ?? '',
                social_instagram: settings.social?.instagram ?? '',
                social_facebook: settings.social?.facebook ?? '',
                social_tiktok: settings.social?.tiktok ?? '',
                social_youtube: settings.social?.youtube ?? '',
                opening_hours: normalizeOpeningHours(settings.opening_hours),
            });
            setLogo(settings.logo ?? null);
            setFavicon(settings.favicon ?? null);
            setPopup(settings.popup ?? null);
            setPayment(settings.payment ?? null);
            setLogoPreview(null);
            setLogoFile(null);
            setFaviconPreview(null);
            setFaviconFile(null);
        } catch (err) {
            setError(normalizeApiError(err));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        try {
            const updated = await updateAdminSettings({
                customer_order_cancellation_enabled: orderControls.customer_order_cancellation_enabled,
                customer_order_modification_enabled: orderControls.customer_order_modification_enabled,
                order_estimated_minutes: Number(orderControls.order_estimated_minutes),
                phone: contact.phone || null,
                whatsapp: contact.whatsapp || null,
                email: contact.email || null,
                notification_email: contact.notification_email || null,
                address: contact.address || null,
                latitude: contact.latitude === '' ? null : Number(contact.latitude),
                longitude: contact.longitude === '' ? null : Number(contact.longitude),
                map_url: contact.map_url || null,
                timezone: contact.timezone || null,
                social_instagram: contact.social_instagram || null,
                social_facebook: contact.social_facebook || null,
                social_tiktok: contact.social_tiktok || null,
                social_youtube: contact.social_youtube || null,
                opening_hours: contact.opening_hours,
            });
            setOrderControls({
                customer_order_cancellation_enabled: updated.customer_order_cancellation_enabled ?? true,
                customer_order_modification_enabled: updated.customer_order_modification_enabled ?? true,
                order_estimated_minutes: String(updated.order_estimated_minutes ?? '30'),
            });
            setFlash({ type: 'success', message: 'Settings saved.' });
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors);
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setSaving(false);
        }
    };

    const handleLogoSelect = (event) => {
        const file = event.target.files?.[0] ?? null;
        setLogoFile(file);
        setLogoPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleLogoSave = async () => {
        if (!logoFile) {
            setFlash({ type: 'error', message: 'Select a logo image before saving.' });
            return;
        }

        setLogoSaving(true);
        setFlash(null);

        try {
            const response = await uploadAdminLogo(logoFile);
            setLogo(response.data?.logo ?? null);
            setLogoFile(null);
            setLogoPreview(null);
            setFlash({ type: 'success', message: response.message || 'Logo updated successfully.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setLogoSaving(false);
        }
    };

    const handleLogoRemove = async () => {
        if (!window.confirm('Remove the current website logo?')) {
            return;
        }

        setLogoRemoving(true);
        setFlash(null);

        try {
            const response = await removeAdminLogo();
            setLogo(response.data?.logo ?? null);
            setLogoFile(null);
            setLogoPreview(null);
            setFlash({ type: 'success', message: response.message || 'Logo removed successfully.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setLogoRemoving(false);
        }
    };

    const handleFaviconSelect = (event) => {
        const file = event.target.files?.[0] ?? null;
        setFaviconFile(file);
        setFaviconPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleFaviconSave = async () => {
        if (!faviconFile) {
            setFlash({ type: 'error', message: 'Select a favicon before saving.' });
            return;
        }

        setFaviconSaving(true);
        setFlash(null);

        try {
            const response = await uploadAdminFavicon(faviconFile);
            setFavicon(response.data?.favicon ?? null);
            setFaviconFile(null);
            setFaviconPreview(null);
            setFlash({ type: 'success', message: response.message || 'Favicon updated successfully.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setFaviconSaving(false);
        }
    };

    const handleFaviconRemove = async () => {
        if (!window.confirm('Remove the current website favicon?')) {
            return;
        }

        setFaviconRemoving(true);
        setFlash(null);

        try {
            const response = await removeAdminFavicon();
            setFavicon(response.data?.favicon ?? null);
            setFaviconFile(null);
            setFaviconPreview(null);
            setFlash({ type: 'success', message: response.message || 'Favicon removed successfully.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setFaviconRemoving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading settings…" />;
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={load} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Settings"
                description="Configure store operations and public contact information."
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <AdminPanelBody className="space-y-5">
                    <div>
                        <h2 className="text-lg font-semibold text-stone-900">Branding</h2>
                        <p className="mt-1 text-sm text-stone-600">
                            Upload one global logo used across the navbar, footer, and sign-in screens.
                        </p>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-stone-700">Current logo</p>
                            {logo?.url ? (
                                <img
                                    src={logo.url}
                                    alt="Current website logo"
                                    className="h-16 max-w-xs rounded-lg border border-stone-200 bg-white object-contain p-3"
                                />
                            ) : (
                                <div className="flex h-16 max-w-xs items-center rounded-lg border border-dashed border-stone-300 bg-stone-50 px-4 text-sm text-stone-500">
                                    No custom logo configured
                                </div>
                            )}
                        </div>

                        <div className="space-y-2">
                            <p className="text-sm font-medium text-stone-700">New logo preview</p>
                            {logoPreview ? (
                                <img
                                    src={logoPreview}
                                    alt="Selected logo preview"
                                    className="h-16 max-w-xs rounded-lg border border-stone-200 bg-white object-contain p-3"
                                />
                            ) : (
                                <div className="flex h-16 max-w-xs items-center rounded-lg border border-dashed border-stone-300 bg-stone-50 px-4 text-sm text-stone-500">
                                    Select an image to preview
                                </div>
                            )}
                        </div>
                    </div>

                    <AdminFormField
                        label="Upload logo"
                        name="site_logo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={handleLogoSelect}
                        hint="PNG, JPG, or WebP up to 2 MB. Transparent backgrounds are supported."
                    />

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={handleLogoSave}
                            disabled={logoSaving || !logoFile}
                            className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                        >
                            {logoSaving ? 'Saving…' : 'Save logo'}
                        </button>
                        {logo?.url ? (
                            <button
                                type="button"
                                onClick={handleLogoRemove}
                                disabled={logoRemoving}
                                className="rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-60"
                            >
                                {logoRemoving ? 'Removing…' : 'Remove logo'}
                            </button>
                        ) : null}
                    </div>
                </AdminPanelBody>
            </AdminPanel>

            <AdminPanel>
                <AdminPanelBody className="space-y-5">
                    <div>
                        <h2 className="text-lg font-semibold text-stone-900">Favicon</h2>
                        <p className="mt-1 text-sm text-stone-600">
                            Upload a browser tab icon shown across the storefront.
                        </p>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-stone-700">Current favicon</p>
                            {favicon?.url ? (
                                <img
                                    src={favicon.url}
                                    alt="Current website favicon"
                                    className="size-12 rounded-lg border border-stone-200 bg-white object-contain p-2"
                                />
                            ) : (
                                <div className="flex size-12 items-center justify-center rounded-lg border border-dashed border-stone-300 bg-stone-50 text-xs text-stone-500">
                                    None
                                </div>
                            )}
                        </div>

                        <div className="space-y-2">
                            <p className="text-sm font-medium text-stone-700">New favicon preview</p>
                            {faviconPreview ? (
                                <img
                                    src={faviconPreview}
                                    alt="Selected favicon preview"
                                    className="size-12 rounded-lg border border-stone-200 bg-white object-contain p-2"
                                />
                            ) : (
                                <div className="flex size-12 items-center justify-center rounded-lg border border-dashed border-stone-300 bg-stone-50 text-xs text-stone-500">
                                    —
                                </div>
                            )}
                        </div>
                    </div>

                    <AdminFormField
                        label="Upload favicon"
                        name="site_favicon"
                        type="file"
                        accept=".ico,image/png,image/jpeg,image/webp,image/svg+xml"
                        onChange={handleFaviconSelect}
                        hint="ICO, PNG, JPG, WebP, or SVG up to 1 MB."
                    />

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={handleFaviconSave}
                            disabled={faviconSaving || !faviconFile}
                            className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                        >
                            {faviconSaving ? 'Saving…' : 'Save favicon'}
                        </button>
                        {favicon?.url ? (
                            <button
                                type="button"
                                onClick={handleFaviconRemove}
                                disabled={faviconRemoving}
                                className="rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-60"
                            >
                                {faviconRemoving ? 'Removing…' : 'Remove favicon'}
                            </button>
                        ) : null}
                    </div>
                </AdminPanelBody>
            </AdminPanel>

            {popup ? <PopupSettingsPanel initialPopup={popup} onUpdated={setPopup} setFlash={setFlash} /> : null}

            {payment ? (
                <PaymentSettingsPanel initialPayment={payment} onUpdated={setPayment} setFlash={setFlash} />
            ) : null}

            <form onSubmit={handleSubmit} className="space-y-6">
                <AdminPanel>
                    <AdminPanelBody className="max-w-lg space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Customer order changes</h2>
                        <label className="flex items-start gap-3 rounded-lg border border-stone-200 px-3 py-3">
                            <input
                                type="checkbox"
                                checked={orderControls.customer_order_cancellation_enabled}
                                onChange={(event) =>
                                    setOrderControls((current) => ({
                                        ...current,
                                        customer_order_cancellation_enabled: event.target.checked,
                                    }))
                                }
                                className="mt-1"
                            />
                            <span className="space-y-1">
                                <span className="block text-sm font-medium text-stone-900">Customer can cancel orders</span>
                                <span className="block text-xs text-stone-500">
                                    Allow customers to cancel orders while they are still pending confirmation.
                                </span>
                            </span>
                        </label>
                        <label className="flex items-start gap-3 rounded-lg border border-stone-200 px-3 py-3">
                            <input
                                type="checkbox"
                                checked={orderControls.customer_order_modification_enabled}
                                onChange={(event) =>
                                    setOrderControls((current) => ({
                                        ...current,
                                        customer_order_modification_enabled: event.target.checked,
                                    }))
                                }
                                className="mt-1"
                            />
                            <span className="space-y-1">
                                <span className="block text-sm font-medium text-stone-900">Customer can modify orders</span>
                                <span className="block text-xs text-stone-500">
                                    Allow customers to modify orders while they are still pending confirmation.
                                </span>
                            </span>
                        </label>
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="max-w-lg space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Estimated order time</h2>
                        <AdminFormField
                            label="Estimated order time (minutes)"
                            name="order_estimated_minutes"
                            type="number"
                            min="5"
                            max="240"
                            required
                            value={orderControls.order_estimated_minutes}
                            onChange={(event) =>
                                setOrderControls((current) => ({
                                    ...current,
                                    order_estimated_minutes: event.target.value,
                                }))
                            }
                            hint="Shown to customers after the order is confirmed. Snapshotted when each order is placed."
                            errors={fieldErrors}
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Contact information</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Business phone" name="phone" value={contact.phone} onChange={(event) => setContact((current) => ({ ...current, phone: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="WhatsApp number" name="whatsapp" value={contact.whatsapp} onChange={(event) => setContact((current) => ({ ...current, whatsapp: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="Public email" name="email" type="email" value={contact.email} onChange={(event) => setContact((current) => ({ ...current, email: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="Notification email" name="notification_email" type="email" value={contact.notification_email} onChange={(event) => setContact((current) => ({ ...current, notification_email: event.target.value }))} errors={fieldErrors} hint="Optional destination for future contact notifications." />
                            <div className="sm:col-span-2">
                                <AdminFormField label="Address" name="address" value={contact.address} onChange={(event) => setContact((current) => ({ ...current, address: event.target.value }))} errors={fieldErrors} />
                            </div>
                            <AdminFormField label="Latitude" name="latitude" type="number" step="any" value={contact.latitude} onChange={(event) => setContact((current) => ({ ...current, latitude: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="Longitude" name="longitude" type="number" step="any" value={contact.longitude} onChange={(event) => setContact((current) => ({ ...current, longitude: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="Google Maps URL" name="map_url" value={contact.map_url} onChange={(event) => setContact((current) => ({ ...current, map_url: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="Timezone" name="timezone" value={contact.timezone} onChange={(event) => setContact((current) => ({ ...current, timezone: event.target.value }))} hint="Example: Asia/Karachi" errors={fieldErrors} />
                        </div>
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Social media</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Instagram URL" name="social_instagram" value={contact.social_instagram} onChange={(event) => setContact((current) => ({ ...current, social_instagram: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="Facebook URL" name="social_facebook" value={contact.social_facebook} onChange={(event) => setContact((current) => ({ ...current, social_facebook: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="TikTok URL" name="social_tiktok" value={contact.social_tiktok} onChange={(event) => setContact((current) => ({ ...current, social_tiktok: event.target.value }))} errors={fieldErrors} />
                            <AdminFormField label="YouTube URL" name="social_youtube" value={contact.social_youtube} onChange={(event) => setContact((current) => ({ ...current, social_youtube: event.target.value }))} errors={fieldErrors} />
                        </div>
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Opening hours</h2>
                        <OpeningHoursEditor
                            value={contact.opening_hours}
                            onChange={(opening_hours) => setContact((current) => ({ ...current, opening_hours }))}
                            errors={fieldErrors}
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <button
                    type="submit"
                    disabled={saving}
                    className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {saving ? 'Saving…' : 'Save settings'}
                </button>
            </form>
        </div>
    );
}

import { useEffect, useMemo, useState } from 'react';
import { fetchAdminOffers } from '../../api/admin/offers';
import {
    removeAdminPopupImage,
    updateAdminPopupSettings,
    uploadAdminPopupImage,
} from '../../api/admin/settings';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField, AdminTextareaField } from '../../components/admin/AdminFormField';
import WelcomePopupModal from '../../components/storefront/WelcomePopupModal';
import { AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const typeOptions = [
    { value: 'welcome_offer', label: 'Welcome offer' },
    { value: 'new_product', label: 'New product' },
    { value: 'special_promotion', label: 'Special promotion' },
    { value: 'announcement', label: 'Announcement' },
];

const delayOptions = [
    { value: '500', label: 'After 500ms' },
    { value: '700', label: 'After 700ms (default)' },
    { value: '1000', label: 'After 1 second' },
    { value: '2000', label: 'After 2 seconds' },
];

const frequencyOptions = [
    { value: 'session', label: 'Once per session' },
    { value: 'day', label: 'Once per day' },
    { value: 'visit', label: 'Every visit' },
];

function StatusBadge({ active }) {
    return (
        <span
            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700'
            }`}
        >
            {active ? 'Active' : 'Inactive'}
        </span>
    );
}

export default function PopupSettingsPanel({ initialPopup, onUpdated, setFlash }) {
    const [form, setForm] = useState(initialPopup);
    const [offers, setOffers] = useState([]);
    const [saving, setSaving] = useState(false);
    const [imageSaving, setImageSaving] = useState(false);
    const [imageRemoving, setImageRemoving] = useState(false);
    const [imagePreview, setImagePreview] = useState(null);
    const [imageFile, setImageFile] = useState(null);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [localFlash, setLocalFlash] = useState(null);

    useEffect(() => {
        fetchAdminOffers({ per_page: 100 })
            .then((response) => setOffers(response.data ?? []))
            .catch(() => setOffers([]));
    }, []);

    const previewPopup = useMemo(
        () => ({
            ...form,
            is_active: true,
            image: imagePreview
                ? { url: imagePreview }
                : form.image?.url
                  ? { url: form.image.url }
                  : null,
        }),
        [form, imagePreview],
    );

    const handleSave = async (event) => {
        event.preventDefault();
        setSaving(true);
        setLocalFlash(null);

        try {
            if (imageFile) {
                setImageSaving(true);
                const imageResponse = await uploadAdminPopupImage(imageFile);
                setForm(imageResponse.data?.popup ?? form);
                setImageFile(null);
                setImagePreview(null);
                setImageSaving(false);
            }

            const response = await updateAdminPopupSettings({
                is_active: form.is_active,
                type: form.type,
                heading: form.heading || null,
                message: form.message || null,
                description: form.description || null,
                badge_text: form.badge_text || null,
                button_text: form.button_text || null,
                button_url: form.button_url || null,
                delay_ms: Number(form.delay_ms ?? 700),
                frequency: form.frequency,
                offer_id: form.offer_id ? Number(form.offer_id) : null,
            });

            const updated = response.data?.popup ?? form;
            setForm(updated);
            onUpdated?.(updated);
            setFlash?.({ type: 'success', message: response.message || 'Popup settings updated successfully.' });
        } catch (err) {
            setFlash?.({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
            setImageSaving(false);
        }
    };

    const handleRemoveImage = async () => {
        setImageRemoving(true);
        try {
            const response = await removeAdminPopupImage();
            setForm(response.data?.popup ?? { ...form, image: null });
            setImageFile(null);
            setImagePreview(null);
            setFlash?.({ type: 'success', message: response.message || 'Popup settings updated successfully.' });
        } catch (err) {
            setFlash?.({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setImageRemoving(false);
        }
    };

    return (
        <>
            <AdminPanel>
                <AdminPanelBody className="space-y-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold text-stone-900">Welcome popup</h2>
                            <p className="mt-1 text-sm text-stone-600">
                                Manage the homepage welcome and promotion modal shown to customers.
                            </p>
                        </div>
                        <StatusBadge active={form.is_active} />
                    </div>

                    {localFlash ? (
                        <FlashMessage type={localFlash.type} message={localFlash.message} onDismiss={() => setLocalFlash(null)} />
                    ) : null}

                    <form onSubmit={handleSave} className="space-y-4">
                        <AdminSelectField
                            label="Popup active"
                            name="is_active"
                            value={form.is_active ? '1' : '0'}
                            onChange={(event) => setForm({ ...form, is_active: event.target.value === '1' })}
                            options={[
                                { value: '1', label: 'Active' },
                                { value: '0', label: 'Inactive' },
                            ]}
                        />

                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminSelectField
                                label="Popup type"
                                name="type"
                                value={form.type ?? 'welcome_offer'}
                                onChange={(event) => setForm({ ...form, type: event.target.value })}
                                options={typeOptions}
                            />
                            <AdminSelectField
                                label="Linked offer (optional)"
                                name="offer_id"
                                value={form.offer_id ? String(form.offer_id) : ''}
                                onChange={(event) => setForm({ ...form, offer_id: event.target.value || null })}
                                options={[
                                    { value: '', label: 'No linked offer' },
                                    ...offers.map((offer) => ({ value: String(offer.id), label: offer.title })),
                                ]}
                            />
                        </div>

                        <AdminFormField
                            label="Heading"
                            name="heading"
                            value={form.heading ?? ''}
                            onChange={(event) => setForm({ ...form, heading: event.target.value })}
                        />
                        <AdminFormField
                            label="Main message"
                            name="message"
                            value={form.message ?? ''}
                            onChange={(event) => setForm({ ...form, message: event.target.value })}
                        />
                        <AdminTextareaField
                            label="Description"
                            name="description"
                            value={form.description ?? ''}
                            onChange={(event) => setForm({ ...form, description: event.target.value })}
                        />
                        <AdminFormField
                            label="Badge / offer text"
                            name="badge_text"
                            value={form.badge_text ?? ''}
                            onChange={(event) => setForm({ ...form, badge_text: event.target.value })}
                            hint="Optional. Example: Limited-time offer"
                        />

                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField
                                label="CTA text"
                                name="button_text"
                                value={form.button_text ?? ''}
                                onChange={(event) => setForm({ ...form, button_text: event.target.value })}
                            />
                            <AdminFormField
                                label="CTA URL"
                                name="button_url"
                                value={form.button_url ?? ''}
                                onChange={(event) => setForm({ ...form, button_url: event.target.value })}
                                hint="Example: /order"
                            />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminSelectField
                                label="Display delay"
                                name="delay_ms"
                                value={String(form.delay_ms ?? 700)}
                                onChange={(event) => setForm({ ...form, delay_ms: Number(event.target.value) })}
                                options={delayOptions}
                            />
                            <AdminSelectField
                                label="Display frequency"
                                name="frequency"
                                value={form.frequency ?? 'session'}
                                onChange={(event) => setForm({ ...form, frequency: event.target.value })}
                                options={frequencyOptions}
                            />
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <p className="text-sm font-medium text-stone-700">Current image</p>
                                {form.image?.url ? (
                                    <img
                                        src={form.image.url}
                                        alt="Current popup"
                                        className="h-40 w-full max-w-sm rounded-lg border border-stone-200 object-cover"
                                    />
                                ) : (
                                    <div className="flex h-40 max-w-sm items-center rounded-lg border border-dashed border-stone-300 bg-stone-50 px-4 text-sm text-stone-500">
                                        No popup image configured
                                    </div>
                                )}
                            </div>
                            <div className="space-y-2">
                                <p className="text-sm font-medium text-stone-700">New image preview</p>
                                {imagePreview ? (
                                    <img
                                        src={imagePreview}
                                        alt="Selected popup preview"
                                        className="h-40 w-full max-w-sm rounded-lg border border-stone-200 object-cover"
                                    />
                                ) : (
                                    <div className="flex h-40 max-w-sm items-center rounded-lg border border-dashed border-stone-300 bg-stone-50 px-4 text-sm text-stone-500">
                                        Select an image to preview
                                    </div>
                                )}
                            </div>
                        </div>

                        <AdminFormField
                            label="Popup image"
                            name="popup_image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) => {
                                const file = event.target.files?.[0] ?? null;
                                setImageFile(file);
                                setImagePreview(file ? URL.createObjectURL(file) : null);
                            }}
                            hint="PNG, JPG, or WebP up to 4 MB."
                        />

                        <div className="flex flex-wrap gap-2">
                            <button
                                type="submit"
                                disabled={saving || imageSaving}
                                className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                            >
                                {saving || imageSaving ? 'Saving…' : 'Save popup'}
                            </button>
                            <button
                                type="button"
                                onClick={() => setPreviewOpen(true)}
                                className="rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50"
                            >
                                Preview popup
                            </button>
                            {form.image?.url ? (
                                <button
                                    type="button"
                                    onClick={handleRemoveImage}
                                    disabled={imageRemoving}
                                    className="rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-60"
                                >
                                    {imageRemoving ? 'Removing…' : 'Remove image'}
                                </button>
                            ) : null}
                        </div>
                    </form>
                </AdminPanelBody>
            </AdminPanel>

            <WelcomePopupModal
                open={previewOpen}
                popup={previewPopup}
                onClose={() => setPreviewOpen(false)}
                preview
            />
        </>
    );
}

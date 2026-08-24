import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { createAdminOffer, fetchAdminOffer, updateAdminOffer } from '../../api/admin/offers';
import FlashMessage from '../../components/admin/FlashMessage';
import {
    AdminCheckboxField,
    AdminFormField,
    AdminSelectField,
    AdminTextareaField,
} from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { firstFieldError, normalizeApiError } from '../../lib/errors';

const typeOptions = [
    { value: 'combo_offers', label: 'Combo Offers' },
    { value: 'discounts', label: 'Discounts' },
    { value: 'special_events', label: 'Special Events' },
    { value: 'seasonal_deals', label: 'Seasonal Deals' },
];

const emptyForm = {
    title: '',
    type: 'combo_offers',
    short_description: '',
    description: '',
    offer_value: '',
    cta_label: 'Order Now',
    cta_url: '/order',
    starts_at: '',
    ends_at: '',
    is_active: true,
    sort_order: 0,
};

function toDatetimeLocal(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function toApiDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toISOString();
}

export default function OfferFormPage() {
    const { id } = useParams();
    const isEdit = Boolean(id && id !== 'new');
    const navigate = useNavigate();

    const [form, setForm] = useState(emptyForm);
    const [currentImageUrl, setCurrentImageUrl] = useState(null);
    const [imageFile, setImageFile] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    const [loading, setLoading] = useState(isEdit);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});
    const [flash, setFlash] = useState(null);

    useEffect(() => {
        if (!isEdit) {
            return;
        }

        const load = async () => {
            setLoading(true);
            setError(null);

            try {
                const offer = await fetchAdminOffer(id);
                setForm({
                    title: offer.title ?? '',
                    type: offer.type ?? 'combo_offers',
                    short_description: offer.short_description ?? '',
                    description: offer.description ?? '',
                    offer_value: offer.offer_value ?? '',
                    cta_label: offer.cta_label ?? '',
                    cta_url: offer.cta_url ?? '',
                    starts_at: toDatetimeLocal(offer.starts_at),
                    ends_at: toDatetimeLocal(offer.ends_at),
                    is_active: Boolean(offer.is_active),
                    sort_order: offer.sort_order ?? 0,
                });
                setCurrentImageUrl(offer.image?.url ?? null);
                setImageFile(null);
                setImagePreview(null);
            } catch (err) {
                setError(normalizeApiError(err));
            } finally {
                setLoading(false);
            }
        };

        load();
    }, [id, isEdit]);

    useEffect(() => {
        return () => {
            if (imagePreview) {
                URL.revokeObjectURL(imagePreview);
            }
        };
    }, [imagePreview]);

    const updateField = (name, value) => {
        setForm((current) => ({ ...current, [name]: value }));
    };

    const handleImageChange = (event) => {
        const file = event.target.files?.[0] ?? null;

        if (imagePreview) {
            URL.revokeObjectURL(imagePreview);
        }

        if (!file) {
            setImageFile(null);
            setImagePreview(null);
            return;
        }

        setImageFile(file);
        setImagePreview(URL.createObjectURL(file));
    };

    const clearSelectedImage = () => {
        if (imagePreview) {
            URL.revokeObjectURL(imagePreview);
        }
        setImageFile(null);
        setImagePreview(null);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        const formData = new FormData();
        formData.append('title', form.title);
        formData.append('type', form.type);
        formData.append('short_description', form.short_description || '');
        formData.append('description', form.description || '');
        formData.append('offer_value', form.offer_value || '');
        formData.append('cta_label', form.cta_label || '');
        formData.append('cta_url', form.cta_url || '');
        formData.append('starts_at', toApiDate(form.starts_at));
        formData.append('ends_at', toApiDate(form.ends_at));
        formData.append('is_active', form.is_active ? '1' : '0');
        formData.append('sort_order', String(Number(form.sort_order) || 0));

        if (imageFile) {
            formData.append('image', imageFile);
        }

        try {
            if (isEdit) {
                const updated = await updateAdminOffer(id, formData);
                setCurrentImageUrl(updated.image?.url ?? null);
                clearSelectedImage();
                setFlash({ type: 'success', message: 'Offer updated.' });
            } else {
                const created = await createAdminOffer(formData);
                navigate(`/admin/offers/${created.id}/edit`, { replace: true });
            }
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors ?? {});
            setFlash({ type: 'error', message: firstFieldError(normalized.errors, 'image') || normalized.message });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading offer…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    const previewUrl = imagePreview || currentImageUrl;
    const imageError = firstFieldError(fieldErrors, 'image');

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit offer' : 'New offer'}
                actions={
                    <Link to="/admin/offers" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to offers
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit}>
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField
                            label="Title"
                            name="title"
                            required
                            value={form.title}
                            onChange={(event) => updateField('title', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminSelectField
                            label="Type"
                            name="type"
                            required
                            value={form.type}
                            onChange={(event) => updateField('type', event.target.value)}
                            options={typeOptions}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Offer value"
                            name="offer_value"
                            value={form.offer_value}
                            onChange={(event) => updateField('offer_value', event.target.value)}
                            hint='Use formats like "10% off" or "Rs 100 off" so the discount auto-applies at checkout. Other text is display-only.'
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="CTA label"
                            name="cta_label"
                            value={form.cta_label}
                            onChange={(event) => updateField('cta_label', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="CTA URL"
                            name="cta_url"
                            value={form.cta_url}
                            onChange={(event) => updateField('cta_url', event.target.value)}
                            hint="Existing app path such as /order or /menu."
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Starts at"
                            name="starts_at"
                            type="datetime-local"
                            value={form.starts_at}
                            onChange={(event) => updateField('starts_at', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Ends at"
                            name="ends_at"
                            type="datetime-local"
                            value={form.ends_at}
                            onChange={(event) => updateField('ends_at', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Sort order"
                            name="sort_order"
                            type="number"
                            min="0"
                            value={form.sort_order}
                            onChange={(event) => updateField('sort_order', event.target.value)}
                            errors={fieldErrors}
                        />
                        <div className="space-y-2 sm:col-span-2">
                            <label htmlFor="offer-image" className="block text-sm font-medium text-stone-700">
                                Offer image
                            </label>
                            <input
                                id="offer-image"
                                name="image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={handleImageChange}
                                className="block w-full text-sm text-stone-700 file:mr-3 file:rounded-md file:border-0 file:bg-stone-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-stone-800"
                            />
                            <p className="text-xs text-stone-500">JPG, PNG, or WEBP · max 10 MB. Leave empty on edit to keep the current image.</p>
                            {imageError ? <p className="text-xs text-red-600">{imageError}</p> : null}
                            {previewUrl ? (
                                <div className="mt-2 overflow-hidden rounded-lg border border-stone-200 bg-stone-50 p-2 sm:max-w-sm">
                                    <img
                                        src={previewUrl}
                                        alt={imagePreview ? 'Selected offer preview' : 'Current offer'}
                                        className="aspect-[16/10] w-full object-cover"
                                    />
                                    {imagePreview ? (
                                        <button
                                            type="button"
                                            onClick={clearSelectedImage}
                                            className="mt-2 text-xs font-medium text-stone-600 hover:text-stone-900"
                                        >
                                            Clear selected file
                                        </button>
                                    ) : (
                                        <p className="mt-2 text-xs text-stone-500">Current image</p>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-stone-500">No image selected.</p>
                            )}
                        </div>
                        <div className="sm:col-span-2">
                            <AdminFormField
                                label="Short description"
                                name="short_description"
                                value={form.short_description}
                                onChange={(event) => updateField('short_description', event.target.value)}
                                errors={fieldErrors}
                            />
                        </div>
                        <div className="sm:col-span-2">
                            <AdminTextareaField
                                label="Detailed description"
                                name="description"
                                value={form.description}
                                onChange={(event) => updateField('description', event.target.value)}
                                errors={fieldErrors}
                            />
                        </div>
                        <AdminCheckboxField
                            label="Active"
                            name="is_active"
                            checked={form.is_active}
                            onChange={(event) => updateField('is_active', event.target.checked)}
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <button
                    type="submit"
                    disabled={saving}
                    className="mt-4 rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Create offer'}
                </button>
            </form>
        </div>
    );
}

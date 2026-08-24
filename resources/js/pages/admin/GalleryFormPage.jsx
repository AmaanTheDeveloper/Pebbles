import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { createAdminGalleryItem, fetchAdminGalleryItem, updateAdminGalleryItem } from '../../api/admin/gallery';
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
import { normalizeApiError } from '../../lib/errors';

const categoryOptions = [
    { value: 'Food', label: 'Food' },
    { value: 'Interior', label: 'Interior' },
    { value: 'Events', label: 'Events' },
    { value: 'Atmosphere', label: 'Atmosphere' },
];

const gradientOptions = [
    { value: '', label: 'No gradient (use image)' },
    { value: 'from-brand-green to-brand-green-light', label: 'Green gradient' },
    { value: 'from-brand-green-light/90 to-brand-cream', label: 'Cream gradient' },
    { value: 'from-brand-orange to-[#ea580c]', label: 'Orange gradient' },
];

const emptyForm = {
    title: '',
    description: '',
    category: 'Food',
    display_order: 0,
    is_active: true,
    alt_text: '',
    gradient: '',
};

export default function GalleryFormPage() {
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
                const item = await fetchAdminGalleryItem(id);
                setForm({
                    title: item.title ?? '',
                    description: item.description ?? '',
                    category: item.category ?? 'Food',
                    display_order: item.display_order ?? 0,
                    is_active: Boolean(item.is_active),
                    alt_text: item.alt_text ?? '',
                    gradient: item.gradient ?? '',
                });
                setCurrentImageUrl(item.image?.url ?? null);
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
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        if (imagePreview) {
            URL.revokeObjectURL(imagePreview);
        }

        setImageFile(file);
        setImagePreview(URL.createObjectURL(file));
    };

    const buildFormData = () => {
        const formData = new FormData();
        formData.append('title', form.title);
        formData.append('description', form.description || '');
        formData.append('category', form.category);
        formData.append('display_order', String(Number(form.display_order) || 0));
        formData.append('is_active', form.is_active ? '1' : '0');
        formData.append('alt_text', form.alt_text || '');

        if (form.gradient) {
            formData.append('gradient', form.gradient);
        }

        if (imageFile) {
            formData.append('image', imageFile);
        }

        return formData;
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        const formData = buildFormData();

        try {
            if (isEdit) {
                const response = await updateAdminGalleryItem(id, formData);
                setCurrentImageUrl(response.data?.data?.image?.url ?? currentImageUrl);
                if (imagePreview) {
                    URL.revokeObjectURL(imagePreview);
                }
                setImageFile(null);
                setImagePreview(null);
                setFlash({ type: 'success', message: response.message || 'Gallery image updated successfully.' });
            } else {
                const response = await createAdminGalleryItem(formData);
                navigate(`/admin/gallery/${response.data.data.id}/edit`, { replace: true });
            }
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors ?? {});
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading gallery item…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    const previewUrl = imagePreview || currentImageUrl;

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit gallery item' : 'Add gallery item'}
                actions={
                    <Link to="/admin/gallery" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to gallery
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit}>
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField label="Title" name="title" required value={form.title} onChange={(event) => updateField('title', event.target.value)} errors={fieldErrors} />
                        <AdminSelectField label="Category" name="category" required value={form.category} onChange={(event) => updateField('category', event.target.value)} options={categoryOptions} errors={fieldErrors} />
                        <div className="sm:col-span-2">
                            <AdminTextareaField label="Description" name="description" rows={4} value={form.description} onChange={(event) => updateField('description', event.target.value)} errors={fieldErrors} />
                        </div>
                        <AdminFormField label="Alt text" name="alt_text" value={form.alt_text} onChange={(event) => updateField('alt_text', event.target.value)} errors={fieldErrors} />
                        <AdminFormField label="Display order" name="display_order" type="number" min="0" value={form.display_order} onChange={(event) => updateField('display_order', event.target.value)} errors={fieldErrors} />
                        <AdminSelectField label="Gradient placeholder" name="gradient" value={form.gradient} onChange={(event) => updateField('gradient', event.target.value)} options={gradientOptions} hint="Optional for Interior cards without an uploaded image." errors={fieldErrors} />
                        <AdminCheckboxField label="Active" name="is_active" checked={form.is_active} onChange={(event) => updateField('is_active', event.target.checked)} />
                        <div className="sm:col-span-2 space-y-3">
                            <label className="block text-sm font-medium text-stone-700">
                                Image
                                <input type="file" accept="image/jpeg,image/png,image/webp" className="mt-2 block w-full text-sm" onChange={handleImageChange} />
                            </label>
                            {previewUrl ? (
                                <img src={previewUrl} alt="Gallery preview" className="max-h-64 rounded-lg border border-stone-200 object-cover" />
                            ) : form.gradient ? (
                                <div className={`flex h-40 max-w-sm flex-col items-center justify-center rounded-lg bg-gradient-to-br p-6 text-center text-white ${form.gradient}`}>
                                    <p className="font-semibold">{form.title || 'Preview title'}</p>
                                </div>
                            ) : null}
                        </div>
                    </AdminPanelBody>
                </AdminPanel>

                <button type="submit" disabled={saving} className="mt-4 rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60">
                    {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Add gallery item'}
                </button>
            </form>
        </div>
    );
}

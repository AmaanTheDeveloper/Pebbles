import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { createAdminMenuBookPage, fetchAdminMenuBookPage, updateAdminMenuBookPage } from '../../api/admin/menuBook';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminCheckboxField, AdminFormField } from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const emptyForm = {
    title: '',
    display_order: 0,
    is_active: true,
};

export default function MenuBookFormPage() {
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
                const page = await fetchAdminMenuBookPage(id);
                setForm({
                    title: page.title ?? '',
                    display_order: page.display_order ?? 0,
                    is_active: Boolean(page.is_active),
                });
                setCurrentImageUrl(page.image?.url ?? null);
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
        formData.append('display_order', String(Number(form.display_order) || 0));
        formData.append('is_active', form.is_active ? '1' : '0');

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
                const response = await updateAdminMenuBookPage(id, formData);
                setCurrentImageUrl(response.data?.image?.url ?? currentImageUrl);
                if (imagePreview) {
                    URL.revokeObjectURL(imagePreview);
                }
                setImageFile(null);
                setImagePreview(null);
                setFlash({ type: 'success', message: response.message || 'Menu page updated successfully.' });
            } else {
                const response = await createAdminMenuBookPage(formData);
                navigate(`/admin/menu-book/${response.data.id}/edit`, { replace: true });
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
        return <LoadingState label="Loading menu page…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    const previewUrl = imagePreview || currentImageUrl;

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit menu page' : 'Add menu page'}
                actions={
                    <Link to="/admin/menu-book" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to menu book
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit}>
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField
                            label="Page title"
                            name="title"
                            value={form.title}
                            onChange={(event) => updateField('title', event.target.value)}
                            hint="Optional — e.g. Main Menu, Shakes, Drinks, Page 1"
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Display order"
                            name="display_order"
                            type="number"
                            min="0"
                            value={form.display_order}
                            onChange={(event) => updateField('display_order', event.target.value)}
                            errors={fieldErrors}
                        />
                        <div className="sm:col-span-2">
                            <AdminCheckboxField
                                label="Active"
                                name="is_active"
                                checked={form.is_active}
                                onChange={(event) => updateField('is_active', event.target.checked)}
                            />
                        </div>
                        <div className="sm:col-span-2 space-y-3">
                            <label className="block text-sm font-medium text-stone-700">
                                Menu image {isEdit ? '(replace optional)' : '(required)'}
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    className="mt-2 block w-full text-sm"
                                    onChange={handleImageChange}
                                    required={!isEdit && !previewUrl}
                                />
                            </label>
                            {fieldErrors.image ? (
                                <p className="text-sm text-red-600">{fieldErrors.image[0]}</p>
                            ) : null}
                            {previewUrl ? (
                                <img
                                    src={previewUrl}
                                    alt="Menu page preview"
                                    className="max-h-[28rem] w-full max-w-2xl rounded-lg border border-stone-200 object-contain"
                                />
                            ) : null}
                        </div>
                    </AdminPanelBody>
                </AdminPanel>

                <button type="submit" disabled={saving} className="mt-4 rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60">
                    {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Add menu page'}
                </button>
            </form>
        </div>
    );
}

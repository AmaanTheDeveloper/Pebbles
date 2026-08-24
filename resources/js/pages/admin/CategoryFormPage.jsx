import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { createAdminCategory, fetchAdminCategory, updateAdminCategory } from '../../api/admin/categories';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminCheckboxField, AdminFormField, AdminTextareaField } from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const emptyForm = {
    name: '',
    slug: '',
    description: '',
    image_path: '',
    is_active: true,
    sort_order: 0,
};

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function CategoryFormPage() {
    const { id } = useParams();
    const isEdit = Boolean(id && id !== 'new');
    const navigate = useNavigate();

    const [form, setForm] = useState(emptyForm);
    const [loading, setLoading] = useState(isEdit);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});
    const [flash, setFlash] = useState(null);
    const [slugTouched, setSlugTouched] = useState(false);

    useEffect(() => {
        if (!isEdit) {
            return;
        }

        const load = async () => {
            setLoading(true);
            setError(null);

            try {
                const category = await fetchAdminCategory(id);
                setForm({
                    name: category.name ?? '',
                    slug: category.slug ?? '',
                    description: category.description ?? '',
                    image_path: category.image_path ?? '',
                    is_active: Boolean(category.is_active),
                    sort_order: category.sort_order ?? 0,
                });
                setSlugTouched(true);
            } catch (err) {
                setError(normalizeApiError(err));
            } finally {
                setLoading(false);
            }
        };

        load();
    }, [id, isEdit]);

    const updateField = (name, value) => {
        setForm((current) => {
            const next = { ...current, [name]: value };
            if (name === 'name' && !slugTouched) {
                next.slug = slugify(value);
            }
            return next;
        });
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        const payload = {
            ...form,
            sort_order: Number(form.sort_order),
        };

        try {
            if (isEdit) {
                await updateAdminCategory(id, payload);
                setFlash({ type: 'success', message: 'Category updated.' });
            } else {
                const created = await createAdminCategory(payload);
                navigate(`/admin/categories/${created.id}/edit`, { replace: true });
            }
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors);
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading category…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit category' : 'New category'}
                actions={
                    <Link to="/admin/categories" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to categories
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit}>
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField
                            label="Name"
                            name="name"
                            required
                            value={form.name}
                            onChange={(event) => updateField('name', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Slug"
                            name="slug"
                            required
                            value={form.slug}
                            onChange={(event) => {
                                setSlugTouched(true);
                                updateField('slug', event.target.value);
                            }}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Image path"
                            name="image_path"
                            value={form.image_path}
                            onChange={(event) => updateField('image_path', event.target.value)}
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
                        <div className="sm:col-span-2">
                            <AdminTextareaField
                                label="Description"
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
                    {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Create category'}
                </button>
            </form>
        </div>
    );
}

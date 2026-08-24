import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { createAdminBranch, fetchAdminBranch, updateAdminBranch } from '../../api/admin/branches';
import OpeningHoursEditor from '../../components/admin/OpeningHoursEditor';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminCheckboxField, AdminFormField } from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { defaultOpeningHours, normalizeOpeningHours } from '../../lib/contactHelpers';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const emptyForm = {
    name: '',
    slug: '',
    code: '',
    address: '',
    phone: '',
    whatsapp: '',
    latitude: '',
    longitude: '',
    map_url: '',
    opens_at: '',
    closes_at: '',
    display_order: '0',
    is_active: true,
    opening_hours: defaultOpeningHours(),
};

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function BranchFormPage() {
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
    const [imageFile, setImageFile] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    const [currentImageUrl, setCurrentImageUrl] = useState(null);

    useEffect(() => {
        if (!isEdit) {
            return;
        }

        const load = async () => {
            setLoading(true);
            setError(null);

            try {
                const branch = await fetchAdminBranch(id);
                setForm({
                    name: branch.name ?? '',
                    slug: branch.slug ?? '',
                    code: branch.code ?? '',
                    address: branch.address ?? '',
                    phone: branch.phone ?? '',
                    whatsapp: branch.whatsapp ?? '',
                    latitude: branch.latitude ?? '',
                    longitude: branch.longitude ?? '',
                    map_url: branch.map_url ?? '',
                    opens_at: branch.opens_at ?? '',
                    closes_at: branch.closes_at ?? '',
                    display_order: String(branch.display_order ?? 0),
                    is_active: Boolean(branch.is_active),
                    opening_hours: normalizeOpeningHours(branch.opening_hours),
                });
                setCurrentImageUrl(branch.image_url ?? null);
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

    const buildPayload = () => ({
        ...form,
        latitude: form.latitude === '' ? null : Number(form.latitude),
        longitude: form.longitude === '' ? null : Number(form.longitude),
        code: form.code || null,
        phone: form.phone || null,
        whatsapp: form.whatsapp || null,
        map_url: form.map_url || null,
        opens_at: form.opens_at || null,
        closes_at: form.closes_at || null,
        display_order: Number(form.display_order) || 0,
        opening_hours: form.opening_hours,
    });

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

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        const payload = buildPayload();

        try {
            if (imageFile) {
                const formData = new FormData();
                Object.entries(payload).forEach(([key, value]) => {
                    if (key === 'opening_hours') {
                        formData.append(key, JSON.stringify(value));
                        return;
                    }

                    if (typeof value === 'boolean') {
                        formData.append(key, value ? '1' : '0');
                        return;
                    }

                    if (value !== null && value !== undefined) {
                        formData.append(key, String(value));
                    }
                });
                formData.append('image', imageFile);

                if (isEdit) {
                    await updateAdminBranch(id, formData);
                    setFlash({ type: 'success', message: 'Branch updated.' });
                } else {
                    const created = await createAdminBranch(formData);
                    navigate(`/admin/branches/${created.id}/edit`, { replace: true });
                }
            } else if (isEdit) {
                await updateAdminBranch(id, payload);
                setFlash({ type: 'success', message: 'Branch updated.' });
            } else {
                const created = await createAdminBranch(payload);
                navigate(`/admin/branches/${created.id}/edit`, { replace: true });
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
        return <LoadingState label="Loading branch…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit branch' : 'New branch'}
                actions={
                    <Link to="/admin/branches" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to branches
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit} className="space-y-6">
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField label="Name" name="name" required value={form.name} onChange={(event) => updateField('name', event.target.value)} errors={fieldErrors} />
                        <AdminFormField label="Slug" name="slug" required value={form.slug} onChange={(event) => { setSlugTouched(true); updateField('slug', event.target.value); }} errors={fieldErrors} />
                        <AdminFormField label="Code" name="code" value={form.code} onChange={(event) => updateField('code', event.target.value)} errors={fieldErrors} />
                        <AdminFormField label="Display order" name="display_order" type="number" min="0" value={form.display_order} onChange={(event) => updateField('display_order', event.target.value)} errors={fieldErrors} />
                        <AdminFormField label="Phone" name="phone" value={form.phone} onChange={(event) => updateField('phone', event.target.value)} errors={fieldErrors} />
                        <AdminFormField label="WhatsApp" name="whatsapp" value={form.whatsapp} onChange={(event) => updateField('whatsapp', event.target.value)} errors={fieldErrors} />
                        <div className="sm:col-span-2">
                            <AdminFormField label="Address" name="address" required value={form.address} onChange={(event) => updateField('address', event.target.value)} errors={fieldErrors} />
                        </div>
                        <AdminFormField label="Latitude" name="latitude" type="number" step="any" value={form.latitude} onChange={(event) => updateField('latitude', event.target.value)} errors={fieldErrors} />
                        <AdminFormField label="Longitude" name="longitude" type="number" step="any" value={form.longitude} onChange={(event) => updateField('longitude', event.target.value)} errors={fieldErrors} />
                        <div className="sm:col-span-2">
                            <AdminFormField label="Google Maps URL" name="map_url" value={form.map_url} onChange={(event) => updateField('map_url', event.target.value)} errors={fieldErrors} />
                        </div>
                        <AdminFormField label="Legacy opens at" name="opens_at" type="time" value={form.opens_at} onChange={(event) => updateField('opens_at', event.target.value)} errors={fieldErrors} hint="Optional fallback if weekly hours are not set." />
                        <AdminFormField label="Legacy closes at" name="closes_at" type="time" value={form.closes_at} onChange={(event) => updateField('closes_at', event.target.value)} errors={fieldErrors} />
                        <div className="sm:col-span-2">
                            <label className="block space-y-2 text-sm">
                                <span className="font-medium text-stone-800">Branch image</span>
                                {currentImageUrl ? <img src={currentImageUrl} alt="" className="h-32 rounded-lg object-cover" /> : null}
                                {imagePreview ? <img src={imagePreview} alt="" className="h-32 rounded-lg object-cover" /> : null}
                                <input type="file" accept="image/*" onChange={handleImageChange} />
                            </label>
                        </div>
                        <AdminCheckboxField label="Active" name="is_active" checked={form.is_active} onChange={(event) => updateField('is_active', event.target.checked)} />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody>
                        <h2 className="mb-4 text-lg font-semibold text-stone-900">Weekly opening hours</h2>
                        <OpeningHoursEditor value={form.opening_hours} onChange={(opening_hours) => updateField('opening_hours', opening_hours)} errors={fieldErrors} />
                    </AdminPanelBody>
                </AdminPanel>

                <button type="submit" disabled={saving} className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60">
                    {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Create branch'}
                </button>
            </form>
        </div>
    );
}

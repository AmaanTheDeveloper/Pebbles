import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
    createAddOnOption,
    createAdminAddOnGroup,
    fetchAdminAddOnGroup,
    updateAddOnOption,
    updateAdminAddOnGroup,
} from '../../api/admin/addons';
import FlashMessage from '../../components/admin/FlashMessage';
import {
    AdminCheckboxField,
    AdminFormField,
    AdminTextareaField,
} from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { formatMoney } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';

const emptyForm = {
    name: '',
    slug: '',
    description: '',
    is_required: false,
    min_selections: 0,
    max_selections: '',
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

export default function AddOnGroupFormPage() {
    const { id } = useParams();
    const isEdit = Boolean(id && id !== 'new');
    const navigate = useNavigate();

    const [form, setForm] = useState(emptyForm);
    const [options, setOptions] = useState([]);
    const [loading, setLoading] = useState(isEdit);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});
    const [flash, setFlash] = useState(null);
    const [slugTouched, setSlugTouched] = useState(false);
    const [newOption, setNewOption] = useState({ name: '', price: '', is_active: true });

    useEffect(() => {
        if (!isEdit) {
            return;
        }

        const load = async () => {
            setLoading(true);
            setError(null);

            try {
                const group = await fetchAdminAddOnGroup(id);
                setForm({
                    name: group.name ?? '',
                    slug: group.slug ?? '',
                    description: group.description ?? '',
                    is_required: Boolean(group.is_required),
                    min_selections: group.min_selections ?? 0,
                    max_selections: group.max_selections ?? '',
                    is_active: Boolean(group.is_active),
                    sort_order: group.sort_order ?? 0,
                });
                setOptions(group.add_ons ?? []);
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
            min_selections: Number(form.min_selections),
            max_selections: form.max_selections === '' ? null : Number(form.max_selections),
            sort_order: Number(form.sort_order),
        };

        try {
            if (isEdit) {
                await updateAdminAddOnGroup(id, payload);
                setFlash({ type: 'success', message: 'Add-on group updated.' });
            } else {
                const created = await createAdminAddOnGroup(payload);
                navigate(`/admin/add-ons/${created.id}/edit`, { replace: true });
            }
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors);
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setSaving(false);
        }
    };

    const handleAddOption = async (event) => {
        event.preventDefault();
        if (!isEdit) {
            return;
        }

        setSaving(true);
        setFlash(null);

        try {
            const option = await createAddOnOption(id, {
                name: newOption.name,
                price: Number(newOption.price),
                is_active: newOption.is_active,
            });
            setOptions((current) => [...current, option]);
            setNewOption({ name: '', price: '', is_active: true });
            setFlash({ type: 'success', message: 'Option added.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const handleOptionToggle = async (option) => {
        try {
            const updated = await updateAddOnOption(id, option.id, { is_active: !option.is_active });
            setOptions((current) => current.map((item) => (item.id === option.id ? updated : item)));
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    if (loading) {
        return <LoadingState label="Loading add-on group…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit add-on group' : 'New add-on group'}
                actions={
                    <Link to="/admin/add-ons" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to add-ons
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit} className="space-y-6">
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
                            label="Min selections"
                            name="min_selections"
                            type="number"
                            min="0"
                            value={form.min_selections}
                            onChange={(event) => updateField('min_selections', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Max selections"
                            name="max_selections"
                            type="number"
                            min="1"
                            value={form.max_selections}
                            onChange={(event) => updateField('max_selections', event.target.value)}
                            hint="Leave blank for unlimited"
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
                        <div className="sm:col-span-2 flex flex-wrap gap-6">
                            <AdminCheckboxField
                                label="Required"
                                name="is_required"
                                checked={form.is_required}
                                onChange={(event) => updateField('is_required', event.target.checked)}
                            />
                            <AdminCheckboxField
                                label="Active"
                                name="is_active"
                                checked={form.is_active}
                                onChange={(event) => updateField('is_active', event.target.checked)}
                            />
                        </div>
                    </AdminPanelBody>
                </AdminPanel>

                <button
                    type="submit"
                    disabled={saving}
                    className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Create group'}
                </button>
            </form>

            {isEdit ? (
                <AdminPanel>
                    <AdminPanelBody>
                        <h2 className="text-sm font-semibold text-stone-900">Options</h2>
                        {options.length ? (
                            <ul className="mt-3 divide-y divide-stone-100">
                                {options.map((option) => (
                                    <li key={option.id} className="flex items-center justify-between py-3 text-sm">
                                        <div>
                                            <p className="font-medium">{option.name}</p>
                                            <p className="text-stone-500">{formatMoney(option.price)}</p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => handleOptionToggle(option)}
                                            className="text-sm font-medium text-amber-900 hover:underline"
                                        >
                                            {option.is_active ? 'Deactivate' : 'Activate'}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-2 text-sm text-stone-500">No options yet.</p>
                        )}
                        <form onSubmit={handleAddOption} className="mt-4 grid gap-3 sm:grid-cols-3">
                            <AdminFormField
                                label="Option name"
                                name="option_name"
                                required
                                value={newOption.name}
                                onChange={(event) => setNewOption((current) => ({ ...current, name: event.target.value }))}
                            />
                            <AdminFormField
                                label="Price"
                                name="option_price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                value={newOption.price}
                                onChange={(event) => setNewOption((current) => ({ ...current, price: event.target.value }))}
                            />
                            <div className="flex items-end">
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="w-full rounded-md border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-stone-50"
                                >
                                    Add option
                                </button>
                            </div>
                        </form>
                    </AdminPanelBody>
                </AdminPanel>
            ) : null}
        </div>
    );
}

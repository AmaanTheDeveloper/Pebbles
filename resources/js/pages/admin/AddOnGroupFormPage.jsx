import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
    createAddOnOption,
    createAdminAddOnGroup,
    fetchAdminAddOnGroup,
    fetchAssignableCategories,
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
import { firstFieldError, normalizeApiError } from '../../lib/errors';

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

function CategoryAssignmentPicker({
    categories,
    selectedIds,
    onChange,
    loading,
    error,
    fieldError,
}) {
    const categoryList = Array.isArray(categories) ? categories : [];
    const selectedCount = selectedIds.length;

    const toggle = (categoryId) => {
        const id = Number(categoryId);
        if (selectedIds.includes(id)) {
            onChange(selectedIds.filter((value) => value !== id));
            return;
        }

        onChange([...selectedIds, id]);
    };

    const selectAll = () => {
        onChange(categoryList.map((category) => Number(category.id)));
    };

    const clearSelection = () => onChange([]);

    return (
        <div className="space-y-3 sm:col-span-3">
            <div className="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <p className="text-sm font-medium text-stone-700">Available Categories</p>
                    <p className="text-xs text-stone-500">
                        Select the product categories where this add-on should be available. {selectedCount}{' '}
                        {selectedCount === 1 ? 'category' : 'categories'} selected.
                    </p>
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={selectAll}
                        disabled={loading || categoryList.length === 0}
                        className="rounded-md border border-stone-300 px-2.5 py-1 text-xs font-medium hover:bg-stone-50 disabled:opacity-50"
                    >
                        Select all
                    </button>
                    <button
                        type="button"
                        onClick={clearSelection}
                        disabled={loading || selectedCount === 0}
                        className="rounded-md border border-stone-300 px-2.5 py-1 text-xs font-medium hover:bg-stone-50 disabled:opacity-50"
                    >
                        Clear
                    </button>
                </div>
            </div>

            {loading ? <p className="text-sm text-stone-500">Loading categories…</p> : null}
            {error && !loading ? <p className="text-sm text-red-600">{error}</p> : null}
            {fieldError ? <p className="text-xs text-red-600">{fieldError}</p> : null}

            <div className="max-h-56 space-y-1 overflow-y-auto rounded-lg border border-stone-200 bg-stone-50 p-3">
                {loading ? null : error ? null : categoryList.length === 0 ? (
                    <p className="text-sm text-stone-500">No product categories available.</p>
                ) : (
                    categoryList.map((category) => {
                        const checked = selectedIds.includes(Number(category.id));

                        return (
                            <label
                                key={category.id}
                                className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-white"
                            >
                                <input
                                    type="checkbox"
                                    checked={checked}
                                    onChange={() => toggle(category.id)}
                                    className="rounded border-stone-300 text-stone-900 focus:ring-stone-400"
                                />
                                <span className="font-medium text-stone-800">{category.name}</span>
                            </label>
                        );
                    })
                )}
            </div>
        </div>
    );
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
    const [newOption, setNewOption] = useState({
        name: '',
        price: '',
        is_active: true,
        category_ids: [],
    });
    const [assignableCategories, setAssignableCategories] = useState([]);
    const [categoriesLoading, setCategoriesLoading] = useState(isEdit);
    const [categoriesError, setCategoriesError] = useState(null);
    const [editingOptionId, setEditingOptionId] = useState(null);
    const [editOption, setEditOption] = useState(null);
    const [groupReady, setGroupReady] = useState(!isEdit);

    useEffect(() => {
        if (!isEdit) {
            setGroupReady(true);
            return;
        }

        const load = async () => {
            setLoading(true);
            setError(null);
            setGroupReady(false);

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
                setGroupReady(true);
            } catch (err) {
                setError(normalizeApiError(err));
                setGroupReady(false);
            } finally {
                setLoading(false);
            }
        };

        load();
    }, [id, isEdit]);

    useEffect(() => {
        if (!isEdit || !groupReady) {
            return;
        }

        let cancelled = false;

        (async () => {
            setCategoriesLoading(true);
            setCategoriesError(null);

            try {
                const categories = await fetchAssignableCategories(id);
                if (!cancelled) {
                    setAssignableCategories(Array.isArray(categories) ? categories : []);
                }
            } catch (err) {
                if (!cancelled) {
                    setCategoriesError('Unable to load categories. Please try again.');
                    setAssignableCategories([]);
                }
            } finally {
                if (!cancelled) {
                    setCategoriesLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [id, isEdit, groupReady]);

    const categoriesForPicker = useMemo(() => {
        const byId = new Map();

        (Array.isArray(assignableCategories) ? assignableCategories : []).forEach((category) => {
            byId.set(Number(category.id), category);
        });

        options.forEach((option) => {
            (option.categories ?? []).forEach((category) => {
                if (!byId.has(Number(category.id))) {
                    byId.set(Number(category.id), category);
                }
            });
        });

        return [...byId.values()].sort((a, b) => String(a.name).localeCompare(String(b.name)));
    }, [assignableCategories, options]);

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

        if (newOption.category_ids.length === 0) {
            setFlash({ type: 'error', message: 'Please select at least one category.' });
            return;
        }

        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        try {
            const option = await createAddOnOption(id, {
                name: newOption.name,
                price: Number(newOption.price),
                is_active: newOption.is_active,
                category_ids: newOption.category_ids,
            });
            setOptions((current) => [...current, option]);
            setNewOption({ name: '', price: '', is_active: true, category_ids: [] });
            setFlash({ type: 'success', message: 'Option added.' });
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors ?? {});
            setFlash({
                type: 'error',
                message: firstFieldError(normalized.errors, 'category_ids') || normalized.message,
            });
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

    const startEditOption = (option) => {
        setEditingOptionId(option.id);
        setEditOption({
            name: option.name ?? '',
            price: option.price ?? '',
            is_active: Boolean(option.is_active),
            category_ids: (option.category_ids ?? []).map(Number),
        });
        setFieldErrors({});
    };

    const cancelEditOption = () => {
        setEditingOptionId(null);
        setEditOption(null);
    };

    const handleSaveOption = async (event) => {
        event.preventDefault();
        if (!editingOptionId || !editOption) {
            return;
        }

        if (editOption.category_ids.length === 0) {
            setFlash({ type: 'error', message: 'Please select at least one category.' });
            return;
        }

        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        try {
            const updated = await updateAddOnOption(id, editingOptionId, {
                name: editOption.name,
                price: Number(editOption.price),
                is_active: editOption.is_active,
                category_ids: editOption.category_ids,
            });
            setOptions((current) => current.map((item) => (item.id === updated.id ? updated : item)));
            cancelEditOption();
            setFlash({ type: 'success', message: 'Option updated.' });
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors ?? {});
            setFlash({
                type: 'error',
                message: firstFieldError(normalized.errors, 'category_ids') || normalized.message,
            });
        } finally {
            setSaving(false);
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
                                    <li key={option.id} className="py-3 text-sm">
                                        {editingOptionId === option.id && editOption ? (
                                            <form onSubmit={handleSaveOption} className="grid gap-3 sm:grid-cols-3">
                                                <AdminFormField
                                                    label="Option name"
                                                    name="edit_option_name"
                                                    required
                                                    value={editOption.name}
                                                    onChange={(event) =>
                                                        setEditOption((current) => ({
                                                            ...current,
                                                            name: event.target.value,
                                                        }))
                                                    }
                                                    errors={fieldErrors}
                                                />
                                                <AdminFormField
                                                    label="Price"
                                                    name="edit_option_price"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    required
                                                    value={editOption.price}
                                                    onChange={(event) =>
                                                        setEditOption((current) => ({
                                                            ...current,
                                                            price: event.target.value,
                                                        }))
                                                    }
                                                    errors={fieldErrors}
                                                />
                                                <div className="flex items-end gap-2">
                                                    <AdminCheckboxField
                                                        label="Active"
                                                        name="edit_option_active"
                                                        checked={editOption.is_active}
                                                        onChange={(event) =>
                                                            setEditOption((current) => ({
                                                                ...current,
                                                                is_active: event.target.checked,
                                                            }))
                                                        }
                                                    />
                                                </div>
                                                <CategoryAssignmentPicker
                                                    categories={categoriesForPicker}
                                                    selectedIds={editOption.category_ids}
                                                    onChange={(categoryIds) =>
                                                        setEditOption((current) => ({
                                                            ...current,
                                                            category_ids: categoryIds,
                                                        }))
                                                    }
                                                    loading={categoriesLoading}
                                                    error={categoriesError}
                                                    fieldError={firstFieldError(fieldErrors, 'category_ids')}
                                                />
                                                <div className="flex gap-2 sm:col-span-3">
                                                    <button
                                                        type="submit"
                                                        disabled={saving}
                                                        className="rounded-md bg-stone-900 px-3 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                                                    >
                                                        {saving ? 'Saving…' : 'Save option'}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={cancelEditOption}
                                                        className="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-stone-50"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        ) : (
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">{option.name}</p>
                                                    <p className="text-stone-500">{formatMoney(option.price)}</p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        {(option.categories ?? []).length
                                                            ? `Available for: ${(option.categories ?? [])
                                                                  .map((category) => category.name)
                                                                  .join(', ')}`
                                                            : 'Available for all eligible categories in this type (legacy)'}
                                                    </p>
                                                </div>
                                                <div className="flex gap-3">
                                                    <button
                                                        type="button"
                                                        onClick={() => startEditOption(option)}
                                                        className="text-sm font-medium text-stone-700 hover:underline"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOptionToggle(option)}
                                                        className="text-sm font-medium text-amber-900 hover:underline"
                                                    >
                                                        {option.is_active ? 'Deactivate' : 'Activate'}
                                                    </button>
                                                </div>
                                            </div>
                                        )}
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
                                onChange={(event) =>
                                    setNewOption((current) => ({ ...current, name: event.target.value }))
                                }
                            />
                            <AdminFormField
                                label="Price"
                                name="option_price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                value={newOption.price}
                                onChange={(event) =>
                                    setNewOption((current) => ({ ...current, price: event.target.value }))
                                }
                            />
                            <div className="flex items-end">
                                <AdminCheckboxField
                                    label="Active"
                                    name="option_active"
                                    checked={newOption.is_active}
                                    onChange={(event) =>
                                        setNewOption((current) => ({
                                            ...current,
                                            is_active: event.target.checked,
                                        }))
                                    }
                                />
                            </div>
                            <CategoryAssignmentPicker
                                categories={categoriesForPicker}
                                selectedIds={newOption.category_ids}
                                onChange={(categoryIds) =>
                                    setNewOption((current) => ({ ...current, category_ids: categoryIds }))
                                }
                                loading={categoriesLoading}
                                error={categoriesError}
                                fieldError={firstFieldError(fieldErrors, 'category_ids')}
                            />
                            <div className="flex items-end sm:col-span-3">
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-stone-50 disabled:opacity-60"
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

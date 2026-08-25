import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { fetchAdminAddOnGroups } from '../../api/admin/addons';
import { fetchAdminCategories } from '../../api/admin/categories';
import {
    createAdminProduct,
    createProductVariant,
    deleteProductImage,
    fetchAdminProduct,
    setPrimaryProductImage,
    updateAdminProduct,
    updateProductVariant,
    uploadProductImage,
} from '../../api/admin/products';
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
import { cropImageFileToSquare } from '../../lib/cropImageToSquare';
import { formatMoney } from '../../lib/format';
import { firstFieldError, normalizeApiError } from '../../lib/errors';

const emptyForm = {
    category_id: '',
    name: '',
    slug: '',
    short_description: '',
    description: '',
    base_price: '',
    is_active: true,
    is_available: true,
    is_featured: false,
    sort_order: 0,
    stock_quantity: '',
    low_stock_threshold: 5,
    add_on_group_ids: [],
};

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function ProductFormPage() {
    const { id } = useParams();
    const isEdit = Boolean(id && id !== 'new');
    const navigate = useNavigate();

    const [form, setForm] = useState(emptyForm);
    const [variants, setVariants] = useState([]);
    const [images, setImages] = useState([]);
    const [categories, setCategories] = useState([]);
    const [addOnGroups, setAddOnGroups] = useState([]);
    const [loading, setLoading] = useState(isEdit);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});
    const [flash, setFlash] = useState(null);
    const [slugTouched, setSlugTouched] = useState(false);
    const [newVariant, setNewVariant] = useState({
        name: '',
        price: '',
        sku: '',
        is_active: true,
        product_image_id: '',
    });
    const [imagePreview, setImagePreview] = useState(null);
    const [imageBlob, setImageBlob] = useState(null);
    const [imageBusy, setImageBusy] = useState(false);
    const [imageHint, setImageHint] = useState(null);

    useEffect(() => {
        Promise.all([
            fetchAdminCategories().then((response) => setCategories(response.data ?? [])),
            fetchAdminAddOnGroups().then((response) => setAddOnGroups(response.data ?? [])),
        ]).catch(() => {});
    }, []);

    useEffect(() => {
        if (!isEdit) {
            return;
        }

        const load = async () => {
            setLoading(true);
            setError(null);

            try {
                const product = await fetchAdminProduct(id);
                setForm({
                    category_id: String(product.category_id ?? ''),
                    name: product.name ?? '',
                    slug: product.slug ?? '',
                    short_description: product.short_description ?? '',
                    description: product.description ?? '',
                    base_price: product.base_price ?? '',
                    is_active: Boolean(product.is_active),
                    is_available: Boolean(product.is_available),
                    is_featured: Boolean(product.is_featured),
                    sort_order: product.sort_order ?? 0,
                    stock_quantity: product.stock_quantity ?? '',
                    low_stock_threshold: product.low_stock_threshold ?? 5,
                    add_on_group_ids: (product.add_on_groups ?? []).map((group) => group.id),
                });
                setVariants(product.variants ?? []);
                setImages(product.images ?? []);
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

    const toggleAddOnGroup = (groupId) => {
        setForm((current) => {
            const ids = current.add_on_group_ids.includes(groupId)
                ? current.add_on_group_ids.filter((value) => value !== groupId)
                : [...current.add_on_group_ids, groupId];
            return { ...current, add_on_group_ids: ids };
        });
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        const payload = {
            ...form,
            category_id: Number(form.category_id),
            base_price: Number(form.base_price),
            sort_order: Number(form.sort_order),
            stock_quantity: form.stock_quantity === '' || form.stock_quantity === null ? null : Number(form.stock_quantity),
            low_stock_threshold:
                form.low_stock_threshold === '' || form.low_stock_threshold === null
                    ? null
                    : Number(form.low_stock_threshold),
            add_on_group_ids: form.add_on_group_ids,
        };

        try {
            if (isEdit) {
                await updateAdminProduct(id, payload);
                setFlash({ type: 'success', message: 'Product updated.' });
            } else {
                const created = await createAdminProduct(payload);
                navigate(`/admin/products/${created.id}/edit`, { replace: true });
            }
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors);
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setSaving(false);
        }
    };

    const handleAddVariant = async (event) => {
        event.preventDefault();
        if (!isEdit) {
            return;
        }

        setSaving(true);
        setFlash(null);

        try {
            const variant = await createProductVariant(id, {
                name: newVariant.name,
                price: Number(newVariant.price),
                sku: newVariant.sku || undefined,
                is_active: newVariant.is_active,
                product_image_id: newVariant.product_image_id ? Number(newVariant.product_image_id) : null,
            });
            setVariants((current) => [...current, variant]);
            setNewVariant({ name: '', price: '', sku: '', is_active: true, product_image_id: '' });
            setFlash({ type: 'success', message: 'Variant added.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const handleVariantToggle = async (variant) => {
        try {
            const updated = await updateProductVariant(id, variant.id, { is_active: !variant.is_active });
            setVariants((current) => current.map((item) => (item.id === variant.id ? updated : item)));
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const handleVariantImageChange = async (variant, productImageId) => {
        try {
            const updated = await updateProductVariant(id, variant.id, {
                product_image_id: productImageId ? Number(productImageId) : null,
            });
            setVariants((current) => current.map((item) => (item.id === variant.id ? updated : item)));
            setFlash({ type: 'success', message: 'Variant image updated.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const clearImageDraft = () => {
        if (imagePreview) {
            URL.revokeObjectURL(imagePreview);
        }
        setImagePreview(null);
        setImageBlob(null);
        setImageHint(null);
    };

    const handleSelectImage = async (event) => {
        const file = event.target.files?.[0] ?? null;
        event.target.value = '';
        clearImageDraft();

        if (!file) {
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            setFlash({ type: 'error', message: 'Image is too large. Maximum size is 10 MB.' });
            return;
        }

        setImageBusy(true);
        setFlash(null);

        try {
            const cropped = await cropImageFileToSquare(file);
            setImagePreview(cropped.previewUrl);
            setImageBlob(cropped.blob);
            setImageHint(`Square preview ${cropped.width}×${cropped.height} (center cropped)`);
        } catch (err) {
            setFlash({ type: 'error', message: err.message || 'Unable to prepare image.' });
        } finally {
            setImageBusy(false);
        }
    };

    const handleUploadImage = async (event) => {
        event.preventDefault();
        if (!isEdit || !imageBlob || images.length >= 8) {
            return;
        }

        setImageBusy(true);
        setFlash(null);

        const formData = new FormData();
        formData.append('image', imageBlob, 'product.jpg');
        formData.append('is_primary', images.length === 0 ? '1' : '0');
        formData.append('alt_text', form.name || 'Product image');

        try {
            const image = await uploadProductImage(id, formData);
            setImages((current) => [...current, image]);
            clearImageDraft();
            setFlash({ type: 'success', message: 'Image uploaded as 1:1 square.' });
        } catch (err) {
            const normalized = normalizeApiError(err);
            const imageError = firstFieldError(normalized.errors, 'image');
            setFlash({ type: 'error', message: imageError || normalized.message });
        } finally {
            setImageBusy(false);
        }
    };

    const handleDeleteImage = async (imageId) => {
        setImageBusy(true);
        try {
            await deleteProductImage(id, imageId);
            const product = await fetchAdminProduct(id);
            setImages(product.images ?? []);
            setFlash({ type: 'success', message: 'Image removed.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setImageBusy(false);
        }
    };

    const handleSetPrimary = async (imageId) => {
        setImageBusy(true);
        try {
            const updated = await setPrimaryProductImage(id, imageId);
            setImages((current) =>
                current.map((image) => ({
                    ...image,
                    is_primary: image.id === updated.id,
                })),
            );
            setFlash({ type: 'success', message: 'Primary image updated.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setImageBusy(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading product…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit product' : 'New product'}
                actions={
                    <Link to="/admin/products" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to products
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit} className="space-y-6">
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminSelectField
                            label="Category"
                            name="category_id"
                            required
                            value={form.category_id}
                            onChange={(event) => updateField('category_id', event.target.value)}
                            errors={fieldErrors}
                            options={[
                                { value: '', label: 'Select category' },
                                ...categories.map((category) => ({ value: String(category.id), label: category.name })),
                            ]}
                        />
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
                            label="Base price"
                            name="base_price"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            value={form.base_price}
                            onChange={(event) => updateField('base_price', event.target.value)}
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
                        <AdminFormField
                            label="Stock quantity"
                            name="stock_quantity"
                            type="number"
                            min="0"
                            value={form.stock_quantity}
                            onChange={(event) => updateField('stock_quantity', event.target.value)}
                            errors={fieldErrors}
                            hint="Leave blank for unlimited stock."
                        />
                        <AdminFormField
                            label="Low stock threshold"
                            name="low_stock_threshold"
                            type="number"
                            min="0"
                            value={form.low_stock_threshold}
                            onChange={(event) => updateField('low_stock_threshold', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Short description"
                            name="short_description"
                            value={form.short_description}
                            onChange={(event) => updateField('short_description', event.target.value)}
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
                                label="Active"
                                name="is_active"
                                checked={form.is_active}
                                onChange={(event) => updateField('is_active', event.target.checked)}
                            />
                            <AdminCheckboxField
                                label="Available"
                                name="is_available"
                                checked={form.is_available}
                                onChange={(event) => updateField('is_available', event.target.checked)}
                            />
                            <AdminCheckboxField
                                label="Featured"
                                name="is_featured"
                                checked={form.is_featured}
                                onChange={(event) => updateField('is_featured', event.target.checked)}
                            />
                        </div>
                        {addOnGroups.length ? (
                            <div className="sm:col-span-2">
                                <p className="text-sm font-medium text-stone-700">Add-on groups</p>
                                <div className="mt-2 flex flex-wrap gap-3">
                                    {addOnGroups.map((group) => (
                                        <AdminCheckboxField
                                            key={group.id}
                                            label={group.name}
                                            name={`addon_${group.id}`}
                                            checked={form.add_on_group_ids.includes(group.id)}
                                            onChange={() => toggleAddOnGroup(group.id)}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}
                    </AdminPanelBody>
                </AdminPanel>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={saving}
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                    >
                        {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Create product'}
                    </button>
                </div>
            </form>

            {!isEdit ? (
                <AdminPanel>
                    <AdminPanelBody>
                        <h2 className="text-sm font-semibold text-stone-900">Product images</h2>
                        <p className="mt-2 text-sm text-stone-600">
                            Create the product first, then upload square (1:1) images on the edit screen. JPG, PNG, or
                            WEBP · max 10 MB · min 600×600 source.
                        </p>
                    </AdminPanelBody>
                </AdminPanel>
            ) : null}

            {isEdit ? (
                <>
                    <AdminPanel>
                        <AdminPanelBody>
                            <h2 className="text-sm font-semibold text-stone-900">Variants</h2>
                            {variants.length ? (
                                <ul className="mt-3 divide-y divide-stone-100">
                                    {variants.map((variant) => (
                                        <li key={variant.id} className="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="size-12 overflow-hidden rounded-lg bg-stone-100 ring-1 ring-stone-200">
                                                    {variant.image?.url ? (
                                                        <img
                                                            src={variant.image.url}
                                                            alt={variant.image.alt_text || variant.name}
                                                            className="size-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex size-full items-center justify-center text-[10px] text-stone-400">
                                                            None
                                                        </div>
                                                    )}
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium">{variant.name}</p>
                                                    <p className="text-sm text-stone-500">
                                                        {formatMoney(variant.price)}
                                                        {variant.sku ? ` · ${variant.sku}` : ''}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <label className="flex items-center gap-2 text-xs text-stone-600">
                                                    Image
                                                    <select
                                                        className="rounded-md border border-stone-300 bg-white px-2 py-1.5 text-sm"
                                                        value={variant.product_image_id ?? ''}
                                                        onChange={(event) =>
                                                            handleVariantImageChange(variant, event.target.value)
                                                        }
                                                    >
                                                        <option value="">Product default</option>
                                                        {images.map((image) => (
                                                            <option key={image.id} value={image.id}>
                                                                #{image.id}
                                                                {image.is_primary ? ' · primary' : ''}
                                                                {image.alt_text ? ` · ${image.alt_text}` : ''}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </label>
                                                <button
                                                    type="button"
                                                    onClick={() => handleVariantToggle(variant)}
                                                    className="text-sm font-medium text-amber-900 hover:underline"
                                                >
                                                    {variant.is_active ? 'Deactivate' : 'Activate'}
                                                </button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="mt-2 text-sm text-stone-500">No variants yet.</p>
                            )}
                            <form onSubmit={handleAddVariant} className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                <AdminFormField
                                    label="Variant name"
                                    name="variant_name"
                                    required
                                    value={newVariant.name}
                                    onChange={(event) => setNewVariant((current) => ({ ...current, name: event.target.value }))}
                                />
                                <AdminFormField
                                    label="Price"
                                    name="variant_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    value={newVariant.price}
                                    onChange={(event) => setNewVariant((current) => ({ ...current, price: event.target.value }))}
                                />
                                <AdminFormField
                                    label="SKU"
                                    name="variant_sku"
                                    value={newVariant.sku}
                                    onChange={(event) => setNewVariant((current) => ({ ...current, sku: event.target.value }))}
                                />
                                <label className="flex flex-col gap-1 text-sm">
                                    <span className="font-medium text-stone-700">Image</span>
                                    <select
                                        className="rounded-md border border-stone-300 px-3 py-2"
                                        value={newVariant.product_image_id}
                                        onChange={(event) =>
                                            setNewVariant((current) => ({
                                                ...current,
                                                product_image_id: event.target.value,
                                            }))
                                        }
                                    >
                                        <option value="">None (use product default)</option>
                                        {images.map((image) => (
                                            <option key={image.id} value={image.id}>
                                                #{image.id}
                                                {image.is_primary ? ' · primary' : ''}
                                                {image.alt_text ? ` · ${image.alt_text}` : ''}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <div className="flex items-end">
                                    <button
                                        type="submit"
                                        disabled={saving}
                                        className="w-full rounded-md border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-stone-50"
                                    >
                                        Add variant
                                    </button>
                                </div>
                            </form>
                        </AdminPanelBody>
                    </AdminPanel>

                    <AdminPanel>
                        <AdminPanelBody>
                            <h2 className="text-sm font-semibold text-stone-900">Product images</h2>
                            <p className="mt-1 text-sm text-stone-500">
                                1:1 square · JPG / PNG / WEBP · max 10 MB · up to 8 images · center-cropped without
                                distortion
                            </p>

                            {images.length ? (
                                <div className="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                    {images.map((image) => (
                                        <div key={image.id} className="rounded-lg border border-stone-200 p-2">
                                            <div className="aspect-square overflow-hidden rounded bg-stone-100">
                                                <img
                                                    src={image.url}
                                                    alt={image.alt_text || form.name}
                                                    className="size-full object-cover"
                                                />
                                            </div>
                                            <div className="mt-2 flex flex-wrap items-center gap-2">
                                                {image.is_primary ? (
                                                    <span className="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-950">
                                                        Primary
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        disabled={imageBusy}
                                                        onClick={() => handleSetPrimary(image.id)}
                                                        className="text-xs font-medium text-amber-900 hover:underline disabled:opacity-60"
                                                    >
                                                        Set primary
                                                    </button>
                                                )}
                                                <button
                                                    type="button"
                                                    disabled={imageBusy}
                                                    onClick={() => handleDeleteImage(image.id)}
                                                    className="text-xs font-medium text-red-700 hover:underline disabled:opacity-60"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-3 text-sm text-stone-500">No images uploaded yet.</p>
                            )}

                            {images.length < 8 ? (
                                <form onSubmit={handleUploadImage} className="mt-4 space-y-3">
                                    <label className="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-stone-300 bg-stone-50 px-4 py-8 text-center hover:border-amber-700/40">
                                        <span className="text-sm font-semibold text-stone-800">Upload image</span>
                                        <span className="mt-1 text-xs text-stone-500">1:1 square · PNG / JPG / WEBP · Max 10 MB</span>
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={handleSelectImage}
                                            className="sr-only"
                                            disabled={imageBusy}
                                        />
                                    </label>

                                    {imagePreview ? (
                                        <div className="flex flex-wrap items-end gap-4">
                                            <div className="w-40">
                                                <p className="mb-1 text-xs font-medium text-stone-600">Square preview</p>
                                                <div className="aspect-square overflow-hidden rounded-lg border border-stone-200 bg-white">
                                                    <img src={imagePreview} alt="Square crop preview" className="size-full object-cover" />
                                                </div>
                                                {imageHint ? <p className="mt-1 text-xs text-stone-500">{imageHint}</p> : null}
                                            </div>
                                            <div className="flex gap-2">
                                                <button
                                                    type="submit"
                                                    disabled={imageBusy || !imageBlob}
                                                    className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                                                >
                                                    {imageBusy ? 'Uploading…' : 'Confirm upload'}
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled={imageBusy}
                                                    onClick={clearImageDraft}
                                                    className="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-50"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    ) : null}
                                </form>
                            ) : (
                                <p className="mt-3 text-sm text-stone-500">Maximum of 8 images reached.</p>
                            )}
                        </AdminPanelBody>
                    </AdminPanel>
                </>
            ) : null}
        </div>
    );
}

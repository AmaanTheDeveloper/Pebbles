import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
    createAdminHomepageCtaCard,
    createAdminHomepageFeature,
    createAdminHomepageShowcaseItem,
    deleteAdminHomepageCtaCard,
    deleteAdminHomepageFeature,
    deleteAdminHomepageShowcaseItem,
    fetchAdminHomepage,
    reorderAdminHomepageSections,
    updateAdminHomepageCtaCard,
    updateAdminHomepageFeature,
    updateAdminHomepageSection,
    updateAdminHomepageShowcaseItem,
} from '../../api/admin/homepage';
import { fetchAdminCategories } from '../../api/admin/categories';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField, AdminTextareaField } from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const tabs = [
    { id: 'sections', label: 'Sections' },
    { id: 'features', label: 'Why Pebbles' },
    { id: 'cta_cards', label: 'Order Cards' },
    { id: 'showcase', label: 'Showcase' },
];

function StatusBadge({ active }) {
    return (
        <span
            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700'
            }`}
        >
            {active ? 'Active' : 'Hidden'}
        </span>
    );
}

function SectionEditor({ section, onSaved, onClose }) {
    const [form, setForm] = useState({
        label: section.label,
        display_order: section.display_order,
        is_active: section.is_active,
        settings: { ...(section.settings ?? {}) },
    });
    const [heroPreview, setHeroPreview] = useState(section.settings?.hero_image?.url ?? null);
    const [heroFile, setHeroFile] = useState(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    const updateSetting = (key, value) => {
        setForm((current) => ({
            ...current,
            settings: { ...current.settings, [key]: value },
        }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setError(null);

        try {
            let payload;
            if (section.key === 'hero' && heroFile) {
                payload = new FormData();
                payload.append('label', form.label);
                payload.append('display_order', String(form.display_order));
                payload.append('is_active', form.is_active ? '1' : '0');
                Object.entries(form.settings).forEach(([key, value]) => {
                    if (key === 'hero_image' || key === 'hero_product') {
                        return;
                    }
                    if (key === 'chips' && Array.isArray(value)) {
                        value.forEach((chip, index) => payload.append(`settings[chips][${index}]`, chip));
                        return;
                    }
                    if (value !== null && value !== undefined) {
                        payload.append(`settings[${key}]`, value);
                    }
                });
                payload.append('hero_image', heroFile);
            } else {
                payload = {
                    label: form.label,
                    display_order: Number(form.display_order),
                    is_active: form.is_active,
                    settings: form.settings,
                };
            }

            const response = await updateAdminHomepageSection(section.key, payload);
            onSaved(response.message || 'Homepage section updated successfully.');
            onClose();
        } catch (err) {
            setError(normalizeApiError(err).message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            {error ? <p className="text-sm text-red-600">{error}</p> : null}
            <AdminFormField
                label="Section label"
                name="label"
                value={form.label}
                onChange={(event) => setForm({ ...form, label: event.target.value })}
            />
            <div className="grid gap-4 sm:grid-cols-2">
                <AdminFormField
                    label="Display order"
                    name="display_order"
                    type="number"
                    value={form.display_order}
                    onChange={(event) => setForm({ ...form, display_order: event.target.value })}
                />
                <AdminSelectField
                    label="Status"
                    name="is_active"
                    value={form.is_active ? '1' : '0'}
                    onChange={(event) => setForm({ ...form, is_active: event.target.value === '1' })}
                    options={[
                        { value: '1', label: 'Active' },
                        { value: '0', label: 'Hidden' },
                    ]}
                />
            </div>

            {section.key === 'hero' ? (
                <>
                    <AdminFormField label="Badge" name="badge" value={form.settings.badge ?? ''} onChange={(event) => updateSetting('badge', event.target.value)} />
                    <AdminFormField label="Heading line 1" name="heading_line1" value={form.settings.heading_line1 ?? ''} onChange={(event) => updateSetting('heading_line1', event.target.value)} />
                    <AdminFormField label="Heading accent" name="heading_accent" value={form.settings.heading_accent ?? ''} onChange={(event) => updateSetting('heading_accent', event.target.value)} />
                    <AdminTextareaField label="Subtitle" name="subtitle" value={form.settings.subtitle ?? ''} onChange={(event) => updateSetting('subtitle', event.target.value)} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField label="Primary CTA text" name="primary_cta_text" value={form.settings.primary_cta_text ?? ''} onChange={(event) => updateSetting('primary_cta_text', event.target.value)} />
                        <AdminFormField label="Primary CTA URL" name="primary_cta_url" value={form.settings.primary_cta_url ?? ''} onChange={(event) => updateSetting('primary_cta_url', event.target.value)} />
                        <AdminFormField label="Secondary CTA text" name="secondary_cta_text" value={form.settings.secondary_cta_text ?? ''} onChange={(event) => updateSetting('secondary_cta_text', event.target.value)} />
                        <AdminFormField label="Secondary CTA URL" name="secondary_cta_url" value={form.settings.secondary_cta_url ?? ''} onChange={(event) => updateSetting('secondary_cta_url', event.target.value)} />
                    </div>
                    <AdminFormField
                        label="Hero image"
                        name="hero_image"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(event) => {
                            const file = event.target.files?.[0] ?? null;
                            setHeroFile(file);
                            if (file) {
                                setHeroPreview(URL.createObjectURL(file));
                            }
                        }}
                    />
                    {heroPreview ? (
                        <img src={heroPreview} alt="Hero preview" className="h-40 w-full max-w-xs rounded-lg object-cover ring-1 ring-stone-200" />
                    ) : null}
                </>
            ) : null}

            {['favorites', 'featured_ice_cream', 'shakes', 'signature_desserts', 'order_online', 'why_pebbles', 'offers', 'gallery', 'reviews', 'location', 'final_cta'].includes(section.key) ? (
                <>
                    <AdminFormField label="Eyebrow" name="eyebrow" value={form.settings.eyebrow ?? ''} onChange={(event) => updateSetting('eyebrow', event.target.value)} />
                    <AdminFormField label="Title" name="title" value={form.settings.title ?? ''} onChange={(event) => updateSetting('title', event.target.value)} />
                    <AdminTextareaField label="Description" name="description" value={form.settings.description ?? ''} onChange={(event) => updateSetting('description', event.target.value)} />
                </>
            ) : null}

            {['featured_ice_cream', 'shakes'].includes(section.key) ? (
                <>
                    <AdminFormField label="Category slug" name="category_slug" value={form.settings.category_slug ?? ''} onChange={(event) => updateSetting('category_slug', event.target.value)} />
                    <AdminFormField label="CTA label" name="cta_label" value={form.settings.cta_label ?? ''} onChange={(event) => updateSetting('cta_label', event.target.value)} />
                    <AdminFormField label="Product limit" name="product_limit" type="number" value={form.settings.product_limit ?? 8} onChange={(event) => updateSetting('product_limit', Number(event.target.value))} />
                </>
            ) : null}

            {section.key === 'final_cta' ? (
                <div className="grid gap-4 sm:grid-cols-2">
                    <AdminFormField label="Primary CTA text" name="primary_cta_text" value={form.settings.primary_cta_text ?? ''} onChange={(event) => updateSetting('primary_cta_text', event.target.value)} />
                    <AdminFormField label="Primary CTA URL" name="primary_cta_url" value={form.settings.primary_cta_url ?? ''} onChange={(event) => updateSetting('primary_cta_url', event.target.value)} />
                    <AdminFormField label="Secondary CTA text" name="secondary_cta_text" value={form.settings.secondary_cta_text ?? ''} onChange={(event) => updateSetting('secondary_cta_text', event.target.value)} />
                    <AdminFormField label="Secondary CTA URL" name="secondary_cta_url" value={form.settings.secondary_cta_url ?? ''} onChange={(event) => updateSetting('secondary_cta_url', event.target.value)} />
                </div>
            ) : null}

            <div className="flex flex-wrap gap-2">
                <button type="submit" disabled={saving} className="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
                    {saving ? 'Saving…' : 'Save section'}
                </button>
                <button type="button" onClick={onClose} className="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700">
                    Cancel
                </button>
            </div>
        </form>
    );
}

export default function AdminHomepagePage() {
    const [payload, setPayload] = useState(null);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [activeTab, setActiveTab] = useState('sections');
    const [editingSection, setEditingSection] = useState(null);
    const [reordering, setReordering] = useState(false);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const [homepage, categoryResponse] = await Promise.all([fetchAdminHomepage(), fetchAdminCategories()]);
            setPayload(homepage);
            setCategories(categoryResponse?.data ?? []);
        } catch (err) {
            setError(normalizeApiError(err));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const linkedResources = useMemo(
        () => [
            { to: '/admin/products', label: 'Featured products', hint: 'Use product featured flag for homepage hero and strips.' },
            { to: '/admin/gallery', label: 'Gallery', hint: 'Homepage gallery preview uses active gallery items.' },
            { to: '/admin/reviews', label: 'Reviews', hint: 'Feature approved reviews to show them on the homepage.' },
            { to: '/admin/offers', label: 'Offers', hint: 'Active offers appear automatically in the homepage offers section.' },
            { to: '/admin/branches', label: 'Branches', hint: 'Location section uses the selected branch details.' },
        ],
        [],
    );

    const handleToggleSection = async (section) => {
        try {
            const response = await updateAdminHomepageSection(section.key, { is_active: !section.is_active });
            setFlash({ type: 'success', message: response.message });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const handleReorder = async () => {
        if (!payload?.sections?.length) {
            return;
        }

        setReordering(true);
        try {
            const response = await reorderAdminHomepageSections(
                payload.sections.map((section, index) => ({
                    key: section.key,
                    display_order: index + 1,
                })),
            );
            setPayload(response.data);
            setFlash({ type: 'success', message: response.message });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setReordering(false);
        }
    };

    const moveSection = (index, direction) => {
        const next = [...(payload?.sections ?? [])];
        const target = index + direction;
        if (target < 0 || target >= next.length) {
            return;
        }
        [next[index], next[target]] = [next[target], next[index]];
        setPayload({ ...payload, sections: next });
    };

    if (loading) {
        return <LoadingState label="Loading homepage management…" />;
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={load} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Homepage"
                description="Manage homepage sections, content, ordering, and visibility without changing the storefront design."
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <AdminPanel>
                    <AdminPanelBody>
                        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active sections</p>
                        <p className="mt-2 text-3xl font-semibold text-stone-900">{payload.summary.active_sections}</p>
                    </AdminPanelBody>
                </AdminPanel>
                <AdminPanel>
                    <AdminPanelBody>
                        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Hidden sections</p>
                        <p className="mt-2 text-3xl font-semibold text-stone-900">{payload.summary.hidden_sections}</p>
                    </AdminPanelBody>
                </AdminPanel>
                <AdminPanel>
                    <AdminPanelBody>
                        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Homepage items</p>
                        <p className="mt-2 text-3xl font-semibold text-stone-900">
                            {payload.summary.feature_count + payload.summary.cta_card_count + payload.summary.showcase_count}
                        </p>
                    </AdminPanelBody>
                </AdminPanel>
                <AdminPanel>
                    <AdminPanelBody>
                        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Last updated</p>
                        <p className="mt-2 text-sm font-medium text-stone-900">
                            {payload.summary.last_updated ? new Date(payload.summary.last_updated).toLocaleString() : '—'}
                        </p>
                    </AdminPanelBody>
                </AdminPanel>
            </div>

            <AdminPanel>
                <AdminPanelBody className="space-y-3">
                    <p className="text-sm font-semibold text-stone-900">Linked content systems</p>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {linkedResources.map((resource) => (
                            <Link
                                key={resource.to}
                                to={resource.to}
                                className="rounded-lg border border-stone-200 p-4 transition hover:border-amber-700 hover:bg-amber-50/40"
                            >
                                <p className="font-medium text-stone-900">{resource.label}</p>
                                <p className="mt-1 text-xs text-stone-600">{resource.hint}</p>
                            </Link>
                        ))}
                    </div>
                </AdminPanelBody>
            </AdminPanel>

            <div className="flex flex-wrap gap-2">
                {tabs.map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        onClick={() => setActiveTab(tab.id)}
                        className={`rounded-md px-3 py-2 text-sm font-medium ${
                            activeTab === tab.id ? 'bg-stone-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {activeTab === 'sections' ? (
                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm text-stone-600">Reorder sections, edit content, and hide/show homepage blocks.</p>
                            <button
                                type="button"
                                onClick={handleReorder}
                                disabled={reordering}
                                className="rounded-md bg-amber-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                            >
                                {reordering ? 'Saving order…' : 'Save section order'}
                            </button>
                        </div>

                        {editingSection ? (
                            <AdminPanel className="border-amber-200">
                                <AdminPanelBody>
                                    <p className="mb-4 text-sm font-semibold text-stone-900">Edit {editingSection.label}</p>
                                    <SectionEditor
                                        section={editingSection}
                                        onSaved={(message) => {
                                            setFlash({ type: 'success', message });
                                            load();
                                        }}
                                        onClose={() => setEditingSection(null)}
                                    />
                                </AdminPanelBody>
                            </AdminPanel>
                        ) : null}

                        <div className="space-y-3">
                            {(payload.sections ?? []).map((section, index) => (
                                <div key={section.key} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-stone-200 p-4">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium text-stone-900">{section.label}</p>
                                            <StatusBadge active={section.is_active} />
                                            <span className="text-xs text-stone-500">Order {index + 1}</span>
                                        </div>
                                        <p className="mt-1 text-xs text-stone-500">{section.key}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <button type="button" onClick={() => moveSection(index, -1)} className="rounded-md border border-stone-300 px-2 py-1 text-xs">
                                            Up
                                        </button>
                                        <button type="button" onClick={() => moveSection(index, 1)} className="rounded-md border border-stone-300 px-2 py-1 text-xs">
                                            Down
                                        </button>
                                        <button type="button" onClick={() => setEditingSection(section)} className="rounded-md border border-stone-300 px-3 py-1.5 text-xs font-medium">
                                            Edit
                                        </button>
                                        <button type="button" onClick={() => handleToggleSection(section)} className="rounded-md border border-stone-300 px-3 py-1.5 text-xs font-medium">
                                            {section.is_active ? 'Hide' : 'Show'}
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </AdminPanelBody>
                </AdminPanel>
            ) : null}

            {activeTab === 'features' ? (
                <FeatureManager
                    features={payload.features ?? []}
                    onChange={load}
                    setFlash={setFlash}
                />
            ) : null}

            {activeTab === 'cta_cards' ? (
                <CtaCardManager cards={payload.cta_cards ?? []} onChange={load} setFlash={setFlash} />
            ) : null}

            {activeTab === 'showcase' ? (
                <ShowcaseManager
                    items={payload.showcase_items ?? []}
                    categories={categories}
                    onChange={load}
                    setFlash={setFlash}
                />
            ) : null}
        </div>
    );
}

function FeatureManager({ features, onChange, setFlash }) {
    const empty = { title: '', description: '', icon_letter: 'P', accent: 'from-brand-green/15 to-brand-green/5', glow: 'lift-card-green', display_order: features.length + 1, is_active: true };
    const [form, setForm] = useState(empty);
    const [editingId, setEditingId] = useState(null);
    const [saving, setSaving] = useState(false);

    const save = async (event) => {
        event.preventDefault();
        setSaving(true);
        try {
            const response = editingId
                ? await updateAdminHomepageFeature(editingId, form)
                : await createAdminHomepageFeature(form);
            setFlash({ type: 'success', message: response.message });
            setForm(empty);
            setEditingId(null);
            onChange();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const remove = async (id) => {
        if (!window.confirm('Are you sure you want to delete this homepage feature?')) {
            return;
        }
        try {
            const response = await deleteAdminHomepageFeature(id);
            setFlash({ type: 'success', message: response.message });
            onChange();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <AdminPanel>
                <AdminPanelBody>
                    <form onSubmit={save} className="space-y-3">
                        <p className="font-medium text-stone-900">{editingId ? 'Edit feature' : 'Add feature'}</p>
                        <AdminFormField label="Title" name="title" value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} required />
                        <AdminTextareaField label="Description" name="description" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} required />
                        <AdminFormField label="Icon letter" name="icon_letter" value={form.icon_letter} onChange={(event) => setForm({ ...form, icon_letter: event.target.value })} />
                        <AdminFormField label="Display order" name="display_order" type="number" value={form.display_order} onChange={(event) => setForm({ ...form, display_order: Number(event.target.value) })} />
                        <button type="submit" disabled={saving} className="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
                            {saving ? 'Saving…' : editingId ? 'Update feature' : 'Add feature'}
                        </button>
                    </form>
                </AdminPanelBody>
            </AdminPanel>
            <AdminPanel>
                <AdminPanelBody className="space-y-3">
                    {features.map((feature) => (
                        <div key={feature.id} className="rounded-lg border border-stone-200 p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="flex size-8 items-center justify-center rounded-md bg-stone-100 font-semibold">{feature.icon_letter}</span>
                                        <p className="font-medium text-stone-900">{feature.title}</p>
                                        <StatusBadge active={feature.is_active} />
                                    </div>
                                    <p className="mt-2 text-sm text-stone-600">{feature.description}</p>
                                </div>
                                <div className="flex gap-2">
                                    <button type="button" onClick={() => { setEditingId(feature.id); setForm(feature); }} className="text-xs font-medium text-amber-800">Edit</button>
                                    <button type="button" onClick={() => remove(feature.id)} className="text-xs font-medium text-red-700">Delete</button>
                                </div>
                            </div>
                        </div>
                    ))}
                </AdminPanelBody>
            </AdminPanel>
        </div>
    );
}

function CtaCardManager({ cards, onChange, setFlash }) {
    const empty = { title: '', description: '', link_url: '/menu', tone: 'orange', display_order: cards.length + 1, is_active: true };
    const [form, setForm] = useState(empty);
    const [editingId, setEditingId] = useState(null);
    const [saving, setSaving] = useState(false);

    const save = async (event) => {
        event.preventDefault();
        setSaving(true);
        try {
            const response = editingId ? await updateAdminHomepageCtaCard(editingId, form) : await createAdminHomepageCtaCard(form);
            setFlash({ type: 'success', message: response.message });
            setForm(empty);
            setEditingId(null);
            onChange();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const remove = async (id) => {
        if (!window.confirm('Are you sure you want to delete this homepage card?')) {
            return;
        }
        try {
            const response = await deleteAdminHomepageCtaCard(id);
            setFlash({ type: 'success', message: response.message });
            onChange();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <AdminPanel>
                <AdminPanelBody>
                    <form onSubmit={save} className="space-y-3">
                        <p className="font-medium text-stone-900">{editingId ? 'Edit card' : 'Add card'}</p>
                        <AdminFormField label="Title" name="title" value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} required />
                        <AdminTextareaField label="Description" name="description" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} required />
                        <AdminFormField label="Link URL" name="link_url" value={form.link_url} onChange={(event) => setForm({ ...form, link_url: event.target.value })} required />
                        <AdminSelectField label="Tone" name="tone" value={form.tone} onChange={(event) => setForm({ ...form, tone: event.target.value })} options={[{ value: 'orange', label: 'Orange' }, { value: 'green', label: 'Green' }, { value: 'white', label: 'White' }]} />
                        <button type="submit" disabled={saving} className="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
                            {saving ? 'Saving…' : editingId ? 'Update card' : 'Add card'}
                        </button>
                    </form>
                </AdminPanelBody>
            </AdminPanel>
            <AdminPanel>
                <AdminPanelBody className="space-y-3">
                    {cards.map((card) => (
                        <div key={card.id} className="rounded-lg border border-stone-200 p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <p className="font-medium text-stone-900">{card.title}</p>
                                        <StatusBadge active={card.is_active} />
                                    </div>
                                    <p className="mt-1 text-sm text-stone-600">{card.description}</p>
                                    <p className="mt-1 text-xs text-stone-500">{card.link_url}</p>
                                </div>
                                <div className="flex gap-2">
                                    <button type="button" onClick={() => { setEditingId(card.id); setForm(card); }} className="text-xs font-medium text-amber-800">Edit</button>
                                    <button type="button" onClick={() => remove(card.id)} className="text-xs font-medium text-red-700">Delete</button>
                                </div>
                            </div>
                        </div>
                    ))}
                </AdminPanelBody>
            </AdminPanel>
        </div>
    );
}

function ShowcaseManager({ items, categories, onChange, setFlash }) {
    const empty = { pillar_key: 'ice-cream', category_id: '', label: '', blurb: '', badge: '', accent: 'orange', is_special: false, display_order: items.length + 1, is_active: true };
    const [form, setForm] = useState(empty);
    const [editingId, setEditingId] = useState(null);
    const [saving, setSaving] = useState(false);

    const save = async (event) => {
        event.preventDefault();
        setSaving(true);
        try {
            const payload = {
                ...form,
                category_id: form.category_id ? Number(form.category_id) : null,
                is_special: Boolean(form.is_special),
            };
            const response = editingId
                ? await updateAdminHomepageShowcaseItem(editingId, payload)
                : await createAdminHomepageShowcaseItem(payload);
            setFlash({ type: 'success', message: response.message });
            setForm(empty);
            setEditingId(null);
            onChange();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const remove = async (id) => {
        if (!window.confirm('Are you sure you want to delete this showcase item?')) {
            return;
        }
        try {
            const response = await deleteAdminHomepageShowcaseItem(id);
            setFlash({ type: 'success', message: response.message });
            onChange();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <AdminPanel>
                <AdminPanelBody>
                    <form onSubmit={save} className="space-y-3">
                        <p className="font-medium text-stone-900">{editingId ? 'Edit showcase item' : 'Add showcase item'}</p>
                        <AdminFormField label="Pillar key" name="pillar_key" value={form.pillar_key} onChange={(event) => setForm({ ...form, pillar_key: event.target.value })} />
                        <AdminSelectField
                            label="Category override"
                            name="category_id"
                            value={form.category_id ?? ''}
                            onChange={(event) => setForm({ ...form, category_id: event.target.value })}
                            options={[{ value: '', label: 'Use default pillar category' }, ...categories.map((category) => ({ value: String(category.id), label: category.name }))]}
                        />
                        <AdminFormField label="Label override" name="label" value={form.label ?? ''} onChange={(event) => setForm({ ...form, label: event.target.value })} />
                        <AdminTextareaField label="Blurb override" name="blurb" value={form.blurb ?? ''} onChange={(event) => setForm({ ...form, blurb: event.target.value })} />
                        <AdminFormField label="Badge" name="badge" value={form.badge ?? ''} onChange={(event) => setForm({ ...form, badge: event.target.value })} />
                        <AdminSelectField label="Accent" name="accent" value={form.accent} onChange={(event) => setForm({ ...form, accent: event.target.value })} options={[{ value: 'orange', label: 'Orange' }, { value: 'green', label: 'Green' }]} />
                        <button type="submit" disabled={saving} className="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
                            {saving ? 'Saving…' : editingId ? 'Update item' : 'Add item'}
                        </button>
                    </form>
                </AdminPanelBody>
            </AdminPanel>
            <AdminPanel>
                <AdminPanelBody className="space-y-3">
                    {items.map((item) => (
                        <div key={item.id} className="rounded-lg border border-stone-200 p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <p className="font-medium text-stone-900">{item.label || item.pillar_key}</p>
                                        <StatusBadge active={item.is_active} />
                                    </div>
                                    <p className="mt-1 text-sm text-stone-600">{item.blurb}</p>
                                    <p className="mt-1 text-xs text-stone-500">
                                        {item.category?.name ? `Category: ${item.category.name}` : item.pillar_key}
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <button type="button" onClick={() => { setEditingId(item.id); setForm({ ...item, category_id: item.category_id ? String(item.category_id) : '' }); }} className="text-xs font-medium text-amber-800">Edit</button>
                                    <button type="button" onClick={() => remove(item.id)} className="text-xs font-medium text-red-700">Delete</button>
                                </div>
                            </div>
                        </div>
                    ))}
                </AdminPanelBody>
            </AdminPanel>
        </div>
    );
}

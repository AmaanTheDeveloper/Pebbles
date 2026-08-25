import { useEffect, useState } from 'react';
import { fetchAdminAbout, updateAdminAbout } from '../../api/admin/about';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminTextareaField } from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { DEFAULT_ABOUT_CONTENT } from '../../lib/aboutContent';
import { invalidateCatalogCache } from '../../lib/catalogCache';
import { normalizeApiError } from '../../lib/errors';

function cloneDefaults() {
    return structuredClone(DEFAULT_ABOUT_CONTENT);
}

function listToText(items, mapper) {
    return (items ?? []).map(mapper).join('\n');
}

function parseLines(text) {
    return String(text ?? '')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
}

function parsePipeRows(text, keys) {
    return parseLines(text).map((line) => {
        const parts = line.split('|').map((part) => part.trim());
        const row = {};
        keys.forEach((key, index) => {
            row[key] = parts[index] ?? '';
        });
        return row;
    });
}

export default function AdminAboutPage() {
    const [form, setForm] = useState(cloneDefaults());
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const payload = await fetchAdminAbout();
            setForm({
                ...cloneDefaults(),
                ...(payload ?? {}),
                hero: { ...cloneDefaults().hero, ...(payload?.hero ?? {}) },
                story: { ...cloneDefaults().story, ...(payload?.story ?? {}) },
                philosophy: { ...cloneDefaults().philosophy, ...(payload?.philosophy ?? {}) },
                team: { ...cloneDefaults().team, ...(payload?.team ?? {}) },
                values: { ...cloneDefaults().values, ...(payload?.values ?? {}) },
                ambience: { ...cloneDefaults().ambience, ...(payload?.ambience ?? {}) },
                brand_statement: { ...cloneDefaults().brand_statement, ...(payload?.brand_statement ?? {}) },
            });
        } catch (err) {
            setError(normalizeApiError(err));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const updateSection = (section, field, value) => {
        setForm((current) => ({
            ...current,
            [section]: {
                ...current[section],
                [field]: value,
            },
        }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);

        try {
            const response = await updateAdminAbout(form);
            setForm({
                ...cloneDefaults(),
                ...(response.data ?? {}),
            });
            invalidateCatalogCache('about');
            setFlash({ type: 'success', message: response.message || 'About page content saved.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading about content…" />;
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={load} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="About page"
                description="Edit storefront About copy. Empty fields fall back to the default Pebbles story."
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit} className="space-y-6">
                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Hero</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Eyebrow" name="hero_eyebrow" value={form.hero.eyebrow} onChange={(e) => updateSection('hero', 'eyebrow', e.target.value)} />
                            <AdminFormField label="Title line" name="hero_title_line1" value={form.hero.title_line1} onChange={(e) => updateSection('hero', 'title_line1', e.target.value)} />
                            <AdminFormField label="Accent title" name="hero_title_accent" value={form.hero.title_accent} onChange={(e) => updateSection('hero', 'title_accent', e.target.value)} />
                        </div>
                        <AdminTextareaField label="Subtitle" name="hero_subtitle" rows={3} value={form.hero.subtitle} onChange={(e) => updateSection('hero', 'subtitle', e.target.value)} />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Story</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Eyebrow" name="story_eyebrow" value={form.story.eyebrow} onChange={(e) => updateSection('story', 'eyebrow', e.target.value)} />
                            <AdminFormField label="Title" name="story_title" value={form.story.title} onChange={(e) => updateSection('story', 'title', e.target.value)} />
                            <AdminFormField label="Accent title" name="story_title_accent" value={form.story.title_accent} onChange={(e) => updateSection('story', 'title_accent', e.target.value)} />
                        </div>
                        <AdminTextareaField
                            label="Paragraphs"
                            name="story_paragraphs"
                            rows={6}
                            value={(form.story.paragraphs ?? []).join('\n\n')}
                            onChange={(e) =>
                                updateSection(
                                    'story',
                                    'paragraphs',
                                    e.target.value
                                        .split(/\n\s*\n/)
                                        .map((part) => part.trim())
                                        .filter(Boolean),
                                )
                            }
                            hint="Separate paragraphs with a blank line."
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Philosophy</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Eyebrow" name="philosophy_eyebrow" value={form.philosophy.eyebrow} onChange={(e) => updateSection('philosophy', 'eyebrow', e.target.value)} />
                            <AdminFormField label="Title" name="philosophy_title" value={form.philosophy.title} onChange={(e) => updateSection('philosophy', 'title', e.target.value)} />
                        </div>
                        <AdminTextareaField label="Subtitle" name="philosophy_subtitle" rows={2} value={form.philosophy.subtitle} onChange={(e) => updateSection('philosophy', 'subtitle', e.target.value)} />
                        <AdminTextareaField
                            label="Items"
                            name="philosophy_items"
                            rows={6}
                            value={listToText(form.philosophy.items, (item) => `${item.icon}|${item.title}|${item.description}`)}
                            onChange={(e) =>
                                updateSection(
                                    'philosophy',
                                    'items',
                                    parsePipeRows(e.target.value, ['icon', 'title', 'description']),
                                )
                            }
                            hint="One item per line: icon|title|description"
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Team</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Eyebrow" name="team_eyebrow" value={form.team.eyebrow} onChange={(e) => updateSection('team', 'eyebrow', e.target.value)} />
                            <AdminFormField label="Title" name="team_title" value={form.team.title} onChange={(e) => updateSection('team', 'title', e.target.value)} />
                        </div>
                        <AdminTextareaField label="Subtitle" name="team_subtitle" rows={2} value={form.team.subtitle} onChange={(e) => updateSection('team', 'subtitle', e.target.value)} />
                        <AdminTextareaField
                            label="Roles"
                            name="team_roles"
                            rows={6}
                            value={listToText(form.team.roles, (role) => `${role.key}|${role.role}|${role.description}|${role.accent}`)}
                            onChange={(e) =>
                                updateSection(
                                    'team',
                                    'roles',
                                    parsePipeRows(e.target.value, ['key', 'role', 'description', 'accent']),
                                )
                            }
                            hint="One role per line: key|role|description|accent"
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Values</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Eyebrow" name="values_eyebrow" value={form.values.eyebrow} onChange={(e) => updateSection('values', 'eyebrow', e.target.value)} />
                            <AdminFormField label="Title" name="values_title" value={form.values.title} onChange={(e) => updateSection('values', 'title', e.target.value)} />
                        </div>
                        <AdminTextareaField label="Subtitle" name="values_subtitle" rows={2} value={form.values.subtitle} onChange={(e) => updateSection('values', 'subtitle', e.target.value)} />
                        <AdminTextareaField
                            label="Items"
                            name="values_items"
                            rows={7}
                            value={listToText(form.values.items, (item) => `${item.icon}|${item.title}|${item.description}`)}
                            onChange={(e) =>
                                updateSection(
                                    'values',
                                    'items',
                                    parsePipeRows(e.target.value, ['icon', 'title', 'description']),
                                )
                            }
                            hint="One value per line: icon|title|description"
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Ambience</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Eyebrow" name="ambience_eyebrow" value={form.ambience.eyebrow} onChange={(e) => updateSection('ambience', 'eyebrow', e.target.value)} />
                            <AdminFormField label="Title" name="ambience_title" value={form.ambience.title} onChange={(e) => updateSection('ambience', 'title', e.target.value)} />
                        </div>
                        <AdminTextareaField label="Subtitle" name="ambience_subtitle" rows={2} value={form.ambience.subtitle} onChange={(e) => updateSection('ambience', 'subtitle', e.target.value)} />
                        <AdminTextareaField
                            label="Points"
                            name="ambience_points"
                            rows={5}
                            value={listToText(form.ambience.points, (point) => `${point.title}|${point.text}`)}
                            onChange={(e) =>
                                updateSection(
                                    'ambience',
                                    'points',
                                    parsePipeRows(e.target.value, ['title', 'text']),
                                )
                            }
                            hint="One point per line: title|text"
                        />
                    </AdminPanelBody>
                </AdminPanel>

                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <h2 className="text-lg font-semibold text-stone-900">Brand statement</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <AdminFormField label="Eyebrow" name="brand_eyebrow" value={form.brand_statement.eyebrow} onChange={(e) => updateSection('brand_statement', 'eyebrow', e.target.value)} />
                            <AdminFormField label="Title" name="brand_title" value={form.brand_statement.title} onChange={(e) => updateSection('brand_statement', 'title', e.target.value)} />
                            <AdminFormField label="Title line 2" name="brand_title_line2" value={form.brand_statement.title_line2} onChange={(e) => updateSection('brand_statement', 'title_line2', e.target.value)} />
                        </div>
                        <AdminTextareaField label="Subtitle" name="brand_subtitle" rows={3} value={form.brand_statement.subtitle} onChange={(e) => updateSection('brand_statement', 'subtitle', e.target.value)} />
                    </AdminPanelBody>
                </AdminPanel>

                <button
                    type="submit"
                    disabled={saving}
                    className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {saving ? 'Saving…' : 'Save about content'}
                </button>
            </form>
        </div>
    );
}

import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { deleteAdminReview, fetchAdminReviews, updateAdminReview } from '../../api/admin/reviews';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField, AdminTextareaField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { firstFieldError, normalizeApiError } from '../../lib/errors';
import StarRatingInput from '../../components/storefront/reviews/StarRatingInput';

const statusOptions = [
    { value: '', label: 'All statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
];

const ratingOptions = [
    { value: '', label: 'All ratings' },
    { value: '5', label: '5 stars' },
    { value: '4', label: '4 stars' },
    { value: '3', label: '3 stars' },
    { value: '2', label: '2 stars' },
    { value: '1', label: '1 star' },
];

const statusStyles = {
    pending: 'bg-amber-100 text-amber-900',
    approved: 'bg-emerald-100 text-emerald-900',
    rejected: 'bg-red-100 text-red-900',
};

export default function AdminReviewsPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null, stats: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
    const [rating, setRating] = useState(searchParams.get('rating') ?? '');
    const [editing, setEditing] = useState(null);
    const [editForm, setEditForm] = useState(null);
    const [editErrors, setEditErrors] = useState({});
    const [saving, setSaving] = useState(false);

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
            ...(searchParams.get('status') ? { status: searchParams.get('status') } : {}),
            ...(searchParams.get('rating') ? { rating: searchParams.get('rating') } : {}),
        };

        try {
            const response = await fetchAdminReviews(params);
            setPayload({
                data: response.data ?? [],
                meta: response.meta ?? null,
                links: response.links ?? null,
                stats: response.stats ?? null,
            });
        } catch (err) {
            setError(normalizeApiError(err));
            setPayload({ data: [], meta: null, links: null, stats: null });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [searchParams]);

    const applyFilters = (event) => {
        event.preventDefault();
        const next = new URLSearchParams();
        if (search) {
            next.set('search', search);
        }
        if (status) {
            next.set('status', status);
        }
        if (rating) {
            next.set('rating', rating);
        }
        setSearchParams(next);
    };

    const handleAction = async (review, patch, successMessage) => {
        try {
            const response = await updateAdminReview(review.id, patch);
            setFlash({ type: 'success', message: response.message || successMessage });
            if (editing?.id === review.id) {
                setEditing(null);
                setEditForm(null);
            }
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const handleDelete = async (review) => {
        if (!window.confirm(`Delete review from "${review.name}"?`)) {
            return;
        }

        try {
            const response = await deleteAdminReview(review.id);
            setFlash({ type: 'success', message: response.message || 'Review deleted successfully.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const openEdit = (review) => {
        setEditing(review);
        setEditForm({
            name: review.name,
            email: review.email,
            rating: review.rating,
            review: review.review,
            status: review.status,
            is_featured: review.is_featured,
        });
        setEditErrors({});
    };

    const saveEdit = async (event) => {
        event.preventDefault();
        if (!editing || !editForm || saving) {
            return;
        }

        setSaving(true);
        setEditErrors({});

        try {
            const response = await updateAdminReview(editing.id, editForm);
            setFlash({ type: 'success', message: response.message || 'Review updated successfully.' });
            setEditing(null);
            setEditForm(null);
            load();
        } catch (err) {
            const normalized = normalizeApiError(err);
            setEditErrors(normalized.errors ?? {});
            if (!Object.keys(normalized.errors ?? {}).length) {
                setFlash({ type: 'error', message: normalized.message });
            }
        } finally {
            setSaving(false);
        }
    };

    const stats = payload.stats ?? {};

    const columns = [
        {
            key: 'name',
            label: 'Customer',
            render: (row) => (
                <div>
                    <p className="font-medium text-amber-900">{row.name}</p>
                    <p className="text-xs text-stone-500">{row.email}</p>
                </div>
            ),
        },
        {
            key: 'rating',
            label: 'Rating',
            render: (row) => `${row.rating} / 5`,
        },
        {
            key: 'review',
            label: 'Review',
            render: (row) => (
                <p className="max-w-md truncate text-stone-700" title={row.review}>
                    {row.review}
                </p>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <span className={`rounded-full px-2 py-0.5 text-xs ${statusStyles[row.status] ?? 'bg-stone-200 text-stone-600'}`}>
                    {row.status_label ?? row.status}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: 'Date',
            render: (row) => (row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'),
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    {row.status !== 'approved' ? (
                        <button
                            type="button"
                            className="rounded bg-emerald-700 px-2 py-1 text-xs font-semibold text-white"
                            onClick={() => handleAction(row, { status: 'approved' }, 'Review approved successfully.')}
                        >
                            Approve
                        </button>
                    ) : null}
                    {row.status !== 'rejected' ? (
                        <button
                            type="button"
                            className="rounded bg-stone-700 px-2 py-1 text-xs font-semibold text-white"
                            onClick={() => handleAction(row, { status: 'rejected' }, 'Review rejected successfully.')}
                        >
                            Reject
                        </button>
                    ) : null}
                    <button
                        type="button"
                        className="rounded bg-amber-700 px-2 py-1 text-xs font-semibold text-white"
                        onClick={() => openEdit(row)}
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        className="rounded border border-stone-300 px-2 py-1 text-xs font-semibold text-stone-700"
                        onClick={() =>
                            handleAction(
                                row,
                                { is_featured: !row.is_featured },
                                row.is_featured ? 'Review unfeatured.' : 'Review featured.',
                            )
                        }
                    >
                        {row.is_featured ? 'Unfeature' : 'Feature'}
                    </button>
                    <button
                        type="button"
                        className="rounded bg-red-700 px-2 py-1 text-xs font-semibold text-white"
                        onClick={() => handleDelete(row)}
                    >
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <AdminPageHeader title="Reviews" description="Moderate customer reviews before they appear on the website." />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                {[
                    ['Total Reviews', stats.total ?? 0],
                    ['Pending', stats.pending ?? 0],
                    ['Approved', stats.approved ?? 0],
                    ['Rejected', stats.rejected ?? 0],
                    ['Average Rating', stats.average_rating ?? '—'],
                ].map(([label, value]) => (
                    <AdminPanel key={label}>
                        <AdminPanelBody>
                            <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">{label}</p>
                            <p className="mt-2 text-2xl font-semibold text-stone-900">{value}</p>
                        </AdminPanelBody>
                    </AdminPanel>
                ))}
            </div>

            <AdminPanel>
                <AdminPanelBody>
                    <form className="grid gap-4 md:grid-cols-4" onSubmit={applyFilters}>
                        <AdminFormField label="Search" value={search} onChange={(event) => setSearch(event.target.value)} />
                        <AdminSelectField label="Status" value={status} onChange={(event) => setStatus(event.target.value)} options={statusOptions} />
                        <AdminSelectField label="Rating" value={rating} onChange={(event) => setRating(event.target.value)} options={ratingOptions} />
                        <div className="flex items-end">
                            <button type="submit" className="rounded bg-amber-800 px-4 py-2 text-sm font-semibold text-white">
                                Apply filters
                            </button>
                        </div>
                    </form>
                </AdminPanelBody>
            </AdminPanel>

            {editing && editForm ? (
                <AdminPanel>
                    <AdminPanelBody>
                        <form className="space-y-4" onSubmit={saveEdit}>
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-lg font-semibold text-stone-900">Edit review</h2>
                                <button type="button" className="text-sm text-stone-500" onClick={() => setEditing(null)}>
                                    Cancel
                                </button>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <AdminFormField
                                    label="Name"
                                    value={editForm.name}
                                    onChange={(event) => setEditForm({ ...editForm, name: event.target.value })}
                                    error={firstFieldError(editErrors, 'name')}
                                />
                                <AdminFormField
                                    label="Email"
                                    value={editForm.email}
                                    onChange={(event) => setEditForm({ ...editForm, email: event.target.value })}
                                    error={firstFieldError(editErrors, 'email')}
                                />
                            </div>
                            <div>
                                <span className="mb-2 block text-sm font-medium text-stone-700">Rating</span>
                                <StarRatingInput
                                    value={editForm.rating}
                                    onChange={(value) => setEditForm({ ...editForm, rating: value })}
                                    error={firstFieldError(editErrors, 'rating')}
                                />
                            </div>
                            <AdminTextareaField
                                label="Review"
                                value={editForm.review}
                                onChange={(event) => setEditForm({ ...editForm, review: event.target.value })}
                                error={firstFieldError(editErrors, 'review')}
                            />
                            <div className="grid gap-4 md:grid-cols-2">
                                <AdminSelectField
                                    label="Status"
                                    value={editForm.status}
                                    onChange={(event) => setEditForm({ ...editForm, status: event.target.value })}
                                    options={statusOptions.filter((option) => option.value !== '')}
                                />
                                <label className="flex items-center gap-2 pt-8 text-sm font-medium text-stone-700">
                                    <input
                                        type="checkbox"
                                        checked={editForm.is_featured}
                                        onChange={(event) => setEditForm({ ...editForm, is_featured: event.target.checked })}
                                    />
                                    Featured on homepage
                                </label>
                            </div>
                            <button type="submit" className="rounded bg-amber-800 px-4 py-2 text-sm font-semibold text-white" disabled={saving}>
                                {saving ? 'Saving…' : 'Save changes'}
                            </button>
                        </form>
                    </AdminPanelBody>
                </AdminPanel>
            ) : null}

            <AdminDataTable
                columns={columns}
                rows={payload.data}
                loading={loading}
                error={error}
                onRetry={load}
                emptyTitle="No reviews found"
                pagination={<AdminPagination meta={payload.meta} links={payload.links} />}
            />
        </div>
    );
}

import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { fetchAdminBranches } from '../../api/admin/branches';
import { fetchAdminUsers, updateAdminUser } from '../../api/admin/users';
import { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminButton, AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const roleTabs = [
    { value: '', label: 'All' },
    { value: 'user', label: 'Users' },
    { value: 'manager', label: 'Managers' },
    { value: 'super_admin', label: 'Super Admins' },
];

const roleOptions = [
    { value: 'user', label: 'User' },
    { value: 'manager', label: 'Manager' },
    { value: 'super_admin', label: 'Super Admin' },
];

export default function AdminUsersPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null, stats: null });
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [editing, setEditing] = useState(null);
    const [saving, setSaving] = useState(false);
    const [form, setForm] = useState({
        name: '',
        email: '',
        role: 'user',
        branch_id: '',
    });

    const role = searchParams.get('role') ?? '';

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const [usersResponse, branchesResponse] = await Promise.all([
                fetchAdminUsers({
                    page: searchParams.get('page') ?? '1',
                    ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
                    ...(role ? { role } : {}),
                }),
                fetchAdminBranches(),
            ]);

            setPayload({
                data: usersResponse.data ?? [],
                meta: usersResponse.meta ?? null,
                links: usersResponse.links ?? null,
                stats: usersResponse.stats ?? null,
            });
            setBranches(
                (branchesResponse.data ?? [])
                    .filter((branch) => branch.is_active !== false),
            );
        } catch (err) {
            setError(normalizeApiError(err));
            setPayload({ data: [], meta: null, links: null, stats: null });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        document.title = 'Users — Admin';
    }, [searchParams]);

    const openEdit = (user) => {
        setFlash(null);
        setEditing(user);
        setForm({
            name: user.name ?? '',
            email: user.email ?? '',
            role: user.role ?? 'user',
            branch_id: user.branch_id ? String(user.branch_id) : '',
        });
    };

    const applyFilters = (event) => {
        event.preventDefault();
        const next = new URLSearchParams();
        if (search.trim()) {
            next.set('search', search.trim());
        }
        if (role) {
            next.set('role', role);
        }
        setSearchParams(next);
    };

    const handleSave = async (event) => {
        event.preventDefault();

        if (!editing) {
            return;
        }

        if (form.role === 'manager' && !form.branch_id) {
            setFlash({ type: 'error', message: 'A branch must be assigned to a Manager.' });
            return;
        }

        const confirmed = window.confirm(
            `Change ${editing.name}'s role to ${roleOptions.find((option) => option.value === form.role)?.label ?? form.role}?`,
        );

        if (!confirmed) {
            return;
        }

        setSaving(true);
        setFlash(null);

        try {
            const response = await updateAdminUser(editing.id, {
                name: form.name,
                email: form.email,
                role: form.role,
                branch_id: form.role === 'manager' ? Number(form.branch_id) : null,
            });
            setFlash({ type: 'success', message: response.message ?? 'User updated successfully.' });
            setEditing(null);
            await load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const branchOptions = [
        { value: '', label: 'Select branch' },
        ...branches.map((branch) => ({
            value: String(branch.id),
            label: branch.name,
        })),
    ];

    return (
        <div>
            <AdminPageHeader
                title="Users"
                description="Manage customer, manager, and Super Admin accounts. Passwords cannot be edited here."
            />

            {flash ? (
                <div className="mb-4">
                    <FlashMessage type={flash.type} message={flash.message} />
                </div>
            ) : null}
            {error ? (
                <div className="mb-4">
                    <FlashMessage type="error" message={error.message} />
                </div>
            ) : null}

            <div className="mb-4 flex flex-wrap gap-2">
                {roleTabs.map((tab) => (
                    <button
                        key={tab.value || 'all'}
                        type="button"
                        onClick={() => {
                            const next = new URLSearchParams(searchParams);
                            if (tab.value) {
                                next.set('role', tab.value);
                            } else {
                                next.delete('role');
                            }
                            next.delete('page');
                            setSearchParams(next);
                        }}
                        className={[
                            'rounded-full px-3 py-1.5 text-sm font-medium transition',
                            role === tab.value
                                ? 'bg-stone-900 text-white'
                                : 'bg-white text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50',
                        ].join(' ')}
                    >
                        {tab.label}
                        {tab.value === 'user' && payload.stats?.users != null ? ` (${payload.stats.users})` : ''}
                        {tab.value === 'manager' && payload.stats?.managers != null ? ` (${payload.stats.managers})` : ''}
                        {tab.value === 'super_admin' && payload.stats?.super_admins != null
                            ? ` (${payload.stats.super_admins})`
                            : ''}
                    </button>
                ))}
            </div>

            <AdminPanel className="mb-4">
                <AdminPanelBody>
                    <form onSubmit={applyFilters} className="flex flex-col gap-3 sm:flex-row">
                        <div className="flex-1">
                            <AdminFormField
                                label="Search"
                                name="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Name or email"
                            />
                        </div>
                        <div className="flex items-end">
                            <button
                                type="submit"
                                className="rounded-md bg-stone-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-stone-800"
                            >
                                Search
                            </button>
                        </div>
                    </form>
                </AdminPanelBody>
            </AdminPanel>

            <AdminPanel>
                <AdminPanelBody className="overflow-x-auto p-0 sm:p-0">
                    {loading ? (
                        <p className="px-4 py-10 text-center text-sm text-stone-500">Loading users…</p>
                    ) : payload.data.length === 0 ? (
                        <p className="px-4 py-10 text-center text-sm text-stone-500">No users found.</p>
                    ) : (
                        <table className="admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Branch</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payload.data.map((user) => (
                                    <tr key={user.id}>
                                        <td className="font-medium text-stone-900">{user.name}</td>
                                        <td>{user.email}</td>
                                        <td>{user.role_label ?? user.role}</td>
                                        <td>{user.branch?.name ?? '—'}</td>
                                        <td>{user.created_at ? new Date(user.created_at).toLocaleDateString() : '—'}</td>
                                        <td>
                                            <button
                                                type="button"
                                                className="text-sm font-medium text-amber-700 hover:text-amber-800"
                                                onClick={() => openEdit(user)}
                                            >
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </AdminPanelBody>
            </AdminPanel>

            {payload.meta ? (
                <div className="mt-4">
                    <AdminPagination
                        meta={payload.meta}
                        links={payload.links}
                        onPageChange={(page) => {
                            const next = new URLSearchParams(searchParams);
                            next.set('page', String(page));
                            setSearchParams(next);
                        }}
                    />
                </div>
            ) : null}

            {editing ? (
                <div className="admin-modal-backdrop">
                    <div className="admin-modal-panel max-w-lg">
                        <h2 className="admin-page-title text-lg">Edit user</h2>
                        <p className="admin-page-description">Passwords cannot be changed from this screen.</p>

                        <form onSubmit={handleSave} className="mt-4 space-y-3">
                            <AdminFormField
                                label="Name"
                                name="name"
                                value={form.name}
                                onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))}
                                required
                            />
                            <AdminFormField
                                label="Email"
                                name="email"
                                type="email"
                                value={form.email}
                                onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))}
                                required
                            />
                            <AdminSelectField
                                label="Role"
                                name="role"
                                value={form.role}
                                onChange={(event) =>
                                    setForm((current) => ({
                                        ...current,
                                        role: event.target.value,
                                        branch_id: event.target.value === 'manager' ? current.branch_id : '',
                                    }))
                                }
                                options={roleOptions}
                            />
                            {form.role === 'manager' ? (
                                <AdminSelectField
                                    label="Branch"
                                    name="branch_id"
                                    value={form.branch_id}
                                    onChange={(event) => setForm((current) => ({ ...current, branch_id: event.target.value }))}
                                    options={branchOptions}
                                    required
                                />
                            ) : null}

                            <div className="flex justify-end gap-2 pt-2">
                                <AdminButton
                                    type="button"
                                    variant="secondary"
                                    onClick={() => setEditing(null)}
                                    disabled={saving}
                                >
                                    Cancel
                                </AdminButton>
                                <AdminButton type="submit" variant="primary" disabled={saving}>
                                    {saving ? 'Saving…' : 'Save'}
                                </AdminButton>
                            </div>
                        </form>
                    </div>
                </div>
            ) : null}
        </div>
    );
}

import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { fetchAdminBranches } from '../../api/admin/branches';
import {
    createAdminDeliveryZone,
    fetchAdminDeliveryZone,
    updateAdminDeliveryZone,
} from '../../api/admin/deliveryZones';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminCheckboxField, AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const emptyForm = {
    branch_id: '',
    name: '',
    delivery_fee: '',
    minimum_order_amount: '',
    estimated_minutes: '',
    is_active: true,
    sort_order: 0,
};

export default function DeliveryZoneFormPage() {
    const { id } = useParams();
    const isEdit = Boolean(id && id !== 'new');
    const navigate = useNavigate();

    const [form, setForm] = useState(emptyForm);
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(isEdit);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});
    const [flash, setFlash] = useState(null);

    useEffect(() => {
        fetchAdminBranches()
            .then((response) => setBranches(response.data ?? []))
            .catch(() => setBranches([]));
    }, []);

    useEffect(() => {
        if (!isEdit) {
            return;
        }

        const load = async () => {
            setLoading(true);
            setError(null);

            try {
                const zone = await fetchAdminDeliveryZone(id);
                setForm({
                    branch_id: String(zone.branch_id ?? ''),
                    name: zone.name ?? '',
                    delivery_fee: zone.delivery_fee ?? '',
                    minimum_order_amount: zone.minimum_order_amount ?? '',
                    estimated_minutes: zone.estimated_minutes ?? '',
                    is_active: Boolean(zone.is_active),
                    sort_order: zone.sort_order ?? 0,
                });
            } catch (err) {
                setError(normalizeApiError(err));
            } finally {
                setLoading(false);
            }
        };

        load();
    }, [id, isEdit]);

    const updateField = (name, value) => {
        setForm((current) => ({ ...current, [name]: value }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setFlash(null);
        setFieldErrors({});

        const payload = {
            ...form,
            branch_id: Number(form.branch_id),
            delivery_fee: Number(form.delivery_fee),
            minimum_order_amount: form.minimum_order_amount === '' ? null : Number(form.minimum_order_amount),
            estimated_minutes: form.estimated_minutes === '' ? null : Number(form.estimated_minutes),
            sort_order: Number(form.sort_order),
        };

        try {
            if (isEdit) {
                await updateAdminDeliveryZone(id, payload);
                setFlash({ type: 'success', message: 'Delivery zone updated.' });
            } else {
                const created = await createAdminDeliveryZone(payload);
                navigate(`/admin/delivery-zones/${created.id}/edit`, { replace: true });
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
        return <LoadingState label="Loading delivery zone…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit delivery zone' : 'New delivery zone'}
                actions={
                    <Link to="/admin/delivery-zones" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to delivery zones
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit}>
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminSelectField
                            label="Branch"
                            name="branch_id"
                            required
                            value={form.branch_id}
                            onChange={(event) => updateField('branch_id', event.target.value)}
                            errors={fieldErrors}
                            options={[
                                { value: '', label: 'Select branch' },
                                ...branches.map((branch) => ({ value: String(branch.id), label: branch.name })),
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
                            label="Delivery fee"
                            name="delivery_fee"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            value={form.delivery_fee}
                            onChange={(event) => updateField('delivery_fee', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Minimum order amount"
                            name="minimum_order_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            value={form.minimum_order_amount}
                            onChange={(event) => updateField('minimum_order_amount', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Estimated minutes"
                            name="estimated_minutes"
                            type="number"
                            min="1"
                            value={form.estimated_minutes}
                            onChange={(event) => updateField('estimated_minutes', event.target.value)}
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
                    {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Create zone'}
                </button>
            </form>
        </div>
    );
}

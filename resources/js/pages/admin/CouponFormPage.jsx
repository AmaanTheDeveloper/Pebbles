import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { fetchAdminBranches } from '../../api/admin/branches';
import { createAdminCoupon, fetchAdminCoupon, updateAdminCoupon } from '../../api/admin/coupons';
import FlashMessage from '../../components/admin/FlashMessage';
import {
    AdminCheckboxField,
    AdminFormField,
    AdminSelectField,
} from '../../components/admin/AdminFormField';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { firstFieldError, normalizeApiError } from '../../lib/errors';

const typeOptions = [
    { value: 'fixed', label: 'Fixed Amount' },
    { value: 'percentage', label: 'Percentage' },
];

const emptyForm = {
    code: '',
    discount_type: 'fixed',
    discount_value: '',
    minimum_order_amount: '',
    maximum_discount_amount: '',
    usage_limit: '',
    per_customer_limit: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
    branch_id: '',
};

function toDatetimeLocal(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function toApiDate(value) {
    if (!value) {
        return null;
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date.toISOString();
}

function nullableNumber(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    return Number(value);
}

export default function CouponFormPage() {
    const { id } = useParams();
    const isEdit = Boolean(id && id !== 'new');
    const navigate = useNavigate();

    const [form, setForm] = useState(emptyForm);
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});
    const [flash, setFlash] = useState(null);

    useEffect(() => {
        const load = async () => {
            setLoading(true);
            setError(null);

            try {
                const branchResponse = await fetchAdminBranches({ per_page: 100 });
                setBranches(branchResponse.data ?? []);

                if (isEdit) {
                    const coupon = await fetchAdminCoupon(id);
                    setForm({
                        code: coupon.code ?? '',
                        discount_type: coupon.discount_type ?? 'fixed',
                        discount_value: coupon.discount_value ?? '',
                        minimum_order_amount: coupon.minimum_order_amount ?? '',
                        maximum_discount_amount: coupon.maximum_discount_amount ?? '',
                        usage_limit: coupon.usage_limit ?? '',
                        per_customer_limit: coupon.per_customer_limit ?? '',
                        starts_at: toDatetimeLocal(coupon.starts_at),
                        expires_at: toDatetimeLocal(coupon.expires_at),
                        is_active: Boolean(coupon.is_active),
                        branch_id: coupon.branch_id ? String(coupon.branch_id) : '',
                    });
                }
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
            code: form.code,
            discount_type: form.discount_type,
            discount_value: nullableNumber(form.discount_value),
            minimum_order_amount: nullableNumber(form.minimum_order_amount),
            maximum_discount_amount:
                form.discount_type === 'percentage' ? nullableNumber(form.maximum_discount_amount) : null,
            usage_limit: nullableNumber(form.usage_limit),
            per_customer_limit: nullableNumber(form.per_customer_limit),
            starts_at: toApiDate(form.starts_at),
            expires_at: toApiDate(form.expires_at),
            is_active: form.is_active,
            branch_id: form.branch_id ? Number(form.branch_id) : null,
        };

        try {
            if (isEdit) {
                await updateAdminCoupon(id, payload);
                setFlash({ type: 'success', message: 'Coupon updated.' });
            } else {
                const created = await createAdminCoupon(payload);
                navigate(`/admin/coupons/${created.id}/edit`, { replace: true });
            }
        } catch (err) {
            const normalized = normalizeApiError(err);
            setFieldErrors(normalized.errors ?? {});
            setFlash({ type: 'error', message: firstFieldError(normalized.errors, 'code') || normalized.message });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading coupon…" />;
    }

    if (error) {
        return <ErrorState message={error.message} />;
    }

    const branchOptions = [
        { value: '', label: 'All Branches' },
        ...branches.map((branch) => ({ value: String(branch.id), label: branch.name })),
    ];

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={isEdit ? 'Edit coupon' : 'Create coupon'}
                actions={
                    <Link to="/admin/coupons" className="text-sm font-medium text-amber-900 hover:underline">
                        Back to coupons
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <form onSubmit={handleSubmit}>
                <AdminPanel>
                    <AdminPanelBody className="grid gap-4 sm:grid-cols-2">
                        <AdminFormField
                            label="Coupon Code"
                            name="code"
                            required
                            value={form.code}
                            onChange={(event) => updateField('code', event.target.value.toUpperCase())}
                            hint="Stored uppercase. Customers can enter any casing."
                            errors={fieldErrors}
                        />
                        <AdminSelectField
                            label="Discount Type"
                            name="discount_type"
                            required
                            value={form.discount_type}
                            onChange={(event) => updateField('discount_type', event.target.value)}
                            options={typeOptions}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Discount Value"
                            name="discount_value"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            value={form.discount_value}
                            onChange={(event) => updateField('discount_value', event.target.value)}
                            hint={form.discount_type === 'percentage' ? 'Percentage from 0.01 to 100.' : 'Fixed amount in Rs.'}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Minimum Order Amount"
                            name="minimum_order_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            value={form.minimum_order_amount}
                            onChange={(event) => updateField('minimum_order_amount', event.target.value)}
                            errors={fieldErrors}
                        />
                        {form.discount_type === 'percentage' ? (
                            <AdminFormField
                                label="Maximum Discount"
                                name="maximum_discount_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.maximum_discount_amount}
                                onChange={(event) => updateField('maximum_discount_amount', event.target.value)}
                                hint="Optional cap for percentage coupons."
                                errors={fieldErrors}
                            />
                        ) : null}
                        <AdminFormField
                            label="Total Usage Limit"
                            name="usage_limit"
                            type="number"
                            min="1"
                            step="1"
                            value={form.usage_limit}
                            onChange={(event) => updateField('usage_limit', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Per Customer Usage Limit"
                            name="per_customer_limit"
                            type="number"
                            min="1"
                            step="1"
                            value={form.per_customer_limit}
                            onChange={(event) => updateField('per_customer_limit', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="Start Date/Time"
                            name="starts_at"
                            type="datetime-local"
                            value={form.starts_at}
                            onChange={(event) => updateField('starts_at', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminFormField
                            label="End Date/Time"
                            name="expires_at"
                            type="datetime-local"
                            value={form.expires_at}
                            onChange={(event) => updateField('expires_at', event.target.value)}
                            errors={fieldErrors}
                        />
                        <AdminSelectField
                            label="Branch"
                            name="branch_id"
                            value={form.branch_id}
                            onChange={(event) => updateField('branch_id', event.target.value)}
                            options={branchOptions}
                            errors={fieldErrors}
                        />
                        <AdminCheckboxField
                            label="Active"
                            name="is_active"
                            checked={form.is_active}
                            onChange={(event) => updateField('is_active', event.target.checked)}
                        />
                    </AdminPanelBody>
                    <div className="flex justify-end border-t border-stone-200 px-4 py-3">
                        <button
                            type="submit"
                            disabled={saving}
                            className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                        >
                            {saving ? 'Saving…' : isEdit ? 'Save changes' : 'Create coupon'}
                        </button>
                    </div>
                </AdminPanel>
            </form>
        </div>
    );
}

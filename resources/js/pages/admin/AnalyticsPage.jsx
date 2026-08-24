import { useEffect, useMemo, useState } from 'react';
import { fetchAdminBranches } from '../../api/admin/branches';
import {
    exportAnalytics,
    fetchAnalyticsBranches,
    fetchAnalyticsCustomers,
    fetchAnalyticsDeliveryZones,
    fetchAnalyticsOrders,
    fetchAnalyticsOverview,
    fetchAnalyticsPaymentMethods,
    fetchAnalyticsProducts,
    fetchAnalyticsSales,
} from '../../api/admin/analytics';
import AdminDataTable from '../../components/admin/AdminDataTable';
import AnalyticsBarChart from '../../components/admin/AnalyticsBarChart';
import AnalyticsFiltersBar from '../../components/admin/AnalyticsFiltersBar';
import AnalyticsTrendChart from '../../components/admin/AnalyticsTrendChart';
import FlashMessage from '../../components/admin/FlashMessage';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminButton, AdminKpiCard, AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { formatDate, formatMoney, humanizeStatus } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';

const defaultFilters = {
    preset: 'last_30_days',
    from: '',
    to: '',
    branch_id: '',
    type: '',
    status: '',
    payment_status: '',
    payment_method: '',
};

function buildParams(filters) {
    return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value != null));
}

function ComparisonDelta({ comparison }) {
    if (!comparison) {
        return null;
    }

    const { direction, label } = comparison;
    const tone =
        direction === 'up'
            ? 'text-emerald-700'
            : direction === 'down'
              ? 'text-red-700'
              : direction === 'new'
                ? 'text-amber-700'
                : 'text-stone-500';
    const prefix = direction === 'up' ? '↑' : direction === 'down' ? '↓' : direction === 'new' ? '★' : '—';

    return (
        <p className={`mt-1 text-xs font-medium ${tone}`}>
            {prefix} {direction === 'new' ? 'New vs prior period' : `${label} vs prior period`}
        </p>
    );
}

function KpiCard({ label, value, comparison, accent = false }) {
    return (
        <AdminKpiCard
            label={label}
            value={value}
            accent={accent}
            footer={<ComparisonDelta comparison={comparison} />}
        />
    );
}

function SectionHeading({ title, description }) {
    return (
        <div className="mb-4">
            <h2 className="admin-panel-title">{title}</h2>
            {description ? <p className="admin-panel-description">{description}</p> : null}
        </div>
    );
}

function InlineStatGrid({ items }) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((item) => (
                <div key={item.label} className="admin-inline-stat">
                    <p className="text-xs text-brand-muted">{item.label}</p>
                    <p className="mt-0.5 text-sm font-semibold text-brand-ink">{item.value}</p>
                </div>
            ))}
        </div>
    );
}

export default function AnalyticsPage() {
    const [draftFilters, setDraftFilters] = useState(defaultFilters);
    const [appliedFilters, setAppliedFilters] = useState(defaultFilters);
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [exportMessage, setExportMessage] = useState(null);
    const [exportError, setExportError] = useState(null);
    const [exporting, setExporting] = useState(null);
    const [data, setData] = useState({
        overview: null,
        sales: null,
        orders: null,
        products: null,
        branches: null,
        customers: null,
        paymentMethods: null,
        deliveryZones: null,
    });

    const queryParams = useMemo(() => buildParams(appliedFilters), [appliedFilters]);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const [overview, sales, orders, products, branchStats, customers, paymentMethods, deliveryZones] =
                await Promise.all([
                    fetchAnalyticsOverview(queryParams),
                    fetchAnalyticsSales(queryParams),
                    fetchAnalyticsOrders(queryParams),
                    fetchAnalyticsProducts(queryParams),
                    fetchAnalyticsBranches(queryParams),
                    fetchAnalyticsCustomers(queryParams),
                    fetchAnalyticsPaymentMethods(queryParams),
                    fetchAnalyticsDeliveryZones(queryParams),
                ]);

            setData({
                overview,
                sales,
                orders,
                products,
                branches: branchStats,
                customers,
                paymentMethods,
                deliveryZones,
            });
        } catch (err) {
            setError(normalizeApiError(err));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchAdminBranches({ page: 1 })
            .then((response) => setBranches(response.data ?? []))
            .catch(() => setBranches([]));
    }, []);

    useEffect(() => {
        load();
    }, [appliedFilters]);

    const handleApply = () => {
        setAppliedFilters({ ...draftFilters });
    };

    const handleExport = async (report) => {
        setExporting(report);
        setExportMessage(null);
        setExportError(null);

        try {
            await exportAnalytics({ ...queryParams, report });
            setExportMessage(`${humanizeStatus(report)} CSV downloaded.`);
        } catch (err) {
            setExportError(normalizeApiError(err).message);
        } finally {
            setExporting(null);
        }
    };

    const { overview, sales, orders, products, branches: branchStats, customers, paymentMethods, deliveryZones } = data;
    const kpis = overview?.kpis ?? {};
    const comparison = overview?.comparison ?? {};
    const periodLabel = overview?.filters
        ? `${overview.filters.from} – ${overview.filters.to}`
        : 'Selected period';

    const orderStatusItems =
        overview?.order_status_distribution
            ?.filter((row) => row.count > 0)
            .map((row) => ({
                label: humanizeStatus(row.status),
                value: row.count,
            })) ?? [];

    const paymentStatusItems =
        overview?.payment_status_distribution
            ?.filter((row) => row.count > 0)
            .map((row) => ({
                label: humanizeStatus(row.status),
                value: row.count,
            })) ?? [];

    const peakHourItems =
        orders?.peak_hours?.map((row) => ({
            label: row.label,
            value: row.orders,
        })) ?? [];

    const dayOfWeekItems =
        orders?.day_of_week?.map((row) => ({
            label: row.day.slice(0, 3),
            value: row.orders,
        })) ?? [];

    const paymentMethodItems =
        paymentMethods?.methods?.map((row) => ({
            label: humanizeStatus(row.method),
            value: Number(row.revenue),
        })) ?? [];

    const categoryItems =
        products?.categories?.map((row) => ({
            label: row.category,
            value: Number(row.revenue),
        })) ?? [];

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Analytics"
                description="Revenue, orders, products, and customer insights."
                actions={
                    <div className="flex flex-wrap gap-2">
                        <AdminButton
                            type="button"
                            variant="secondary"
                            disabled={exporting != null}
                            onClick={() => handleExport('orders')}
                        >
                            {exporting === 'orders' ? 'Exporting…' : 'Export orders'}
                        </AdminButton>
                        <AdminButton
                            type="button"
                            variant="secondary"
                            disabled={exporting != null}
                            onClick={() => handleExport('products')}
                        >
                            {exporting === 'products' ? 'Exporting…' : 'Export products'}
                        </AdminButton>
                        <AdminButton
                            type="button"
                            variant="secondary"
                            disabled={exporting != null}
                            onClick={() => handleExport('branches')}
                        >
                            {exporting === 'branches' ? 'Exporting…' : 'Export branches'}
                        </AdminButton>
                        <AdminButton
                            type="button"
                            variant="secondary"
                            disabled={exporting != null}
                            onClick={() => handleExport('payments')}
                        >
                            {exporting === 'payments' ? 'Exporting…' : 'Export payments'}
                        </AdminButton>
                    </div>
                }
            />

            <AdminPanel>
                <AdminPanelBody>
                    <AnalyticsFiltersBar
                        value={draftFilters}
                        onChange={setDraftFilters}
                        branches={branches}
                        onApply={handleApply}
                        applying={loading}
                    />
                </AdminPanelBody>
            </AdminPanel>

            {exportMessage ? (
                <FlashMessage type="success" message={exportMessage} onDismiss={() => setExportMessage(null)} />
            ) : null}
            {exportError ? (
                <FlashMessage type="error" message={exportError} onDismiss={() => setExportError(null)} />
            ) : null}

            {loading && !overview ? <LoadingState label="Loading analytics…" /> : null}
            {error && !overview ? <ErrorState message={error.message} onRetry={load} /> : null}

            {overview ? (
                <>
                    <p className="text-sm text-stone-600">Showing data for {periodLabel}.</p>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <KpiCard
                            label="Net revenue"
                            value={formatMoney(kpis.net_revenue)}
                            comparison={comparison.revenue}
                            accent
                        />
                        <KpiCard label="Total orders" value={kpis.total_orders ?? 0} comparison={comparison.orders} />
                        <KpiCard
                            label="Average order value"
                            value={formatMoney(kpis.average_order_value)}
                            comparison={comparison.aov}
                        />
                        <KpiCard label="Completed orders" value={kpis.completed_orders ?? 0} />
                        <KpiCard label="Cancelled orders" value={kpis.cancelled_orders ?? 0} />
                        <KpiCard label="Gross revenue" value={formatMoney(kpis.gross_revenue)} />
                        <KpiCard label="Payment success rate" value={`${kpis.payment_success_rate ?? 0}%`} />
                        <KpiCard label="Cancellation rate" value={`${kpis.cancellation_rate ?? 0}%`} />
                    </div>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Sales trend" description="Revenue line with order volume bars." />
                                <AnalyticsTrendChart series={sales?.series ?? []} grain={sales?.grain} />
                            </AdminPanelBody>
                        </AdminPanel>

                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Order status distribution" />
                                <AnalyticsBarChart items={orderStatusItems} emptyMessage="No orders in this period." />
                            </AdminPanelBody>
                        </AdminPanel>

                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Payment overview" description="Payment status counts." />
                                <AnalyticsBarChart
                                    items={paymentStatusItems}
                                    emptyMessage="No payment activity in this period."
                                    barClassName="fill-emerald-700"
                                />
                                <div className="mt-4 border-t border-stone-200 pt-4">
                                    <InlineStatGrid
                                        items={[
                                            { label: 'Successful payments', value: kpis.successful_payments ?? 0 },
                                            { label: 'Rejected payments', value: kpis.rejected_payments ?? 0 },
                                            { label: 'Pending payments', value: kpis.pending_payments ?? 0 },
                                            { label: 'Failed payments', value: kpis.failed_payments ?? 0 },
                                            {
                                                label: 'Payment rejection rate',
                                                value: `${kpis.payment_rejection_rate ?? 0}%`,
                                            },
                                            {
                                                label: 'Cancellation revenue',
                                                value: formatMoney(kpis.cancellation_revenue),
                                            },
                                        ]}
                                    />
                                </div>
                            </AdminPanelBody>
                        </AdminPanel>

                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Payment methods" />
                                <AnalyticsBarChart
                                    items={paymentMethodItems}
                                    valueFormatter={(value) => formatMoney(value)}
                                    emptyMessage="No payment method data."
                                />
                            </AdminPanelBody>
                        </AdminPanel>

                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Peak hours" description="Orders by hour of day." />
                                <AnalyticsBarChart
                                    items={peakHourItems}
                                    orientation="vertical"
                                    emptyMessage="No hourly order data."
                                />
                            </AdminPanelBody>
                        </AdminPanel>

                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Day of week" />
                                <AnalyticsBarChart
                                    items={dayOfWeekItems}
                                    orientation="vertical"
                                    emptyMessage="No day-of-week data."
                                />
                            </AdminPanelBody>
                        </AdminPanel>
                    </div>

                    <AdminPanel>
                        <AdminPanelBody>
                            <SectionHeading title="Order type breakdown" />
                            <AdminDataTable
                                columns={[
                                    { key: 'type', label: 'Type', render: (row) => humanizeStatus(row.type) },
                                    { key: 'orders', label: 'Orders' },
                                    {
                                        key: 'revenue',
                                        label: 'Revenue',
                                        render: (row) => formatMoney(row.revenue),
                                    },
                                    {
                                        key: 'average_order_value',
                                        label: 'AOV',
                                        render: (row) => formatMoney(row.average_order_value),
                                    },
                                    {
                                        key: 'percentage',
                                        label: 'Share',
                                        render: (row) => `${row.percentage}%`,
                                    },
                                ]}
                                rows={orders?.by_type ?? []}
                                rowKey="type"
                                emptyTitle="No order types"
                                emptyDescription="No orders match the current filters."
                            />
                        </AdminPanelBody>
                    </AdminPanel>

                    <AdminPanel>
                        <AdminPanelBody>
                            <SectionHeading title="Top products" />
                            <AdminDataTable
                                columns={[
                                    { key: 'product', label: 'Product' },
                                    { key: 'units_sold', label: 'Units sold' },
                                    { key: 'orders_containing', label: 'Orders' },
                                    {
                                        key: 'revenue',
                                        label: 'Revenue',
                                        render: (row) => formatMoney(row.revenue),
                                    },
                                    {
                                        key: 'average_selling_price',
                                        label: 'Avg price',
                                        render: (row) => formatMoney(row.average_selling_price),
                                    },
                                ]}
                                rows={products?.products ?? []}
                                rowKey="product_id"
                                emptyTitle="No product sales"
                                emptyDescription="No product line items in this period."
                            />
                        </AdminPanelBody>
                    </AdminPanel>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Categories" />
                                <AnalyticsBarChart
                                    items={categoryItems}
                                    valueFormatter={(value) => formatMoney(value)}
                                    emptyMessage="No category revenue."
                                    maxBars={10}
                                />
                                <div className="mt-4">
                                    <AdminDataTable
                                        columns={[
                                            { key: 'category', label: 'Category' },
                                            { key: 'units_sold', label: 'Units' },
                                            {
                                                key: 'revenue',
                                                label: 'Revenue',
                                                render: (row) => formatMoney(row.revenue),
                                            },
                                            {
                                                key: 'percentage_of_revenue',
                                                label: 'Share',
                                                render: (row) => `${row.percentage_of_revenue}%`,
                                            },
                                        ]}
                                        rows={products?.categories ?? []}
                                        rowKey="category"
                                        emptyTitle="No categories"
                                        emptyDescription="No categorized product sales."
                                    />
                                </div>
                            </AdminPanelBody>
                        </AdminPanel>

                        <AdminPanel>
                            <AdminPanelBody>
                                <SectionHeading title="Add-ons" />
                                <AdminDataTable
                                    columns={[
                                        { key: 'add_on', label: 'Add-on' },
                                        { key: 'times_selected', label: 'Times selected' },
                                        {
                                            key: 'revenue_contribution',
                                            label: 'Revenue',
                                            render: (row) => formatMoney(row.revenue_contribution),
                                        },
                                    ]}
                                    rows={products?.add_ons ?? []}
                                    rowKey="add_on"
                                    emptyTitle="No add-on usage"
                                    emptyDescription="No add-ons were selected in this period."
                                />
                            </AdminPanelBody>
                        </AdminPanel>
                    </div>

                    <AdminPanel>
                        <AdminPanelBody>
                            <SectionHeading title="Variants" />
                            <AdminDataTable
                                columns={[
                                    { key: 'product', label: 'Product' },
                                    { key: 'variant', label: 'Variant' },
                                    { key: 'units_sold', label: 'Units sold' },
                                    {
                                        key: 'revenue',
                                        label: 'Revenue',
                                        render: (row) => formatMoney(row.revenue),
                                    },
                                ]}
                                rows={products?.variants ?? []}
                                rowKey="variant"
                                emptyTitle="No variant sales"
                                emptyDescription="No variant line items in this period."
                            />
                        </AdminPanelBody>
                    </AdminPanel>

                    <AdminPanel>
                        <AdminPanelBody>
                            <SectionHeading title="Branches" />
                            <AdminDataTable
                                columns={[
                                    { key: 'branch', label: 'Branch' },
                                    { key: 'orders', label: 'Orders' },
                                    {
                                        key: 'revenue',
                                        label: 'Revenue',
                                        render: (row) => formatMoney(row.revenue),
                                    },
                                    {
                                        key: 'average_order_value',
                                        label: 'AOV',
                                        render: (row) => formatMoney(row.average_order_value),
                                    },
                                    { key: 'completed_orders', label: 'Completed' },
                                    { key: 'cancelled_orders', label: 'Cancelled' },
                                    {
                                        key: 'payment_success_rate',
                                        label: 'Payment success',
                                        render: (row) => `${row.payment_success_rate}%`,
                                    },
                                ]}
                                rows={branchStats?.branches ?? []}
                                rowKey="branch_id"
                                emptyTitle="No branch data"
                                emptyDescription="No branch-level orders in this period."
                            />
                        </AdminPanelBody>
                    </AdminPanel>

                    <AdminPanel>
                        <AdminPanelBody>
                            <SectionHeading
                                title="Delivery zones"
                                description={deliveryZones?.note}
                            />
                            <AdminDataTable
                                columns={[
                                    { key: 'zone', label: 'Zone' },
                                    { key: 'orders', label: 'Orders' },
                                    {
                                        key: 'revenue',
                                        label: 'Revenue',
                                        render: (row) => formatMoney(row.revenue),
                                    },
                                    {
                                        key: 'delivery_fees',
                                        label: 'Delivery fees',
                                        render: (row) => formatMoney(row.delivery_fees),
                                    },
                                    {
                                        key: 'average_order_value',
                                        label: 'AOV',
                                        render: (row) => formatMoney(row.average_order_value),
                                    },
                                ]}
                                rows={deliveryZones?.zones ?? []}
                                rowKey="delivery_zone_id"
                                emptyTitle="No delivery zone data"
                                emptyDescription="No delivery orders in this period."
                            />
                        </AdminPanelBody>
                    </AdminPanel>

                    <AdminPanel>
                        <AdminPanelBody>
                            <SectionHeading title="Customers" />
                            <InlineStatGrid
                                items={[
                                    {
                                        label: 'Total customers',
                                        value: customers?.kpis?.total_customers ?? 0,
                                    },
                                    {
                                        label: 'Customers with orders',
                                        value: customers?.kpis?.customers_with_orders ?? 0,
                                    },
                                    {
                                        label: 'New customers',
                                        value: customers?.kpis?.new_customers ?? 0,
                                    },
                                    {
                                        label: 'Returning customers',
                                        value: customers?.kpis?.returning_customers ?? 0,
                                    },
                                    {
                                        label: 'Avg orders per customer',
                                        value: customers?.kpis?.average_orders_per_customer ?? '0.00',
                                    },
                                    {
                                        label: 'Avg spend per customer',
                                        value: formatMoney(customers?.kpis?.average_spend_per_customer),
                                    },
                                    {
                                        label: 'New customer orders',
                                        value: customers?.kpis?.new_customer_orders ?? 0,
                                    },
                                    {
                                        label: 'Returning customer orders',
                                        value: customers?.kpis?.returning_customer_orders ?? 0,
                                    },
                                ]}
                            />
                            <div className="mt-6">
                                <h3 className="mb-3 text-sm font-semibold text-stone-900">Top customers</h3>
                                <AdminDataTable
                                    columns={[
                                        { key: 'customer', label: 'Customer' },
                                        { key: 'email', label: 'Email' },
                                        { key: 'orders', label: 'Orders' },
                                        {
                                            key: 'total_spend',
                                            label: 'Total spend',
                                            render: (row) => formatMoney(row.total_spend),
                                        },
                                        {
                                            key: 'average_order_value',
                                            label: 'AOV',
                                            render: (row) => formatMoney(row.average_order_value),
                                        },
                                        {
                                            key: 'last_order_at',
                                            label: 'Last order',
                                            render: (row) => formatDate(row.last_order_at),
                                        },
                                    ]}
                                    rows={customers?.top_customers ?? []}
                                    rowKey="email"
                                    emptyTitle="No customers"
                                    emptyDescription="No customer orders in this period."
                                />
                            </div>
                        </AdminPanelBody>
                    </AdminPanel>

                    <AdminPanel>
                        <AdminPanelBody>
                            <SectionHeading title="Cancellations" />
                            <InlineStatGrid
                                items={[
                                    {
                                        label: 'Cancelled orders',
                                        value: orders?.cancellations?.cancelled_orders ?? 0,
                                    },
                                    {
                                        label: 'Cancellation rate',
                                        value: `${orders?.cancellations?.cancellation_rate ?? 0}%`,
                                    },
                                    {
                                        label: 'Cancellation revenue',
                                        value: formatMoney(orders?.cancellations?.cancellation_revenue),
                                    },
                                ]}
                            />
                            {orders?.cancellations?.reasons_note ? (
                                <p className="mt-3 text-xs text-stone-500">{orders.cancellations.reasons_note}</p>
                            ) : null}
                            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                                <div>
                                    <h3 className="mb-3 text-sm font-semibold text-stone-900">By branch</h3>
                                    <AnalyticsBarChart
                                        items={
                                            orders?.cancellations?.by_branch?.map((row) => ({
                                                label: row.branch,
                                                value: row.count,
                                            })) ?? []
                                        }
                                        emptyMessage="No cancellations by branch."
                                    />
                                </div>
                                <div>
                                    <h3 className="mb-3 text-sm font-semibold text-stone-900">By type</h3>
                                    <AnalyticsBarChart
                                        items={
                                            orders?.cancellations?.by_type?.map((row) => ({
                                                label: humanizeStatus(row.type),
                                                value: row.count,
                                            })) ?? []
                                        }
                                        emptyMessage="No cancellations by type."
                                    />
                                </div>
                            </div>
                            <div className="mt-6">
                                <h3 className="mb-3 text-sm font-semibold text-stone-900">Reasons</h3>
                                <AdminDataTable
                                    columns={[
                                        { key: 'reason', label: 'Reason' },
                                        { key: 'count', label: 'Count' },
                                        {
                                            key: 'percentage',
                                            label: 'Share',
                                            render: (row) => `${row.percentage}%`,
                                        },
                                    ]}
                                    rows={orders?.cancellations?.reasons ?? []}
                                    rowKey="reason"
                                    emptyTitle="No cancellation reasons"
                                    emptyDescription={
                                        orders?.cancellations?.reasons_note ??
                                        'No cancellation reasons recorded.'
                                    }
                                />
                            </div>
                        </AdminPanelBody>
                    </AdminPanel>
                </>
            ) : null}
        </div>
    );
}

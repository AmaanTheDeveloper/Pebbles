import LoadingState from '../storefront/LoadingState';
import ErrorState from '../storefront/ErrorState';
import EmptyState from '../storefront/EmptyState';

export default function AdminDataTable({
    columns,
    rows,
    rowKey = 'id',
    loading = false,
    error = null,
    onRetry,
    emptyTitle = 'Nothing here yet',
    emptyDescription = 'No records match your filters.',
    pagination = null,
}) {
    if (loading) {
        return (
            <div className="admin-loading">
                <LoadingState label="Loading…" />
            </div>
        );
    }

    if (error) {
        return <ErrorState message={error.message || 'Unable to load data.'} onRetry={onRetry} />;
    }

    if (!rows?.length) {
        return (
            <div className="admin-empty-state">
                <EmptyState title={emptyTitle} description={emptyDescription} />
            </div>
        );
    }

    return (
        <div className="admin-table-wrap">
            <table className="admin-table">
                <thead>
                    <tr>
                        {columns.map((column) => (
                            <th key={column.key} scope="col" className={column.className}>
                                {column.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row[rowKey] ?? row.id ?? row.order_number}>
                            {columns.map((column) => (
                                <td key={column.key} className={column.className}>
                                    {column.render ? column.render(row) : row[column.key]}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
            {pagination}
        </div>
    );
}

export function AdminPagination({ meta, links, onPageChange }) {
    if (!meta || meta.last_page <= 1) {
        return null;
    }

    const current = meta.current_page;

    return (
        <div className="admin-pagination">
            <p className="admin-pagination-meta">
                Page {current} of {meta.last_page}
                {meta.total != null ? ` · ${meta.total} total` : ''}
            </p>
            <div className="admin-pagination-actions">
                <button
                    type="button"
                    disabled={!links?.prev}
                    onClick={() => onPageChange(current - 1)}
                    className="admin-btn admin-btn-secondary"
                >
                    Previous
                </button>
                <button
                    type="button"
                    disabled={!links?.next}
                    onClick={() => onPageChange(current + 1)}
                    className="admin-btn admin-btn-secondary"
                >
                    Next
                </button>
            </div>
        </div>
    );
}

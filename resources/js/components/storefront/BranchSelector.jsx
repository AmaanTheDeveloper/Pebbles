export default function BranchSelector({ branches, selectedBranch, onBranchChange, loading }) {
    if (loading) {
        return <p className="text-xs text-brand-muted">Loading branches…</p>;
    }

    if (!branches?.length) {
        return <p className="text-xs text-brand-muted">No branches available yet.</p>;
    }

    return (
        <label className="flex flex-col gap-1 text-xs text-brand-muted sm:flex-row sm:items-center sm:gap-2">
            <span className="shrink-0 font-semibold text-brand-green">Branch</span>
            <select
                className="w-full rounded-lg border border-brand-green/20 bg-white px-3 py-2 text-sm text-brand-ink focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/30 sm:max-w-xs"
                value={selectedBranch?.slug ?? ''}
                onChange={(event) => {
                    const next = branches.find((branch) => branch.slug === event.target.value) ?? null;
                    onBranchChange(next);
                }}
            >
                <option value="">Select a branch</option>
                {branches.map((branch) => (
                    <option key={branch.id} value={branch.slug}>
                        {branch.name}
                    </option>
                ))}
            </select>
        </label>
    );
}

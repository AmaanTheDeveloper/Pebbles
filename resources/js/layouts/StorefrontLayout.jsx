import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { fetchBranches } from '../api/catalog';
import { fetchBranding } from '../api/branding';
import Header from '../components/storefront/Header';
import StorefrontFooter from '../components/storefront/StorefrontFooter';
import { useCatalogQuery } from '../hooks/useCatalog';
import { readBootData } from '../lib/bootData';
import { readSelectedBranch, writeSelectedBranch } from '../lib/branchStorage';

export default function StorefrontLayout() {
    const location = useLocation();
    const isFullBleed = ['/', '/about', '/gallery', '/reviews', '/contact', '/locations'].includes(location.pathname);
    const [selectedBranch, setSelectedBranch] = useState(() => readSelectedBranch());
    const { data: branches, loading: branchesLoading } = useCatalogQuery(fetchBranches, []);
    const { data: branding } = useCatalogQuery(fetchBranding, []);
    const bootLogoUrl = readBootData()?.logoUrl ?? null;
    const logo = branding?.logo ?? (bootLogoUrl ? { url: bootLogoUrl } : null);

    useEffect(() => {
        if (!branches?.length || !selectedBranch) {
            return;
        }

        const stillExists = branches.some((branch) => branch.slug === selectedBranch.slug);

        if (!stillExists) {
            setSelectedBranch(null);
            writeSelectedBranch(null);
        }
    }, [branches, selectedBranch]);

    const handleBranchChange = (branch) => {
        setSelectedBranch(branch);
        writeSelectedBranch(branch);
    };

    const footerBranch = selectedBranch ?? branches?.[0] ?? null;

    return (
        <div className="storefront-shell flex min-h-screen flex-col">
            <Header
                branches={branches ?? []}
                selectedBranch={selectedBranch}
                onBranchChange={handleBranchChange}
                branchesLoading={branchesLoading}
                logo={logo}
            />
            <main className={isFullBleed ? 'flex-1' : 'mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-8'}>
                <Outlet context={{ selectedBranch, branches: branches ?? [], logo }} />
            </main>
            <StorefrontFooter branch={footerBranch} logo={logo} />
        </div>
    );
}

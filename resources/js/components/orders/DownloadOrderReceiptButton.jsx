import { useState } from 'react';
import { downloadOrderReceipt } from '../../api/orders';
import { normalizeApiError } from '../../lib/errors';

function triggerBrowserDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export default function DownloadOrderReceiptButton({
    orderNumber,
    variant = 'secondary',
    className = '',
    label = 'Download Receipt',
}) {
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);

    const handleClick = async () => {
        if (!orderNumber || busy) {
            return;
        }

        setBusy(true);
        setError(null);

        try {
            const { blob, filename } = await downloadOrderReceipt(orderNumber);
            triggerBrowserDownload(blob, filename);
        } catch (err) {
            setError(normalizeApiError(err).message || 'Unable to download receipt.');
        } finally {
            setBusy(false);
        }
    };

    const buttonClass =
        variant === 'admin'
            ? 'rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-800 hover:bg-stone-50 disabled:opacity-60'
            : variant === 'primary'
              ? 'btn-orange btn-shine inline-flex items-center justify-center gap-1 px-4 py-2.5 text-sm disabled:opacity-60'
              : 'rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 ring-1 ring-stone-300 transition hover:-translate-y-0.5 hover:bg-stone-50 hover:shadow-sm disabled:opacity-60';

    return (
        <div className={className}>
            <button type="button" onClick={handleClick} disabled={busy || !orderNumber} className={buttonClass}>
                {busy ? 'Generating…' : label}
            </button>
            {error ? <p className="mt-2 text-sm text-red-700">{error}</p> : null}
        </div>
    );
}

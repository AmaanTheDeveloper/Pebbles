import { useState } from 'react';

const DETAIL_LABELS = {
    business_name: 'Business name',
    till_id: 'Till ID',
    bank_name: 'Bank name',
    account_title: 'Account title',
    account_number: 'Account number',
    iban: 'IBAN',
    branch_code: 'Branch / bank code',
};

const COPYABLE_FIELDS = new Set(['till_id', 'account_number', 'iban']);

export default function PaymentMethodDetails({
    method,
    details = {},
    instructions,
    amountLabel,
    qrImage = null,
}) {
    const [copiedField, setCopiedField] = useState(null);

    if (!method) {
        return null;
    }

    const isJazzcash = method.id === 'jazzcash';
    const businessName = details?.business_name;
    const tillId = details?.till_id;

    const handleCopy = async (field, value) => {
        try {
            await navigator.clipboard.writeText(value);
            setCopiedField(field);
            window.setTimeout(() => setCopiedField(null), 2000);
        } catch {
            setCopiedField(null);
        }
    };

    if (isJazzcash) {
        return (
            <div className="space-y-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200">
                <p className="font-semibold">{method.label}</p>

                {amountLabel ? (
                    <p className="text-xs text-amber-900/80">
                        Order total: <span className="font-semibold">{amountLabel}</span>
                    </p>
                ) : null}

                {businessName ? (
                    <p className="text-base font-semibold tracking-wide text-amber-950">{businessName}</p>
                ) : null}

                {tillId ? (
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-amber-900/70">Till ID</p>
                        <div className="mt-0.5 flex flex-wrap items-center gap-2">
                            <span className="font-medium">{tillId}</span>
                            <button
                                type="button"
                                onClick={() => handleCopy('till_id', tillId)}
                                className="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-950 ring-1 ring-amber-300 hover:bg-amber-200"
                            >
                                {copiedField === 'till_id' ? 'Copied' : 'Copy'}
                            </button>
                        </div>
                    </div>
                ) : null}

                {qrImage?.url ? (
                    <div className="flex justify-center py-1">
                        <img
                            src={qrImage.url}
                            alt="JazzCash payment QR code"
                            className="max-h-72 w-full max-w-xs object-contain"
                        />
                    </div>
                ) : null}

                {tillId || qrImage?.url ? (
                    <p className="text-xs leading-relaxed text-amber-900/90">
                        Scan the QR code or use the Till ID to make your payment.
                    </p>
                ) : null}

                {instructions ? <p className="text-sm leading-relaxed">{instructions}</p> : null}

                {copiedField === 'till_id' ? (
                    <p className="text-xs font-medium text-amber-900">Till ID copied.</p>
                ) : null}
            </div>
        );
    }

    const entries = Object.entries(details ?? {}).filter(([, value]) => Boolean(value));

    return (
        <div className="space-y-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200">
            <p className="font-semibold">{method.label}</p>

            {amountLabel ? (
                <p className="text-xs text-amber-900/80">
                    Order total: <span className="font-semibold">{amountLabel}</span>
                </p>
            ) : null}

            {entries.length > 0 ? (
                <dl className="space-y-2">
                    {entries.map(([field, value]) => (
                        <div key={field}>
                            <dt className="text-xs font-medium uppercase tracking-wide text-amber-900/70">
                                {DETAIL_LABELS[field] ?? field}
                            </dt>
                            <dd className="mt-0.5 flex flex-wrap items-center gap-2">
                                <span className="font-medium break-all">{value}</span>
                                {COPYABLE_FIELDS.has(field) ? (
                                    <button
                                        type="button"
                                        onClick={() => handleCopy(field, value)}
                                        className="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-950 ring-1 ring-amber-300 hover:bg-amber-200"
                                    >
                                        {copiedField === field ? 'Copied' : 'Copy'}
                                    </button>
                                ) : null}
                            </dd>
                        </div>
                    ))}
                </dl>
            ) : null}

            {instructions ? <p className="text-sm leading-relaxed">{instructions}</p> : null}

            {copiedField === 'account_number' ? (
                <p className="text-xs font-medium text-amber-900">Account number copied.</p>
            ) : null}
            {copiedField === 'iban' ? (
                <p className="text-xs font-medium text-amber-900">IBAN copied.</p>
            ) : null}
        </div>
    );
}

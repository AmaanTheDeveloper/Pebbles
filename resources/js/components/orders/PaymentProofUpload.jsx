import { useEffect, useMemo, useState } from 'react';

const ACCEPTED = '.jpg,.jpeg,.png,.webp,.pdf';

function isImageFile(file) {
    return file?.type?.startsWith('image/') ?? false;
}

export default function PaymentProofUpload({
    onUpload,
    disabled,
    error,
    compact = false,
    submitLabel = 'Submit proof',
    hideReference = false,
}) {
    const [file, setFile] = useState(null);
    const [reference, setReference] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [previewUrl, setPreviewUrl] = useState(null);

    useEffect(() => {
        if (!file || !isImageFile(file)) {
            setPreviewUrl(null);
            return undefined;
        }

        const url = URL.createObjectURL(file);
        setPreviewUrl(url);

        return () => {
            URL.revokeObjectURL(url);
        };
    }, [file]);

    const fileLabel = useMemo(() => {
        if (!file) {
            return null;
        }

        if (isImageFile(file)) {
            return 'Image selected';
        }

        return file.name;
    }, [file]);

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (!file || submitting || disabled) {
            return;
        }

        setSubmitting(true);

        try {
            await onUpload({
                file,
                transaction_reference: reference || null,
            });
            setFile(null);
            setReference('');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form
            onSubmit={handleSubmit}
            className={[
                'space-y-3 rounded-xl border border-dashed border-stone-300 p-4',
                compact ? 'bg-stone-50' : '',
            ].join(' ')}
        >
            <div>
                <p className="text-sm font-medium text-stone-900">Upload payment screenshot</p>
                <p className="text-xs text-stone-500">
                    Upload a clear screenshot showing your successful payment. JPG, PNG, WEBP, PDF · Max 5 MB
                </p>
            </div>
            <input
                type="file"
                accept={ACCEPTED}
                disabled={disabled || submitting}
                onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                className="block w-full text-sm text-stone-700 file:mr-3 file:rounded-md file:border-0 file:bg-stone-200 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-stone-800 hover:file:bg-stone-300"
            />
            {file && previewUrl ? (
                <div className="space-y-1">
                    <img
                        src={previewUrl}
                        alt="Payment proof preview"
                        className="max-h-40 rounded-lg border border-stone-200 object-contain"
                    />
                    <p className="text-xs text-stone-600">Preview — confirm this is the correct screenshot.</p>
                </div>
            ) : null}
            {file && !previewUrl ? (
                <p className="text-sm text-stone-700">
                    {fileLabel}: <span className="font-medium">{file.name}</span>
                </p>
            ) : null}
            {!hideReference ? (
                <input
                    type="text"
                    value={reference}
                    onChange={(event) => setReference(event.target.value)}
                    placeholder="Transaction reference (optional)"
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm"
                    disabled={disabled || submitting}
                />
            ) : null}
            {error ? <p className="text-sm text-red-700">{error}</p> : null}
            <button
                type="submit"
                disabled={!file || disabled || submitting}
                className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-50"
            >
                {submitting ? 'Uploading…' : submitLabel}
            </button>
        </form>
    );
}

export { ACCEPTED as PAYMENT_PROOF_ACCEPTED };

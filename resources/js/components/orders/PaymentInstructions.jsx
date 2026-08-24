import PaymentMethodDetails from './PaymentMethodDetails';

export default function PaymentInstructions({ method, details, instructions, requiresProof, amountLabel, qrImage }) {
    if (!instructions && !details && !method) {
        return null;
    }

    if (method || (details && Object.keys(details).length > 0)) {
        return (
            <PaymentMethodDetails
                method={method}
                details={details}
                instructions={instructions}
                amountLabel={amountLabel}
                qrImage={qrImage}
            />
        );
    }

    return (
        <div className="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200">
            <p className="font-medium">How to pay</p>
            <p className="mt-1">{instructions}</p>
            {requiresProof ? (
                <p className="mt-2 text-xs">
                    Uploading a receipt submits your payment for review. It does not mean payment is verified.
                </p>
            ) : null}
        </div>
    );
}

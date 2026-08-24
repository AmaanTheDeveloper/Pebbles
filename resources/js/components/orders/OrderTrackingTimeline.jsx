import {
    buildReachedStatuses,
    getStatusHeadline,
    getStatusMessage,
    getStepLabel,
    getStepState,
    getTrackingFlow,
} from '../../lib/orderTracking';
import OrderTrackingStepIcon from './OrderTrackingStepIcon';

export function OrderTrackingTimeline({
    currentStatus,
    fulfillmentType = 'takeaway',
    history = [],
    animateKey = currentStatus,
}) {
    if (currentStatus === 'cancelled') {
        return null;
    }

    const flow = getTrackingFlow(fulfillmentType);
    const reached = buildReachedStatuses(currentStatus, history);

    return (
        <div className="order-track-timeline rounded-[1.75rem] bg-white p-5 ring-1 ring-brand-green/10 sm:p-6" key={animateKey}>
            <h2 className="font-display text-xl font-semibold text-brand-ink">Order Progress</h2>

            <ol className="order-track-steps mt-6" aria-label="Order progress">
                {flow.map((status, index) => {
                    const state = getStepState(status, index, currentStatus, flow, reached);
                    const isLast = index === flow.length - 1;

                    return (
                        <li
                            key={status}
                            className={[
                                'order-track-step',
                                `is-${state}`,
                                status === 'out_for_delivery' && state === 'current' ? 'is-delivering-step' : '',
                            ].join(' ')}
                        >
                            <div className="order-track-step-body">
                                <OrderTrackingStepIcon status={status} state={state} />
                                <span className="order-track-step-label">{getStepLabel(status, fulfillmentType)}</span>
                            </div>
                            {!isLast ? <span className="order-track-line" aria-hidden="true" /> : null}
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

export function OrderTrackingStatusCard({ currentStatus, fulfillmentType = 'takeaway' }) {
    if (currentStatus === 'cancelled') {
        return (
            <div className="order-track-status-card is-cancelled rounded-[1.75rem] bg-white p-5 ring-1 ring-red-200 sm:p-6">
                <p className="text-xs font-semibold tracking-wide text-red-700 uppercase">Order cancelled</p>
                <h2 className="mt-2 font-display text-2xl font-semibold text-brand-ink sm:text-3xl">
                    This Order Was Cancelled
                </h2>
                <p className="mt-3 text-sm leading-relaxed text-brand-muted">
                    This order is no longer active. Contact Pebbles if you need help.
                </p>
            </div>
        );
    }

    return (
        <div
            className={[
                'order-track-status-card rounded-[1.75rem] bg-gradient-to-br from-brand-cream via-white to-brand-cream p-5 ring-1 ring-brand-green/10 sm:p-6',
                currentStatus === 'out_for_delivery' ? 'is-delivering' : '',
                currentStatus === 'ready' && fulfillmentType !== 'delivery' ? 'is-pickup-ready' : '',
            ]
                .filter(Boolean)
                .join(' ')}
        >
            <p className="text-xs font-semibold tracking-wide text-brand-orange uppercase">Current status</p>
            <h2 className="mt-2 font-display text-2xl font-semibold text-brand-ink sm:text-3xl">
                {getStatusHeadline(currentStatus, fulfillmentType)}
            </h2>
            <p className="mt-3 text-sm leading-relaxed text-brand-muted">
                {getStatusMessage(currentStatus, fulfillmentType)}
            </p>
        </div>
    );
}

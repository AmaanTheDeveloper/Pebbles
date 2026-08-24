import { formatDate, humanizeStatus } from '../../lib/format';
import { OrderTrackingStatusCard, OrderTrackingTimeline } from './OrderTrackingTimeline';

export default function OrderStatusTimeline({ currentStatus, history = [], fulfillmentType = 'takeaway' }) {
    if (currentStatus === 'cancelled') {
        return (
            <div className="space-y-4">
                <OrderTrackingStatusCard currentStatus={currentStatus} fulfillmentType={fulfillmentType} />
                <div className="rounded-[1.75rem] bg-white p-5 ring-1 ring-brand-green/10 sm:p-6">
                    <h2 className="font-display text-lg font-semibold text-brand-ink">Status history</h2>
                    <ul className="mt-4 space-y-2 text-sm text-brand-muted">
                        {history.map((entry, index) => (
                            <li key={`${entry.status}-${index}`}>
                                {humanizeStatus(entry.status)} · {formatDate(entry.created_at)}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        );
    }

    return (
        <OrderTrackingTimeline
            currentStatus={currentStatus}
            fulfillmentType={fulfillmentType}
            history={history}
        />
    );
}

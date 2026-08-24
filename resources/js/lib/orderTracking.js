export const TERMINAL_STATUSES = ['completed', 'cancelled'];

export function isFinalOrder(order) {
    return TERMINAL_STATUSES.includes(order?.status) || order?.lifecycle?.is_final === true;
}

export function isActiveOrder(order) {
    if (order?.lifecycle?.is_active != null) {
        return order.lifecycle.is_active;
    }

    return order?.status && !TERMINAL_STATUSES.includes(order.status);
}

export function getCustomerStatusLabel(order) {
    if (order?.lifecycle?.customer_status_label) {
        return order.lifecycle.customer_status_label;
    }

    if (order?.status === 'completed') {
        return order.fulfillment_type === 'delivery' ? 'Delivered' : 'Picked Up';
    }

    if (order?.status === 'cancelled') {
        return order.lifecycle?.cancellation_source === 'customer'
            ? 'Cancelled by Customer'
            : 'Cancelled by Admin';
    }

    return getStepLabel(order?.status, order?.fulfillment_type);
}

const DELIVERY_FLOW = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed'];

const PICKUP_FLOW = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];

export function getTrackingFlow(fulfillmentType) {
    return fulfillmentType === 'delivery' ? DELIVERY_FLOW : PICKUP_FLOW;
}

export function isDeliveryOrder(fulfillmentType) {
    return fulfillmentType === 'delivery';
}

export function getStepLabel(status, fulfillmentType) {
    const delivery = isDeliveryOrder(fulfillmentType);

    return (
        {
            pending: 'Order Received',
            confirmed: delivery ? 'Order Confirmed' : 'Confirmed',
            preparing: 'Preparing',
            ready: delivery ? 'Ready' : 'Ready for Pickup',
            out_for_delivery: 'Out for Delivery',
            completed: delivery ? 'Delivered' : 'Picked Up',
            cancelled: 'Cancelled',
        }[status] ?? status.replaceAll('_', ' ')
    );
}

export function getStatusHeadline(status, fulfillmentType) {
    const delivery = isDeliveryOrder(fulfillmentType);

    return (
        {
            pending: 'Order Received',
            confirmed: 'Your Order Is Confirmed',
            preparing: 'Your Order Is Being Prepared',
            ready: delivery ? 'Your Order Is Ready' : 'Ready for Pickup',
            out_for_delivery: 'Your Order Is On Its Way',
            completed: delivery ? 'Order Delivered' : 'Order Picked Up',
            cancelled: 'Order Cancelled',
        }[status] ?? 'Order Tracking'
    );
}

export function getStatusMessage(status, fulfillmentType) {
    const delivery = isDeliveryOrder(fulfillmentType);

    return (
        {
            pending: "We've received your order. We're waiting for confirmation.",
            confirmed: 'Our team is getting everything ready for you.',
            preparing: 'Our team is currently preparing your order.',
            ready: delivery
                ? 'Your order is ready.'
                : 'Your order is ready. Please collect it from the Pebbles location.',
            out_for_delivery: 'Your order is on its way to you.',
            completed: delivery
                ? 'Your order has been delivered. Enjoy!'
                : 'Your order has been successfully picked up.',
            cancelled: 'This order was cancelled.',
        }[status] ?? 'Track your order progress below.'
    );
}

export function buildReachedStatuses(currentStatus, history = []) {
    return new Set(
        (history ?? [])
            .map((entry) => entry.status)
            .filter(Boolean)
            .concat(currentStatus ? [currentStatus] : []),
    );
}

export function getStepState(status, index, currentStatus, flow, reached) {
    const currentIndex = flow.indexOf(currentStatus);

    if (status === currentStatus) {
        return 'current';
    }

    if (reached.has(status) || (currentIndex >= 0 && index < currentIndex)) {
        return 'complete';
    }

    return 'upcoming';
}

export function shouldAutoRefresh(status) {
    return status && !TERMINAL_STATUSES.includes(status);
}

export function formatRelativeUpdate(date) {
    if (!date) {
        return null;
    }

    const seconds = Math.round((Date.now() - date.getTime()) / 1000);

    if (seconds < 10) {
        return 'Updated just now';
    }

    if (seconds < 60) {
        return `Updated ${seconds}s ago`;
    }

    const minutes = Math.round(seconds / 60);

    if (minutes < 60) {
        return `Updated ${minutes}m ago`;
    }

    return `Updated at ${date.toLocaleTimeString('en-PK', { timeStyle: 'short' })}`;
}

export function formatFulfillmentType(value) {
    return (
        {
            delivery: 'Delivery',
            takeaway: 'Takeaway',
            dine_in: 'Dine in',
        }[value] ?? value
    );
}

export function getIceCreamAnimationMode(status) {
    if (status === 'cancelled') {
        return 'cancelled';
    }

    if (['ready', 'out_for_delivery', 'completed'].includes(status)) {
        return 'complete';
    }

    if (status === 'preparing' || status === 'confirmed') {
        return 'preparing';
    }

    if (status === 'pending') {
        return 'waiting';
    }

    return 'waiting';
}

/** @deprecated Use getIceCreamAnimationMode */
export function getShakeAnimationMode(status) {
    return getIceCreamAnimationMode(status);
}

/**
 * Customer-facing preparation experience copy for the tracking ETA panel.
 *
 * @returns {{ heading: string, message: string, showCountdown: boolean, staticLabel: string|null }}
 */
export function getEstimateExperience(status, fulfillmentType) {
    const delivery = isDeliveryOrder(fulfillmentType);

    if (status === 'pending') {
        return {
            heading: 'Order Received',
            message: "We've received your order. We're waiting for confirmation.",
            showCountdown: false,
            staticLabel: null,
        };
    }

    if (status === 'preparing') {
        return {
            heading: 'Your Order Is Being Prepared',
            message: 'Our team is preparing your order fresh.',
            showCountdown: true,
            staticLabel: null,
        };
    }

    if (status === 'confirmed') {
        return {
            heading: 'Getting Your Order Ready',
            message: 'Our team is preparing your order fresh.',
            showCountdown: true,
            staticLabel: null,
        };
    }

    if (status === 'ready') {
        return {
            heading: delivery ? 'Your Order Is Ready!' : 'Your Order Is Ready for Pickup',
            message: delivery ? 'Everything is ready to go.' : 'Your order is ready to be collected.',
            showCountdown: false,
            staticLabel: 'Ready',
        };
    }

    if (status === 'out_for_delivery') {
        return {
            heading: 'Your Order Is On The Way',
            message: 'Your order is on its way to you.',
            showCountdown: false,
            staticLabel: null,
        };
    }

    if (status === 'completed') {
        return {
            heading: 'Enjoy Your Pebbles Order!',
            message: delivery
                ? 'Your order has been delivered. Enjoy!'
                : 'Your order has been successfully picked up.',
            showCountdown: false,
            staticLabel: null,
        };
    }

    return {
        heading: 'Order Received',
        message: "We've received your order. We're waiting for confirmation.",
        showCountdown: false,
        staticLabel: null,
    };
}

/** @deprecated Use getEstimateExperience */
export function getEstimateCopy(status, fulfillmentType) {
    return getEstimateExperience(status, fulfillmentType);
}

/** @deprecated No longer used in the premium ETA panel */
export function getEstimateLabel(fulfillmentType) {
    return isDeliveryOrder(fulfillmentType) ? 'Estimated delivery' : 'Estimated pickup';
}

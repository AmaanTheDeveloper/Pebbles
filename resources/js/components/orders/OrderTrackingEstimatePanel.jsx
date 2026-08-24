import { getEstimateExperience, getIceCreamAnimationMode } from '../../lib/orderTracking';
import useOrderEstimateCountdown from '../../hooks/useOrderEstimateCountdown';
import AnimatedPreparationHeading from './AnimatedPreparationHeading';
import EstimateCountdownValue from './EstimateCountdownValue';
import IceCreamMakingAnimation from './IceCreamMakingAnimation';

export default function OrderTrackingEstimatePanel({ order }) {
    const estimate = order?.estimated_time;
    const status = order?.status;
    const fulfillmentType = order?.fulfillment_type;
    const experience = getEstimateExperience(status, fulfillmentType);
    const animationMode = getIceCreamAnimationMode(status);
    const animateHeading = animationMode === 'preparing';
    const showCountdown = Boolean(estimate?.deadline_at && experience.showCountdown);

    const countdown = useOrderEstimateCountdown({
        deadline: estimate?.deadline_at,
        serverNow: estimate?.server_now,
        enabled: showCountdown,
    });

    if (!estimate && status === 'cancelled') {
        return null;
    }

    if (!estimate && !experience.message) {
        return null;
    }

    return (
        <section
            className="order-track-estimate rounded-[1.75rem] bg-gradient-to-br from-brand-cream via-white to-brand-cream p-5 ring-1 ring-brand-green/10 sm:p-7"
            aria-label="Order preparation status"
        >
            <div className="flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:gap-10">
                <IceCreamMakingAnimation mode={animationMode} className="order-track-estimate-art shrink-0" />

                <div className="min-w-0 flex-1 space-y-2 text-center sm:text-left">
                    <AnimatedPreparationHeading heading={experience.heading} animated={animateHeading} />

                    {showCountdown ? (
                        <EstimateCountdownValue
                            text={countdown.displayText}
                            isFinalMinute={countdown.isFinalMinute}
                        />
                    ) : experience.staticLabel ? (
                        <p className="font-display text-2xl font-semibold text-brand-green sm:text-3xl">
                            {experience.staticLabel}
                        </p>
                    ) : null}

                    <p className="text-sm leading-relaxed text-brand-muted sm:text-[0.9375rem]">
                        {experience.message}
                    </p>
                </div>
            </div>
        </section>
    );
}

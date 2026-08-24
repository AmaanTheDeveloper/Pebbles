import { useEffect, useMemo, useState } from 'react';
import { computeRemainingMs } from '../lib/orderChangeWindow';

export default function useOrderChangeCountdown({ deadline, serverNow, enabled = true }) {
    const [remainingMs, setRemainingMs] = useState(() =>
        enabled ? computeRemainingMs(deadline, serverNow) : 0,
    );

    useEffect(() => {
        if (!enabled || !deadline || !serverNow) {
            setRemainingMs(0);
            return undefined;
        }

        const tick = () => {
            setRemainingMs(computeRemainingMs(deadline, serverNow));
        };

        tick();
        const timer = window.setInterval(tick, 1000);

        return () => window.clearInterval(timer);
    }, [deadline, serverNow, enabled]);

    return useMemo(
        () => ({
            remainingMs,
            expired: remainingMs <= 0,
        }),
        [remainingMs],
    );
}

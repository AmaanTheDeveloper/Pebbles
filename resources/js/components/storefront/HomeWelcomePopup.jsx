import { useEffect, useState } from 'react';
import { fetchWelcomePopup } from '../../api/popup';
import { useAuth } from '../../contexts/AuthContext';
import { markWelcomePopupDismissed, shouldShowWelcomePopup } from '../../lib/welcomePopupStorage';
import WelcomePopupModal from './WelcomePopupModal';

export default function HomeWelcomePopup() {
    const { user } = useAuth();
    const [popup, setPopup] = useState(null);
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (user?.role === 'super_admin' || user?.role === 'manager') {
            return undefined;
        }

        let delayTimer;
        let cancelled = false;

        const load = async () => {
            try {
                const config = await fetchWelcomePopup();
                if (cancelled || !config?.is_active) {
                    return;
                }

                if (! shouldShowWelcomePopup(config.frequency)) {
                    return;
                }

                if (! config.heading && ! config.message && ! config.description && ! config.image?.url) {
                    return;
                }

                setPopup(config);

                delayTimer = window.setTimeout(() => {
                    if (! cancelled) {
                        setOpen(true);
                    }
                }, config.delay_ms ?? 700);
            } catch {
                // Popup is optional; ignore fetch failures.
            }
        };

        load();

        return () => {
            cancelled = true;
            if (delayTimer) {
                window.clearTimeout(delayTimer);
            }
        };
    }, [user?.role]);

    const handleClose = () => {
        if (popup?.frequency) {
            markWelcomePopupDismissed(popup.frequency);
        }
        setOpen(false);
    };

    return <WelcomePopupModal open={open} popup={popup} onClose={handleClose} />;
}

import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import { shouldPromptEmailVerification } from '../lib/emailVerification';

const EmailVerificationReminderContext = createContext(null);

export function EmailVerificationReminderProvider({ children }) {
    const [reminder, setReminder] = useState(null);

    const dismissReminder = useCallback(() => {
        setReminder(null);
    }, []);

    const showReminder = useCallback((user, { reason = 'register' } = {}) => {
        if (!shouldPromptEmailVerification(user)) {
            return;
        }

        setReminder({
            email: user.email,
            reason,
            shownAt: Date.now(),
        });
    }, []);

    const value = useMemo(
        () => ({
            reminder,
            showReminder,
            dismissReminder,
            isVisible: Boolean(reminder),
        }),
        [reminder, showReminder, dismissReminder],
    );

    return (
        <EmailVerificationReminderContext.Provider value={value}>
            {children}
        </EmailVerificationReminderContext.Provider>
    );
}

export function useEmailVerificationReminder() {
    const context = useContext(EmailVerificationReminderContext);

    if (!context) {
        throw new Error('useEmailVerificationReminder must be used within EmailVerificationReminderProvider.');
    }

    return context;
}

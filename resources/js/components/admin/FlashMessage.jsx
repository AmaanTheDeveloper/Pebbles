export default function FlashMessage({ type = 'success', message, onDismiss }) {
    if (!message) {
        return null;
    }

    const styles = type === 'error' ? 'admin-flash admin-flash-error' : 'admin-flash admin-flash-success';

    return (
        <div className={styles} role="status">
            <p>{message}</p>
            {onDismiss ? (
                <button
                    type="button"
                    onClick={onDismiss}
                    className="admin-btn admin-btn-ghost shrink-0 px-2 py-1 text-xs"
                    aria-label="Dismiss message"
                >
                    Dismiss
                </button>
            ) : null}
        </div>
    );
}

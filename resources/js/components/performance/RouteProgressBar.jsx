import { useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';

export default function RouteProgressBar() {
    const location = useLocation();
    const [active, setActive] = useState(false);

    useEffect(() => {
        setActive(true);

        const timer = window.setTimeout(() => setActive(false), 320);

        return () => window.clearTimeout(timer);
    }, [location.pathname, location.search]);

    if (!active) {
        return null;
    }

    return <div className="route-progress-bar" aria-hidden="true" />;
}

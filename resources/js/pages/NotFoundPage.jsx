import { Link, useOutletContext } from 'react-router-dom';
import SeoHead from '../components/seo/SeoHead';
import StatusExperience from '../components/status/StatusExperience';

export default function NotFoundPage() {
    const { logo } = useOutletContext() ?? {};

    return (
        <>
            <SeoHead
                title="Page not found · Pebbles"
                description="The page you're looking for could not be found."
                noindex
            />
            <StatusExperience
                logo={logo}
                eyebrow="404"
                title="Oops! This page melted away."
                description="The page you're looking for could not be found. It may have been moved, renamed, or never existed."
                illustration="404"
            >
                <Link to="/" className="btn-orange btn-shine status-page-btn">
                    Back to Home
                </Link>
                <Link to="/menu" className="status-page-btn-secondary">
                    Explore Menu
                </Link>
            </StatusExperience>
        </>
    );
}

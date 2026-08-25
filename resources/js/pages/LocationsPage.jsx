import { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { fetchContactInfo } from '../api/contact';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import LocationCard from '../components/storefront/locations/LocationCard';
import LocationsHero from '../components/storefront/locations/LocationsHero';
import LocationsMap from '../components/storefront/locations/LocationsMap';
import SeoHead from '../components/seo/SeoHead';
import { ScrollReveal, ScrollRevealItem } from '../hooks/useInView';

export default function LocationsPage() {
    const [contact, setContact] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedLocationId, setSelectedLocationId] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await fetchContactInfo();
            setContact(data);
            if (data.locations?.length) {
                setSelectedLocationId(data.locations[0].id);
            }
        } catch (err) {
            setError(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    if (loading) {
        return (
            <>
                <SeoHead
                    title="Locations"
                    description="Find your nearest Pebbles branch — addresses, hours, directions, and maps."
                    path="/locations"
                />
                <LoadingState label="Loading locations…" />
            </>
        );
    }

    if (error) {
        return (
            <>
                <SeoHead
                    title="Locations"
                    description="Find your nearest Pebbles branch — addresses, hours, directions, and maps."
                    path="/locations"
                />
                <ErrorState message={error.message || 'Unable to load locations.'} onRetry={load} />
            </>
        );
    }

    if (!contact?.show_locations_page) {
        return <Navigate to="/contact" replace />;
    }

    const locations = contact.locations ?? [];

    const handleSelectLocation = (location) => {
        setSelectedLocationId(location.id);
        document.getElementById('locations-map')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return (
        <>
            <SeoHead
                title="Locations"
                description="Find your nearest Pebbles branch — addresses, hours, directions, and maps."
                path="/locations"
            />
            <LocationsHero />

            <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                <ScrollReveal>
                    <div className="grid gap-6 md:grid-cols-2">
                        {locations.map((location, index) => (
                            <ScrollRevealItem key={location.id} index={index}>
                                <LocationCard location={location} onSelect={handleSelectLocation} />
                            </ScrollRevealItem>
                        ))}
                    </div>
                </ScrollReveal>
            </section>

            <LocationsMap locations={locations} selectedId={selectedLocationId} onSelect={setSelectedLocationId} />
        </>
    );
}

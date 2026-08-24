import { useEffect, useState } from 'react';
import { fetchContactInfo } from '../api/contact';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import ContactForm from '../components/storefront/contact/ContactForm';
import ContactHero from '../components/storefront/contact/ContactHero';
import ContactInfoCards, {
    ContactHoursPanel,
    ContactSocialLinks,
} from '../components/storefront/contact/ContactInfoCards';
import ContactLocationSection, { ContactMapSection } from '../components/storefront/contact/ContactLocationSection';
import { ScrollReveal } from '../hooks/useInView';

export default function ContactPage() {
    const [contact, setContact] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await fetchContactInfo();
            setContact(data);
        } catch (err) {
            setError(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        document.title = 'Contact — Pebbles';
    }, []);

    if (loading) {
        return <LoadingState label="Loading contact information…" />;
    }

    if (error) {
        return <ErrorState message={error.message || 'Unable to load contact information.'} onRetry={load} />;
    }

    const showGeneralMap = !contact.primary_location && contact.map_embed_url;

    return (
        <>
            <ContactHero />

            <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                <ScrollReveal>
                    <div className="grid gap-8 lg:grid-cols-2 lg:gap-10">
                        <div className="space-y-6">
                            <div>
                                <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                                    Contact details
                                </p>
                                <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                                    We&apos;re Here to Help
                                </h2>
                            </div>
                            <ContactInfoCards contact={contact} />
                            <ContactHoursPanel contact={contact} />
                            <ContactSocialLinks social={contact.social} />
                        </div>

                        <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-green/10 sm:p-6">
                            <h2 className="font-display text-2xl font-semibold text-brand-ink">Send a Message</h2>
                            <p className="mt-2 text-sm text-brand-muted">
                                Share your question or feedback and we&apos;ll get back to you soon.
                            </p>
                            <div className="mt-6">
                                <ContactForm />
                            </div>
                        </div>
                    </div>
                </ScrollReveal>
            </section>

            <ContactLocationSection location={contact.primary_location} />

            {showGeneralMap ? <ContactMapSection contact={contact} /> : null}
        </>
    );
}

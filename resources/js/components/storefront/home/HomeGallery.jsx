import { Link } from 'react-router-dom';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

function ViewIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z" />
            <circle cx="12" cy="12" r="2.5" />
        </svg>
    );
}

export default function HomeGallery({ items = [], products = [], content = {} }) {
    const limit = content.item_limit ?? 6;

    const images = (items.length
        ? items.map((item) => ({
              id: item.id,
              url: item.image,
              alt: item.alt || item.title,
              name: item.title,
              slug: null,
              href: '/gallery',
          }))
        : (products ?? [])
              .filter((product) => product.image?.url)
              .map((product) => ({
                  id: product.id,
                  url: product.image.url,
                  alt: product.image.alt_text || product.name,
                  name: product.name,
                  slug: product.slug,
                  href: `/product/${product.slug}`,
              }))
    ).slice(0, limit);

    if (!images.length) {
        return null;
    }

    const [first, ...rest] = images;

    return (
        <section className="hp-section hp-section-divider bg-white/60 py-16 sm:py-20" id="gallery">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <ScrollReveal>
                    <div className="hp-section-head mb-8 max-w-2xl">
                        <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                            {content.eyebrow ?? 'Gallery'}
                        </p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            {content.title ?? 'A scoop of the vibe'}
                        </h2>
                        <p className="mt-3 text-brand-muted">
                            {content.description ??
                                'Ice cream, shakes, faluda, and kulfi from the live menu — tap any treat for details.'}
                        </p>
                    </div>
                </ScrollReveal>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4 md:grid-rows-2 md:gap-4">
                    <ScrollRevealItem index={1} variant="scale" className="col-span-2 row-span-2">
                        <Link
                            to={first.href ?? '/gallery'}
                            className="gallery-tile group block size-full rounded-3xl shadow-lg shadow-brand-green/10"
                        >
                            <img
                                src={first.url}
                                alt={first.alt}
                                className="aspect-square size-full md:aspect-auto md:min-h-[28rem]"
                                loading="lazy"
                            />
                            <div className="gallery-overlay">
                                <span className="gallery-view">
                                    <ViewIcon />
                                </span>
                            </div>
                            <p className="gallery-label text-lg md:text-2xl">{first.name}</p>
                        </Link>
                    </ScrollRevealItem>

                    {rest.map((image, index) => (
                        <ScrollRevealItem key={image.id} index={index + 2} variant="scale" className="min-h-0">
                            <Link
                                to={image.href ?? '/gallery'}
                                className="gallery-tile group block size-full rounded-2xl shadow-md shadow-brand-green/5"
                            >
                                <img src={image.url} alt={image.alt} className="aspect-square" loading="lazy" />
                                <div className="gallery-overlay">
                                    <span className="gallery-view">
                                        <ViewIcon />
                                    </span>
                                </div>
                                <p className="gallery-label text-sm">{image.name}</p>
                            </Link>
                        </ScrollRevealItem>
                    ))}
                </div>
            </div>
        </section>
    );
}

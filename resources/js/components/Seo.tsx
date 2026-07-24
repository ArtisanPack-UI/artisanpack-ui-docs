import { Head, usePage } from '@inertiajs/react';
import type { SharedProps } from '@/types/inertia';

export function Seo() {
    const { seo } = usePage<SharedProps>().props;

    if (!seo) {
        return null;
    }

    return (
        <Head title={seo.title}>
            {seo.description ? <meta name="description" content={seo.description} /> : null}
            <meta name="robots" content={seo.robots} />
            {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}

            {Object.entries(seo.openGraph).map(([property, content]) => (
                <meta key={property} property={property} content={String(content)} />
            ))}

            {Object.entries(seo.twitter).map(([name, content]) => (
                <meta key={name} name={name} content={String(content)} />
            ))}

            {seo.hreflang.map(({ hreflang, href }) => (
                <link key={hreflang} rel="alternate" hrefLang={hreflang} href={href} />
            ))}

            {seo.jsonLd.map((schema, index) => (
                <script
                    key={index}
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
                />
            ))}
        </Head>
    );
}

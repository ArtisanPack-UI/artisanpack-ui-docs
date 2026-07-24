import { Head, Link } from '@inertiajs/react';
import { Button } from '@artisanpack-ui/react/form';

interface NotFoundProps {
    status?: number;
    message?: string;
}

export default function NotFound({ status = 404, message }: NotFoundProps) {
    return (
        <>
            <Head title="Page not found" />
            <main className="ap-aurora min-h-screen bg-base text-text">
                <div className="flex min-h-screen flex-col items-center justify-center px-6 py-12 text-center">
                    <p className="font-display text-small uppercase tracking-[0.2em] text-text-muted">
                        Error {status}
                    </p>
                    <h1 className="mt-4 font-display text-h1 font-bold">Page not found</h1>
                    <p className="mt-4 max-w-lg text-body text-text-muted">
                        {message ?? "The page you're looking for has wandered off. Try heading back home or dig into the docs."}
                    </p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Button color="primary" link="/">
                            Back to home
                        </Button>
                        <Button color="outline" link="/documentation">
                            Browse the docs
                        </Button>
                    </div>
                    <p className="mt-10 text-xsmall text-text-subtle">
                        Need help? <Link href="/" className="underline hover:text-text">Return to ArtisanPack UI</Link>.
                    </p>
                </div>
            </main>
        </>
    );
}

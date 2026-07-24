import { Head, Link } from '@inertiajs/react';
import { Button } from '@artisanpack-ui/react/form';

interface ServerErrorProps {
    status?: number;
    message?: string;
}

export default function ServerError({ status = 500, message }: ServerErrorProps) {
    return (
        <>
            <Head title="Something went wrong" />
            <div className="ap-aurora min-h-screen bg-base text-text">
                <div className="flex min-h-screen flex-col items-center justify-center px-6 py-12 text-center">
                    <p className="font-display text-small uppercase tracking-[0.2em] text-text-muted">
                        Error {status}
                    </p>
                    <h1 className="mt-4 font-display text-h1 font-bold">Something went wrong</h1>
                    <p className="mt-4 max-w-lg text-body text-text-muted">
                        {message ?? "An unexpected error broke through on our end. The team has been notified — please try again in a moment."}
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
                        Still stuck? <Link href="/" className="underline hover:text-text">Return to ArtisanPack UI</Link>.
                    </p>
                </div>
            </div>
        </>
    );
}

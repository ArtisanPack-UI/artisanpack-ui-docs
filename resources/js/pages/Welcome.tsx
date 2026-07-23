import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <main className="min-h-screen flex items-center justify-center p-8">
                <div className="text-center">
                    <h1 className="text-4xl font-bold">ArtisanPack UI Docs</h1>
                    <p className="mt-4 text-lg opacity-70">
                        Inertia + React + TypeScript foundation is live.
                    </p>
                </div>
            </main>
        </>
    );
}

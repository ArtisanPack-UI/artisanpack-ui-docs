import { Button } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <main className="min-h-screen flex items-center justify-center p-8">
                <Card className="max-w-xl w-full text-center">
                    <h1 className="text-4xl font-bold">ArtisanPack UI Docs</h1>
                    <p className="mt-4 text-lg opacity-70">
                        Inertia + React + TypeScript + @artisanpack-ui/react is live.
                    </p>
                    <div className="mt-6 flex justify-center gap-3">
                        <Button color="primary" link="https://artisanpackui.dev" external>
                            View docs
                        </Button>
                        <Button color="outline" link="https://github.com/ArtisanPack-UI" external>
                            GitHub
                        </Button>
                    </div>
                </Card>
            </main>
        </>
    );
}

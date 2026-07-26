import { Head, router } from '@inertiajs/react';
import { Button } from '@artisanpack-ui/react/form';

import { AuthLayout } from '../../layouts/AuthLayout';

export default function Verified() {
    return (
        <AuthLayout
            title="Email verified"
            description="Your email address has been successfully verified"
        >
            <Head title="Email verified" />

            <p className="mb-6 text-small text-text-muted">
                Thanks for confirming your email address. You now have full access to your
                ArtisanPack UI account.
            </p>

            <Button
                type="button"
                color="primary"
                className="w-full"
                onClick={() => router.visit('/dashboard')}
            >
                Continue to dashboard
            </Button>
        </AuthLayout>
    );
}

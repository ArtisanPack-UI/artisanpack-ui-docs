import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@artisanpack-ui/react/form';
import type { FormEventHandler } from 'react';

import { AuthLayout } from '../../layouts/AuthLayout';

interface VerifyEmailProps {
    status?: string | null;
}

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post('/email/verification-notification');
    };

    return (
        <AuthLayout
            title="Verify your email address"
            description="Check your inbox for a verification link before continuing"
        >
            <Head title="Verify email" />

            {status === 'verification-link-sent' ? (
                <div role="status" className="mb-4 text-center text-small text-success">
                    A new verification link has been sent to your email address.
                </div>
            ) : null}

            <p className="mb-6 text-small text-text-muted">
                Thanks for signing up. Before getting started, please verify your email address by
                clicking on the link we just sent to you. If you didn&rsquo;t receive the email,
                we&rsquo;ll gladly send you another.
            </p>

            <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                <Button type="submit" color="primary" className="w-full" loading={processing}>
                    Resend verification email
                </Button>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="self-center text-small text-text-muted underline hover:text-text"
                >
                    Log out
                </Link>
            </form>
        </AuthLayout>
    );
}

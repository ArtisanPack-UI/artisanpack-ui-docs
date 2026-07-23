import { Head, Link, useForm } from '@inertiajs/react';
import { Button, Input } from '@artisanpack-ui/react/form';
import type { FormEventHandler } from 'react';

import { AuthLayout } from '../../layouts/AuthLayout';

interface ForgotPasswordProps {
    status?: string | null;
}

type ForgotPasswordForm = {
    email: string;
};

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm<ForgotPasswordForm>({
        email: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post('/forgot-password');
    };

    return (
        <AuthLayout
            title="Forgot your password?"
            description="Enter your email and we'll send you a link to reset your password"
        >
            <Head title="Forgot password" />

            {status ? (
                <div
                    role="status"
                    className="mb-4 text-center text-small text-success"
                >
                    {status}
                </div>
            ) : null}

            <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    label="Email address"
                    autoComplete="email"
                    autoFocus
                    required
                    placeholder="email@example.com"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                />

                <Button
                    type="submit"
                    color="primary"
                    className="w-full"
                    loading={processing}
                >
                    Email password reset link
                </Button>

                <Link
                    href="/login"
                    className="self-center text-small text-text-muted underline hover:text-text"
                >
                    Back to log in
                </Link>
            </form>
        </AuthLayout>
    );
}

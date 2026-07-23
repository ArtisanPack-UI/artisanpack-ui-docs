import { Head, useForm } from '@inertiajs/react';
import { Button, Input, Password } from '@artisanpack-ui/react/form';
import type { FormEventHandler } from 'react';

import { AuthLayout } from '../../layouts/AuthLayout';

interface ResetPasswordProps {
    token: string;
    email: string;
}

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<ResetPasswordForm>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post('/reset-password', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title="Reset your password"
            description="Choose a new password to regain access to your account"
        >
            <Head title="Reset password" />

            <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    label="Email address"
                    autoComplete="email"
                    required
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                />

                <Password
                    id="password"
                    name="password"
                    label="Password"
                    autoComplete="new-password"
                    autoFocus
                    required
                    value={data.password}
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                />

                <Password
                    id="password_confirmation"
                    name="password_confirmation"
                    label="Confirm password"
                    autoComplete="new-password"
                    required
                    value={data.password_confirmation}
                    onChange={(event) => setData('password_confirmation', event.target.value)}
                    error={errors.password_confirmation}
                />

                <Button
                    type="submit"
                    color="primary"
                    className="w-full"
                    loading={processing}
                >
                    Reset password
                </Button>
            </form>
        </AuthLayout>
    );
}

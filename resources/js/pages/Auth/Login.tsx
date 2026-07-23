import { Head, Link, useForm } from '@inertiajs/react';
import { Button, Checkbox, Input, Password } from '@artisanpack-ui/react/form';
import type { FormEventHandler } from 'react';

import { AuthLayout } from '../../layouts/AuthLayout';

interface LoginProps {
    status?: string | null;
    canResetPassword: boolean;
}

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Log in to your account"
            description="Enter your email and password below to log in"
        >
            <Head title="Log in" />

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

                <div className="flex flex-col gap-2">
                    <Password
                        id="password"
                        name="password"
                        label="Password"
                        autoComplete="current-password"
                        required
                        placeholder="Password"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        error={errors.password}
                    />
                    {canResetPassword ? (
                        <Link
                            href="/forgot-password"
                            className="self-end text-small text-text-muted underline hover:text-text"
                        >
                            Forgot your password?
                        </Link>
                    ) : null}
                </div>

                <Checkbox
                    id="remember"
                    name="remember"
                    label="Remember me"
                    checked={data.remember}
                    onChange={(event) => setData('remember', event.target.checked)}
                />

                <Button
                    type="submit"
                    color="primary"
                    className="w-full"
                    loading={processing}
                >
                    Log in
                </Button>
            </form>
        </AuthLayout>
    );
}

import { Head, Link, useForm } from '@inertiajs/react';
import { Button, Input } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Alert } from '@artisanpack-ui/react/feedback';
import { useState, type FormEventHandler } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';

interface ProfileProps {
    user: {
        name: string;
        email: string;
    };
    mustVerifyEmail: boolean;
    isVerified: boolean;
    status?: string | null;
}

type ProfileForm = {
    name: string;
    email: string;
};

export default function Profile({ user, mustVerifyEmail, isVerified, status }: ProfileProps) {
    const [saved, setSaved] = useState(false);
    const { data, setData, put, processing, errors } = useForm<ProfileForm>({
        name: user.name,
        email: user.email,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        put('/user/profile-information', {
            preserveScroll: true,
            onSuccess: () => setSaved(true),
        });
    };

    return (
        <AdminLayout title="Profile">
            <Head title="Profile" />

            <div className="mx-auto w-full max-w-3xl">
                <header className="mb-8">
                    <p className="text-small text-text-muted">
                        Update your name and email address.
                    </p>
                </header>

                <Card>
                    <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            label="Name"
                            autoComplete="name"
                            required
                            autoFocus
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            error={errors.name}
                        />

                        <div className="flex flex-col gap-2">
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                label="Email"
                                autoComplete="email"
                                required
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                error={errors.email}
                            />

                            {mustVerifyEmail && !isVerified ? (
                                <div className="text-small text-text-muted">
                                    Your email address is unverified.{' '}
                                    <Link
                                        href="/email/verification-notification"
                                        method="post"
                                        as="button"
                                        className="underline hover:text-text"
                                    >
                                        Click here to re-send the verification email.
                                    </Link>
                                    {status === 'verification-link-sent' ? (
                                        <p className="mt-2 font-medium text-success">
                                            A new verification link has been sent to your email
                                            address.
                                        </p>
                                    ) : null}
                                </div>
                            ) : null}
                        </div>

                        <div className="flex items-center gap-4">
                            <Button type="submit" color="primary" loading={processing}>
                                Save
                            </Button>
                            {saved && !processing ? (
                                <Alert color="success" className="!py-2">
                                    Saved.
                                </Alert>
                            ) : null}
                        </div>
                    </form>
                </Card>
            </div>
        </AdminLayout>
    );
}

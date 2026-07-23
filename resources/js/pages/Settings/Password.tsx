import { Head, useForm } from '@inertiajs/react';
import { Button, Password as PasswordInput } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Alert } from '@artisanpack-ui/react/feedback';
import { useState, type FormEventHandler } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';

type PasswordForm = {
    current_password: string;
    password: string;
    password_confirmation: string;
};

export default function Password() {
    const [saved, setSaved] = useState(false);
    const { data, setData, put, processing, errors, reset } = useForm<PasswordForm>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        put('/user/password', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setSaved(true);
            },
            onError: () => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                }
                if (errors.current_password) {
                    reset('current_password');
                }
            },
        });
    };

    return (
        <AdminLayout title="Password">
            <Head title="Password" />

            <div className="mx-auto w-full max-w-3xl">
                <header className="mb-8">
                    <p className="text-small text-text-muted">
                        Ensure your account is using a long, random password to stay secure.
                    </p>
                </header>

                <Card>
                    <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            label="Current password"
                            autoComplete="current-password"
                            required
                            value={data.current_password}
                            onChange={(event) => setData('current_password', event.target.value)}
                            error={errors.current_password}
                        />

                        <PasswordInput
                            id="password"
                            name="password"
                            label="New password"
                            autoComplete="new-password"
                            required
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            error={errors.password}
                        />

                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            label="Confirm password"
                            autoComplete="new-password"
                            required
                            value={data.password_confirmation}
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            error={errors.password_confirmation}
                        />

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

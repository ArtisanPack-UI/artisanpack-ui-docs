import { Head, useForm } from '@inertiajs/react';
import { Button, Password } from '@artisanpack-ui/react/form';
import type { FormEventHandler } from 'react';

import { AuthLayout } from '../../layouts/AuthLayout';

type ConfirmForm = {
    password: string;
};

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm<ConfirmForm>({
        password: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post('/user/confirm-password', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Confirm your password"
            description="This is a secure area — confirm your password before continuing"
        >
            <Head title="Confirm password" />

            <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                <Password
                    id="password"
                    name="password"
                    label="Password"
                    autoComplete="current-password"
                    autoFocus
                    required
                    value={data.password}
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                />

                <Button type="submit" color="primary" className="w-full" loading={processing}>
                    Confirm
                </Button>
            </form>
        </AuthLayout>
    );
}

import { Head, Link, useForm } from '@inertiajs/react';
import { Button, Input, Select } from '@artisanpack-ui/react/form';
import type { FormEventHandler } from 'react';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface RoleOption {
    value: string;
    label: string;
    [key: string]: string;
}

interface CreateProps {
    store_url: string;
    cancel_url: string;
    role_options: RoleOption[];
}

interface UserForm {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
    [key: string]: string;
}

function GradientCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="ap-box ap-border-gradient flex flex-col gap-4 p-6">
            <h2 className="font-display text-h6">{title}</h2>
            {children}
        </section>
    );
}

export default function UsersCreate({ store_url, cancel_url, role_options }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm<UserForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'editor',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(store_url);
    };

    return (
        <AdminLayout title="Add User">
            <Head title="Add User" />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-3xl flex-col gap-6" noValidate>
                <GradientCard title="User">
                    <Input
                        id="name"
                        label="Name"
                        required
                        autoFocus
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        error={errors.name}
                    />
                    <Input
                        id="email"
                        type="email"
                        label="Email"
                        required
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        error={errors.email}
                    />
                    <Select
                        id="role"
                        label="Role"
                        required
                        options={role_options}
                        optionValue="value"
                        optionLabel="label"
                        value={data.role}
                        onChange={(event) => setData('role', event.target.value)}
                        error={errors.role}
                    />
                    <Input
                        id="password"
                        type="password"
                        label="Password"
                        required
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        error={errors.password}
                    />
                    <Input
                        id="password_confirmation"
                        type="password"
                        label="Confirm Password"
                        required
                        value={data.password_confirmation}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                    />

                    <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <Link href={cancel_url} className="btn btn-ghost">
                            Cancel
                        </Link>
                        <Button type="submit" color="primary" loading={processing}>
                            Create User
                        </Button>
                    </div>
                </GradientCard>
            </form>
        </AdminLayout>
    );
}

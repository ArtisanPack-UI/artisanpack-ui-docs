import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button, Input, Select } from '@artisanpack-ui/react/form';
import { Alert } from '@artisanpack-ui/react/feedback';
import type { FormEventHandler } from 'react';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface UserPayload {
    id: number;
    name: string;
    email: string;
    role: string;
    email_verified_at: string | null;
}

interface RoleOption {
    value: string;
    label: string;
    [key: string]: string;
}

interface EditProps {
    user: UserPayload;
    update_url: string;
    destroy_url: string;
    index_url: string;
    is_current_user: boolean;
    role_options: RoleOption[];
    flash?: { success?: string | null };
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

export default function UsersEdit({
    user,
    update_url,
    destroy_url,
    index_url,
    is_current_user,
    role_options,
    flash,
}: EditProps) {
    const { data, setData, patch, processing, errors } = useForm<UserForm>({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role: user.role,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(update_url, { preserveScroll: true });
    };

    const handleDelete = () => {
        if (!window.confirm(`Delete user "${user.name}"?`)) {
            return;
        }
        router.delete(destroy_url);
    };

    return (
        <AdminLayout title="Edit User">
            <Head title={`Edit ${user.name}`} />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-3xl flex-col gap-6" noValidate>
                {flash?.success ? <Alert color="success">{flash.success}</Alert> : null}

                <GradientCard title="User">
                    <Input
                        id="name"
                        label="Name"
                        required
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
                        disabled={is_current_user}
                        hint={is_current_user ? 'You cannot change your own role.' : undefined}
                    />
                    <Input
                        id="password"
                        type="password"
                        label="New Password"
                        hint="Leave blank to keep the current password."
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        error={errors.password}
                    />
                    <Input
                        id="password_confirmation"
                        type="password"
                        label="Confirm New Password"
                        value={data.password_confirmation}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                    />

                    <div className="flex flex-wrap items-center gap-3 pt-2">
                        <Link href={index_url} className="btn btn-ghost">
                            Back
                        </Link>
                        <Button
                            type="button"
                            color="error"
                            onClick={handleDelete}
                            disabled={is_current_user}
                            className="ml-auto"
                        >
                            Delete User
                        </Button>
                        <Button type="submit" color="primary" loading={processing}>
                            Update User
                        </Button>
                    </div>
                </GradientCard>
            </form>
        </AdminLayout>
    );
}

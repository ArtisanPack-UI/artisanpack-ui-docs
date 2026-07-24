import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Table, type TableHeader } from '@artisanpack-ui/react/data';
import { Alert } from '@artisanpack-ui/react/feedback';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface UserRow extends Record<string, unknown> {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    edit_url: string;
    destroy_url: string;
}

interface IndexProps {
    users: UserRow[];
    create_url: string;
    current_user_id: number | null;
    flash?: { success?: string | null };
    errors?: { user?: string };
}

const HEADERS: TableHeader<UserRow>[] = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'email_verified_at', label: 'Verified', render: (row) => (row.email_verified_at ? 'Yes' : 'No') },
];

export default function UsersIndex({ users, create_url, current_user_id, flash, errors }: IndexProps) {
    const handleDelete = (user: UserRow) => {
        if (!window.confirm(`Delete user "${user.name}"?`)) {
            return;
        }
        router.delete(user.destroy_url, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Users">
            <Head title="Users" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6">
                <header className="flex items-start justify-between gap-4">
                    <p className="text-small text-text-muted">
                        Manage the users who can sign in to the admin.
                    </p>
                    <Link href={create_url} className="btn btn-primary">
                        Add User
                    </Link>
                </header>

                {flash?.success ? <Alert color="success">{flash.success}</Alert> : null}
                {errors?.user ? <Alert color="error">{errors.user}</Alert> : null}

                <Card>
                    <Table<UserRow>
                        headers={HEADERS}
                        rows={users}
                        emptyText="No users yet. Add one to get started."
                        renderActions={(user) => (
                            <div className="flex justify-end gap-2">
                                <Link href={user.edit_url} className="btn btn-sm">
                                    Edit
                                </Link>
                                <Button
                                    size="sm"
                                    color="error"
                                    disabled={user.id === current_user_id}
                                    onClick={() => handleDelete(user)}
                                >
                                    Delete
                                </Button>
                            </div>
                        )}
                    />
                </Card>
            </div>
        </AdminLayout>
    );
}

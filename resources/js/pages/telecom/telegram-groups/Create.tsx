import TelecomHeaderNav from '@/components/telecom/TelecomHeaderNav';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';
import TelegramGroupForm, { GroupFormData } from './TelegramGroupForm';

type Option = { id: number; name?: string; branch_name?: string };

type PageProps = {
    branches: Option[];
    departments: Option[];
    groupTypes: string[];
    categories: string[];
    statuses: string[];
};

export default function CreateTelegramGroup({ branches, departments, groupTypes, categories, statuses }: PageProps) {
    const { data, setData, post, processing, errors } = useForm<GroupFormData>({
        name: '',
        chat_id: '',
        username: '',
        group_type: 'Supergroup',
        category: 'Operations',
        purpose: '',
        description: '',
        branch_id: '',
        department_id: '',
        managed_by: '',
        status: 'Active',
        invite_link: '',
        is_public: false,
        member_count: '',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/telecom/telegram-groups');
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Telecom Management', href: '/telecom/dashboard' }, { title: 'Telegram Groups', href: '/telecom/telegram-groups' }, { title: 'Register' }]}>
            <Head title="Register Telegram Group" />
            <div className="mx-auto max-w-screen-2xl space-y-5 p-4 sm:p-6">
                <TelecomHeaderNav title="Telecom Management – Telegram Groups" description="Manage, track, and generate QR codes for corporate Telegram groups and channels" />
                <TelegramGroupForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={handleSubmit}
                    branches={branches}
                    departments={departments}
                    groupTypes={groupTypes}
                    categories={categories}
                    statuses={statuses}
                    mode="create"
                />
            </div>
        </AppLayout>
    );
}

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { SettingRow, SettingSection } from '@/components/settings';
import { toast } from '@/components/ui/sonner';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit as editNotifications, update } from '@/routes/notifications';
import { type BreadcrumbItem } from '@/types';

interface Props {
    notifications: {
        weekly_digest: boolean;
        commit_summary: boolean;
        repository_alerts: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: editNotifications.url(),
    },
];

function updatePreference(key: string, value: boolean) {
    router.patch(
        update.url(),
        {
            notifications: {
                [key]: value,
            },
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Notification preferences updated');
            },
            onError: () => {
                toast.error('Failed to update preferences');
            },
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Notification settings" />

        <h1 class="sr-only">Notification Settings</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <HeadingSmall
                    title="Notification preferences"
                    description="Choose how you want to be notified about your activity"
                />

                <SettingSection
                    title="Email Notifications"
                    description="Manage your email notification preferences"
                    :icon="Bell"
                >
                    <SettingRow
                        title="Weekly digest"
                        description="Receive a weekly summary of your commit activity and insights"
                    >
                        <Switch
                            :checked="props.notifications.weekly_digest"
                            @update:checked="
                                (value: boolean) =>
                                    updatePreference('weekly_digest', value)
                            "
                        />
                    </SettingRow>

                    <SettingRow
                        title="Commit summaries"
                        description="Get notified when new commits are synced from your repositories"
                    >
                        <Switch
                            :checked="props.notifications.commit_summary"
                            @update:checked="
                                (value: boolean) =>
                                    updatePreference('commit_summary', value)
                            "
                        />
                    </SettingRow>

                    <SettingRow
                        title="Repository alerts"
                        description="Receive alerts about significant changes in repository activity"
                    >
                        <Switch
                            :checked="props.notifications.repository_alerts"
                            @update:checked="
                                (value: boolean) =>
                                    updatePreference('repository_alerts', value)
                            "
                        />
                    </SettingRow>
                </SettingSection>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import { Bell, Database, Github, Lock, Sun, User } from 'lucide-vue-next';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import { SettingCard, StatusType } from '@/components/settings';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editAppearance } from '@/routes/appearance';
import { index as dataIndex } from '@/routes/data';
import { edit as editNotifications } from '@/routes/notifications';
import { edit as editProfile } from '@/routes/profile';
import { index as settingsIndex } from '@/routes/settings';
import { show as showTwoFactor } from '@/routes/two-factor';
import { type BreadcrumbItem } from '@/types';

interface SettingsOverview {
    profile: {
        verified: boolean;
    };
    security: {
        two_factor_enabled: boolean;
    };
    github: {
        connected: boolean;
        username: string | null;
    };
    notifications: {
        configured: boolean;
    };
    appearance: {
        theme: string;
    };
}

interface Props {
    overview: SettingsOverview;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: settingsIndex.url(),
    },
];

interface SettingCardData {
    title: string;
    description: string;
    href: string;
    icon: LucideIcon;
    status?: {
        type: StatusType;
        label: string;
    };
}

const settingCards = computed<SettingCardData[]>(() => [
    {
        title: 'Profile',
        description: 'Manage your name and email address',
        href: editProfile.url(),
        icon: User,
        status: {
            type: props.overview.profile.verified ? 'enabled' : 'warning',
            label: props.overview.profile.verified ? 'Verified' : 'Unverified',
        },
    },
    {
        title: 'Security',
        description: 'Password and two-factor authentication',
        href: showTwoFactor.url(),
        icon: Lock,
        status: {
            type: props.overview.security.two_factor_enabled
                ? 'enabled'
                : 'disabled',
            label: props.overview.security.two_factor_enabled
                ? '2FA enabled'
                : '2FA disabled',
        },
    },
    {
        title: 'GitHub',
        description: 'Manage your GitHub connection',
        href: editProfile.url(),
        icon: Github,
        status: props.overview.github.connected
            ? {
                  type: 'enabled',
                  label: `@${props.overview.github.username}`,
              }
            : {
                  type: 'disabled',
                  label: 'Not connected',
              },
    },
    {
        title: 'Notifications',
        description: 'Configure email and alert preferences',
        href: editNotifications.url(),
        icon: Bell,
        status: {
            type: props.overview.notifications.configured ? 'enabled' : 'info',
            label: props.overview.notifications.configured
                ? 'Configured'
                : 'Default',
        },
    },
    {
        title: 'Appearance',
        description: 'Customize the look and feel',
        href: editAppearance.url(),
        icon: Sun,
        status: {
            type: 'info',
            label:
                props.overview.appearance.theme === 'system'
                    ? 'System'
                    : props.overview.appearance.theme === 'dark'
                      ? 'Dark'
                      : 'Light',
        },
    },
    {
        title: 'Data & Privacy',
        description: 'Export your data or delete your account',
        href: dataIndex.url(),
        icon: Database,
        status: {
            type: 'info',
            label: 'Manage data',
        },
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Settings" />

        <div class="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your account settings and preferences"
            />

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <SettingCard
                    v-for="card in settingCards"
                    :key="card.title"
                    :title="card.title"
                    :description="card.description"
                    :href="card.href"
                    :icon="card.icon"
                    :status="card.status"
                />
            </div>
        </div>
    </AppLayout>
</template>

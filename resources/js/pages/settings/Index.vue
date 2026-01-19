<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import {
    Bell,
    Database,
    Github,
    Settings,
    Shield,
    Sun,
    User,
} from 'lucide-vue-next';
import { computed } from 'vue';

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
        icon: Shield,
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

        <div class="px-4 py-8 lg:px-6">
            <!-- Editorial header with decorative elements -->
            <header class="relative mb-12">
                <!-- Decorative background pattern -->
                <div
                    class="pointer-events-none absolute -top-4 right-0 h-32 w-32 opacity-[0.03] lg:h-48 lg:w-48"
                >
                    <svg viewBox="0 0 100 100" class="h-full w-full">
                        <circle
                            cx="50"
                            cy="50"
                            r="40"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="0.5"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            r="30"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="0.5"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            r="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="0.5"
                        />
                    </svg>
                </div>

                <div class="relative">
                    <div class="flex items-center gap-3 text-muted-foreground">
                        <Settings class="h-4 w-4" />
                        <span
                            class="text-sm font-medium tracking-wide uppercase"
                            >Account</span
                        >
                    </div>
                    <h1
                        class="mt-3 font-display text-4xl font-semibold tracking-tight text-foreground lg:text-5xl"
                    >
                        Settings
                    </h1>
                    <p class="mt-3 max-w-lg text-lg text-muted-foreground">
                        Manage your account settings and preferences to
                        personalize your experience
                    </p>
                </div>

                <!-- Decorative accent line -->
                <div
                    class="mt-8 h-px w-full bg-gradient-to-r from-[hsl(32_95%_55%/0.4)] via-border to-transparent"
                />
            </header>

            <!-- Cards grid with staggered animation -->
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <SettingCard
                    v-for="(card, index) in settingCards"
                    :key="card.title"
                    :title="card.title"
                    :description="card.description"
                    :href="card.href"
                    :icon="card.icon"
                    :status="card.status"
                    :style="{ animationDelay: `${index * 50}ms` }"
                    class="animate-in duration-500 fill-mode-both fade-in slide-in-from-bottom-4"
                />
            </div>

            <!-- Bottom decorative element -->
            <div class="mt-16 flex items-center justify-center gap-3">
                <div class="h-1 w-1 rounded-full bg-[hsl(32_95%_55%/0.4)]" />
                <div
                    class="h-1.5 w-1.5 rounded-full bg-[hsl(32_95%_55%/0.6)]"
                />
                <div class="h-1 w-1 rounded-full bg-[hsl(32_95%_55%/0.4)]" />
            </div>
        </div>
    </AppLayout>
</template>

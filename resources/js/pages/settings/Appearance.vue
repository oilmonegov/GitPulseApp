<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Palette } from 'lucide-vue-next';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { SettingSection } from '@/components/settings';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <h1 class="sr-only">Appearance Settings</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <HeadingSmall
                    title="Appearance settings"
                    description="Update your account's appearance settings"
                />

                <SettingSection
                    title="Theme"
                    description="Choose how GitPulse looks to you"
                    :icon="Palette"
                >
                    <div class="py-5">
                        <AppearanceTabs />
                    </div>
                </SettingSection>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

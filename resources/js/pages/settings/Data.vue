<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Database, Download, FileJson, FileSpreadsheet } from 'lucide-vue-next';
import { ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { SettingRow, SettingSection } from '@/components/settings';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index as dataIndex, exportMethod } from '@/routes/data';
import { type BreadcrumbItem } from '@/types';

interface Props {
    stats: {
        total_commits: number;
        total_repositories: number;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data & Privacy',
        href: dataIndex.url(),
    },
];

const isExporting = ref<'json' | 'csv' | null>(null);

async function exportData(format: 'json' | 'csv') {
    isExporting.value = format;

    try {
        // Create a hidden form to trigger the download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = exportMethod.url();

        // Add CSRF token
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        // Add format
        const formatInput = document.createElement('input');
        formatInput.type = 'hidden';
        formatInput.name = 'format';
        formatInput.value = format;
        form.appendChild(formatInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    } finally {
        // Reset after a brief delay to show feedback
        setTimeout(() => {
            isExporting.value = null;
        }, 1000);
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Data & Privacy" />

        <h1 class="sr-only">Data & Privacy Settings</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <HeadingSmall
                    title="Data & Privacy"
                    description="Export your data or manage your account"
                />

                <SettingSection
                    title="Export Data"
                    description="Download a copy of your data"
                    :icon="Download"
                >
                    <SettingRow
                        title="Export as JSON"
                        description="Complete export including commits, statistics, and profile data"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="isExporting !== null"
                            @click="exportData('json')"
                        >
                            <FileJson class="h-4 w-4" />
                            {{
                                isExporting === 'json'
                                    ? 'Exporting...'
                                    : 'Export JSON'
                            }}
                        </Button>
                    </SettingRow>

                    <SettingRow
                        title="Export as CSV"
                        description="Commit history in spreadsheet format"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="isExporting !== null"
                            @click="exportData('csv')"
                        >
                            <FileSpreadsheet class="h-4 w-4" />
                            {{
                                isExporting === 'csv'
                                    ? 'Exporting...'
                                    : 'Export CSV'
                            }}
                        </Button>
                    </SettingRow>
                </SettingSection>

                <SettingSection
                    title="Your Data"
                    description="Summary of your stored data"
                    :icon="Database"
                >
                    <SettingRow
                        title="Total commits tracked"
                        :description="`${stats.total_commits.toLocaleString()} commits`"
                    />

                    <SettingRow
                        title="Connected repositories"
                        :description="`${stats.total_repositories.toLocaleString()} repositories`"
                    />
                </SettingSection>

                <DeleteUser />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

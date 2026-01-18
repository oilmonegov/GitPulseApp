<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { Code2, GitCommitHorizontal, TrendingUp } from 'lucide-vue-next';

import {
    CommitsOverTimeChart,
    CommitTypeDonut,
    StatCard,
} from '@/components/Dashboard';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type {
    BreadcrumbItem,
    CommitOverTime,
    CommitTypeDistribution,
    DashboardSummary,
} from '@/types';

defineProps<{
    summary?: DashboardSummary;
    commitsOverTime?: CommitOverTime[];
    commitTypeDistribution?: CommitTypeDistribution[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatImpact = (value: number | undefined): string => {
    if (value === undefined) return '—';
    return value.toFixed(1);
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid gap-4 md:grid-cols-3">
                        <StatCard
                            label="Total Commits"
                            :icon="GitCommitHorizontal"
                            loading
                        />
                        <StatCard
                            label="Average Impact"
                            :icon="TrendingUp"
                            loading
                        />
                        <StatCard label="Lines Changed" :icon="Code2" loading />
                    </div>
                </template>

                <div class="grid gap-4 md:grid-cols-3">
                    <StatCard
                        label="Total Commits"
                        :value="summary?.total_commits"
                        :icon="GitCommitHorizontal"
                    />
                    <StatCard
                        label="Average Impact"
                        :value="formatImpact(summary?.average_impact)"
                        :icon="TrendingUp"
                    />
                    <StatCard
                        label="Lines Changed"
                        :value="summary?.lines_changed"
                        :icon="Code2"
                    />
                </div>
            </Deferred>

            <div class="grid flex-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <Deferred data="commitsOverTime">
                        <template #fallback>
                            <CommitsOverTimeChart loading />
                        </template>

                        <CommitsOverTimeChart :data="commitsOverTime" />
                    </Deferred>
                </div>

                <div>
                    <Deferred data="commitTypeDistribution">
                        <template #fallback>
                            <CommitTypeDonut loading />
                        </template>

                        <CommitTypeDonut :data="commitTypeDistribution" />
                    </Deferred>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

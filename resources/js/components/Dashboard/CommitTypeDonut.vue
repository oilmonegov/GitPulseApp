<script setup lang="ts">
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useChartColors } from '@/composables/useChartColors';
import type { CommitTypeDistribution } from '@/types';

import ChartSkeleton from './ChartSkeleton.vue';

ChartJS.register(ArcElement, Tooltip, Legend);

interface Props {
    data?: CommitTypeDistribution[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const { colors, themeKey } = useChartColors();

const chartData = computed(() => {
    if (!props.data || props.data.length === 0) {
        return {
            labels: [],
            datasets: [],
        };
    }

    return {
        labels: props.data.map((item) => item.label),
        datasets: [
            {
                data: props.data.map((item) => item.count),
                backgroundColor: props.data.map((item) => item.color),
                borderColor: colors.value.background,
                borderWidth: 3,
                hoverOffset: 4,
            },
        ],
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: colors.value.popover,
            titleColor: colors.value.popoverForeground,
            bodyColor: colors.value.popoverForeground,
            borderColor: colors.value.border,
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
            callbacks: {
                label: (item: { label: string; raw: unknown }) => {
                    const value = item.raw as number;
                    return `${item.label}: ${value} commits`;
                },
            },
        },
    },
}));

const totalCommits = computed(() => {
    if (!props.data) return 0;
    return props.data.reduce((sum, item) => sum + item.count, 0);
});

const hasData = computed(() => {
    return props.data && props.data.length > 0;
});
</script>

<template>
    <Card class="flex flex-col">
        <CardHeader class="pb-2">
            <CardTitle class="text-base font-medium">
                Commit Type Distribution
            </CardTitle>
        </CardHeader>
        <CardContent class="flex-1 pb-4">
            <template v-if="loading">
                <ChartSkeleton type="donut" class="h-[300px] p-0" />
            </template>
            <template v-else-if="!hasData">
                <div
                    class="flex h-[300px] flex-col items-center justify-center gap-2"
                >
                    <p class="text-sm text-muted-foreground">No commits yet</p>
                </div>
            </template>
            <template v-else>
                <div class="relative h-[200px]">
                    <Doughnut
                        :key="themeKey"
                        :data="chartData"
                        :options="chartOptions"
                    />
                    <div
                        class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <span class="text-3xl font-bold">
                            {{ totalCommits }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            commits
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-2">
                    <div
                        v-for="item in data"
                        :key="item.type"
                        class="flex items-center gap-2"
                    >
                        <span
                            class="h-3 w-3 rounded-full"
                            :style="{ backgroundColor: item.color }"
                        />
                        <span class="text-sm text-muted-foreground">
                            {{ item.label }} ({{ item.count }})
                        </span>
                    </div>
                </div>
            </template>
        </CardContent>
    </Card>
</template>

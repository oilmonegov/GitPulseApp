<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { format, parseISO } from 'date-fns';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { CommitOverTime } from '@/types';

import ChartSkeleton from './ChartSkeleton.vue';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

interface Props {
    data?: CommitOverTime[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const chartData = computed(() => {
    if (!props.data) return { labels: [], datasets: [] };

    return {
        labels: props.data.map((item) => format(parseISO(item.date), 'MMM d')),
        datasets: [
            {
                label: 'Commits',
                data: props.data.map((item) => item.count),
                borderColor: 'hsl(var(--primary))',
                backgroundColor: 'hsl(var(--primary) / 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointBackgroundColor: 'hsl(var(--primary))',
                pointBorderColor: 'hsl(var(--background))',
                pointBorderWidth: 2,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'hsl(var(--popover))',
            titleColor: 'hsl(var(--popover-foreground))',
            bodyColor: 'hsl(var(--popover-foreground))',
            borderColor: 'hsl(var(--border))',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
            displayColors: false,
            callbacks: {
                title: (items: { label: string }[]) => items[0]?.label ?? '',
                label: (item: { raw: unknown }) => {
                    const value = item.raw as number;
                    return `${value} commit${value !== 1 ? 's' : ''}`;
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: 'hsl(var(--muted-foreground))',
                maxTicksLimit: 7,
            },
            border: {
                display: false,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'hsl(var(--border) / 0.5)',
            },
            ticks: {
                color: 'hsl(var(--muted-foreground))',
                stepSize: 1,
            },
            border: {
                display: false,
            },
        },
    },
};

const totalCommits = computed(() => {
    if (!props.data) return 0;
    return props.data.reduce((sum, item) => sum + item.count, 0);
});
</script>

<template>
    <Card class="flex flex-col">
        <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-base font-medium">
                Commits Over Time
            </CardTitle>
            <span v-if="!loading" class="text-sm text-muted-foreground">
                {{ totalCommits }} total
            </span>
        </CardHeader>
        <CardContent class="flex-1 pb-4">
            <template v-if="loading">
                <ChartSkeleton type="line" class="h-[300px] p-0" />
            </template>
            <template v-else>
                <div class="h-[300px]">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
            </template>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { cn } from '@/lib/utils';

export type StatusType = 'enabled' | 'disabled' | 'warning' | 'info';

interface Props {
    status: StatusType;
    label: string;
    showDot?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showDot: true,
});

const statusClasses = computed(() => {
    const base =
        'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium';

    switch (props.status) {
        case 'enabled':
            return cn(
                base,
                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
            );
        case 'disabled':
            return cn(
                base,
                'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
            );
        case 'warning':
            return cn(
                base,
                'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
            );
        case 'info':
        default:
            return cn(
                base,
                'bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-400',
            );
    }
});

const dotClasses = computed(() => {
    switch (props.status) {
        case 'enabled':
            return 'bg-emerald-500';
        case 'disabled':
            return 'bg-neutral-400';
        case 'warning':
            return 'bg-amber-500';
        case 'info':
        default:
            return 'bg-sky-500';
    }
});
</script>

<template>
    <span :class="statusClasses">
        <span
            v-if="showDot"
            :class="cn('h-1.5 w-1.5 rounded-full', dotClasses)"
        />
        {{ label }}
    </span>
</template>

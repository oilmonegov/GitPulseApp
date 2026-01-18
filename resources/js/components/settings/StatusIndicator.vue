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
        'inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs font-medium tracking-wide transition-colors duration-200';

    switch (props.status) {
        case 'enabled':
            return cn(
                base,
                'bg-emerald-50/80 text-emerald-700 ring-1 ring-emerald-200/50 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800/30',
            );
        case 'disabled':
            return cn(
                base,
                'bg-neutral-100/80 text-neutral-500 ring-1 ring-neutral-200/50 dark:bg-neutral-800/40 dark:text-neutral-400 dark:ring-neutral-700/30',
            );
        case 'warning':
            return cn(
                base,
                'bg-amber-50/80 text-amber-700 ring-1 ring-amber-200/50 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-800/30',
            );
        case 'info':
        default:
            return cn(
                base,
                'bg-[hsl(32_95%_55%/0.08)] text-[hsl(32_70%_40%)] ring-1 ring-[hsl(32_95%_55%/0.2)] dark:bg-[hsl(32_95%_55%/0.12)] dark:text-[hsl(32_95%_70%)] dark:ring-[hsl(32_95%_55%/0.25)]',
            );
    }
});

const dotClasses = computed(() => {
    const base = 'h-1.5 w-1.5 rounded-full animate-pulse';

    switch (props.status) {
        case 'enabled':
            return cn(base, 'bg-emerald-500 shadow-sm shadow-emerald-500/50');
        case 'disabled':
            return cn(base, 'animate-none bg-neutral-400');
        case 'warning':
            return cn(base, 'bg-amber-500 shadow-sm shadow-amber-500/50');
        case 'info':
        default:
            return cn(
                base,
                'animate-none bg-[hsl(32_95%_55%)] shadow-sm shadow-[hsl(32_95%_55%/0.5)]',
            );
    }
});
</script>

<template>
    <span :class="statusClasses">
        <span v-if="showDot" :class="dotClasses" />
        <span class="truncate">{{ label }}</span>
    </span>
</template>

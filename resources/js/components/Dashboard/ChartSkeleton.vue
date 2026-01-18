<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
    type?: 'line' | 'donut';
}

const props = withDefaults(defineProps<Props>(), {
    type: 'line',
});
</script>

<template>
    <div :class="cn('flex h-full w-full flex-col gap-4 p-6', props.class)">
        <div class="flex items-center justify-between">
            <Skeleton class="h-6 w-32" />
            <Skeleton class="h-4 w-20" />
        </div>

        <template v-if="type === 'line'">
            <div class="flex flex-1 items-end gap-1">
                <Skeleton
                    v-for="i in 30"
                    :key="i"
                    class="w-full"
                    :style="{
                        height: `${20 + Math.random() * 60}%`,
                    }"
                />
            </div>
        </template>

        <template v-else-if="type === 'donut'">
            <div class="flex flex-1 items-center justify-center">
                <Skeleton class="h-48 w-48 rounded-full" />
            </div>
            <div class="flex flex-wrap justify-center gap-4">
                <Skeleton v-for="i in 4" :key="i" class="h-4 w-16" />
            </div>
        </template>
    </div>
</template>

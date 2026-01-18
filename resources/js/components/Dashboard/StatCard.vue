<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

interface Props {
    label: string;
    value?: string | number;
    icon: LucideIcon;
    loading?: boolean;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const formatValue = (val: string | number | undefined): string => {
    if (val === undefined) return '—';
    if (typeof val === 'number') {
        return val.toLocaleString();
    }
    return val;
};
</script>

<template>
    <Card :class="cn('relative overflow-hidden', props.class)">
        <CardContent class="p-6">
            <div class="flex items-center justify-between">
                <div class="space-y-2">
                    <p
                        class="text-sm font-medium tracking-tight text-muted-foreground"
                    >
                        {{ label }}
                    </p>
                    <template v-if="loading">
                        <Skeleton class="h-8 w-24" />
                    </template>
                    <template v-else>
                        <p class="text-3xl font-bold tracking-tight">
                            {{ formatValue(value) }}
                        </p>
                    </template>
                </div>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary"
                >
                    <component :is="icon" class="h-6 w-6" />
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import { ChevronRight } from 'lucide-vue-next';

import StatusIndicator, { StatusType } from './StatusIndicator.vue';

interface Props {
    title: string;
    description: string;
    href: string;
    icon: LucideIcon;
    status?: {
        type: StatusType;
        label: string;
    };
}

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        class="group flex items-center justify-between rounded-xl border border-border/50 bg-card p-4 shadow-sm transition-all duration-200 hover:border-border hover:shadow-md"
    >
        <div class="flex items-start gap-4">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted transition-colors duration-200 group-hover:bg-muted/80"
            >
                <component
                    :is="icon"
                    class="h-5 w-5 text-muted-foreground transition-colors duration-200 group-hover:text-foreground"
                />
            </div>
            <div class="space-y-1">
                <h3
                    class="text-sm font-medium text-foreground transition-colors duration-200"
                >
                    {{ title }}
                </h3>
                <p class="text-sm text-muted-foreground">{{ description }}</p>
                <StatusIndicator
                    v-if="status"
                    :status="status.type"
                    :label="status.label"
                    class="mt-2"
                />
            </div>
        </div>
        <ChevronRight
            class="h-5 w-5 shrink-0 text-muted-foreground transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-foreground"
        />
    </Link>
</template>

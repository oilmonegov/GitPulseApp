<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import { ArrowUpRight } from 'lucide-vue-next';

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
        class="group relative flex flex-col overflow-hidden rounded-2xl border border-border/60 bg-card p-6 shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:border-[hsl(32_95%_55%/0.3)] hover:shadow-xl hover:shadow-[hsl(32_95%_55%/0.08)]"
    >
        <!-- Decorative gradient overlay on hover -->
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[hsl(32_95%_55%/0)] to-[hsl(32_95%_55%/0)] opacity-0 transition-opacity duration-300 group-hover:from-[hsl(32_95%_55%/0.04)] group-hover:to-transparent group-hover:opacity-100"
        />

        <!-- Top row: Icon and arrow -->
        <div class="relative mb-5 flex items-start justify-between">
            <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-muted to-muted/60 ring-1 ring-border/50 transition-all duration-300 group-hover:from-[hsl(32_95%_55%/0.15)] group-hover:to-[hsl(32_95%_55%/0.05)] group-hover:ring-[hsl(32_95%_55%/0.3)]"
            >
                <component
                    :is="icon"
                    class="h-5 w-5 text-muted-foreground transition-colors duration-300 group-hover:text-[hsl(32_95%_55%)]"
                />
            </div>
            <div
                class="flex h-8 w-8 items-center justify-center rounded-full bg-muted/50 opacity-0 transition-all duration-300 group-hover:opacity-100"
            >
                <ArrowUpRight
                    class="h-4 w-4 -translate-x-0.5 translate-y-0.5 text-muted-foreground transition-transform duration-300 group-hover:translate-x-0 group-hover:translate-y-0 group-hover:text-foreground"
                />
            </div>
        </div>

        <!-- Content -->
        <div class="relative flex flex-1 flex-col">
            <h3
                class="font-display text-lg font-semibold tracking-tight text-foreground transition-colors duration-200"
            >
                {{ title }}
            </h3>
            <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                {{ description }}
            </p>

            <!-- Status badge -->
            <div class="mt-4 flex items-center">
                <StatusIndicator
                    v-if="status"
                    :status="status.type"
                    :label="status.label"
                />
            </div>
        </div>

        <!-- Bottom accent line -->
        <div
            class="absolute bottom-0 left-0 h-0.5 w-0 bg-gradient-to-r from-[hsl(32_95%_55%)] to-[hsl(35_90%_60%)] transition-all duration-500 ease-out group-hover:w-full"
        />
    </Link>
</template>

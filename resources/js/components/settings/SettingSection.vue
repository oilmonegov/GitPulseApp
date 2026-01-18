<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';

interface Props {
    title: string;
    description?: string;
    icon?: LucideIcon;
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-5">
        <!-- Section header with editorial styling -->
        <header class="flex items-start gap-4">
            <div
                v-if="icon"
                class="relative flex h-10 w-10 shrink-0 items-center justify-center"
            >
                <!-- Decorative background circle -->
                <div
                    class="absolute inset-0 rounded-xl bg-gradient-to-br from-[hsl(32_95%_55%/0.12)] to-[hsl(32_95%_55%/0.04)] ring-1 ring-[hsl(32_95%_55%/0.15)]"
                />
                <component
                    :is="icon"
                    class="relative h-4.5 w-4.5 text-[hsl(32_80%_45%)] dark:text-[hsl(32_95%_65%)]"
                />
            </div>
            <div class="flex-1 pt-0.5">
                <h3
                    class="font-display text-base font-semibold tracking-tight text-foreground"
                >
                    {{ title }}
                </h3>
                <p
                    v-if="description"
                    class="mt-0.5 text-sm leading-relaxed text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
        </header>

        <!-- Content container with refined styling -->
        <div
            class="relative overflow-hidden rounded-2xl border border-border/60 bg-card shadow-sm transition-shadow duration-200 hover:shadow-md"
        >
            <!-- Subtle top accent line -->
            <div
                class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[hsl(32_95%_55%/0.3)] to-transparent"
            />

            <div class="divide-y divide-border/60 px-5">
                <slot />
            </div>
        </div>
    </div>
</template>

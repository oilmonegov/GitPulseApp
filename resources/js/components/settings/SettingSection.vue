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
    <div class="space-y-4">
        <header class="flex items-start gap-3">
            <div
                v-if="icon"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted"
            >
                <component :is="icon" class="h-4 w-4 text-muted-foreground" />
            </div>
            <div>
                <h3 class="text-base font-medium">{{ title }}</h3>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
        </header>
        <div
            class="divide-y divide-border rounded-xl border border-border/50 bg-card px-4 shadow-sm"
        >
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div
        class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
    >
        <div class="space-y-0.5">
            <p class="text-sm leading-none font-medium">{{ title }}</p>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="shrink-0">
            <slot />
        </div>
    </div>
</template>

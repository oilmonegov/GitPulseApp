<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header class="space-y-1">
        <h3
            class="font-display text-lg font-semibold tracking-tight text-foreground"
        >
            {{ title }}
        </h3>
        <p
            v-if="description"
            class="text-sm leading-relaxed text-muted-foreground"
        >
            {{ description }}
        </p>
    </header>
</template>

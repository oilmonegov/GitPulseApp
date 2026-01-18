<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div
        class="group flex items-center justify-between gap-6 py-5 transition-colors duration-150 first:pt-5 last:pb-5"
    >
        <div class="min-w-0 flex-1 space-y-1">
            <p
                class="text-sm leading-none font-medium text-foreground transition-colors duration-150"
            >
                {{ title }}
            </p>
            <p
                v-if="description"
                class="text-sm leading-relaxed text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <div
            class="shrink-0 transition-transform duration-150 group-hover:scale-[1.02]"
        >
            <slot />
        </div>
    </div>
</template>

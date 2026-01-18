<script setup lang="ts">
import type { ToasterProps } from 'vue-sonner';
import { Toaster } from 'vue-sonner';

import { useAppearance } from '@/composables/useAppearance';

const props = withDefaults(defineProps<ToasterProps>(), {
    position: 'bottom-right',
    duration: 4000,
    gap: 12,
});

const { appearance } = useAppearance();
</script>

<template>
    <Toaster
        v-bind="props"
        :theme="appearance === 'system' ? 'system' : appearance"
        :toast-options="{
            classes: {
                toast: 'group toast group-[.toaster]:border-border/60 group-[.toaster]:bg-card group-[.toaster]:text-foreground group-[.toaster]:shadow-lg group-[.toaster]:rounded-xl',
                title: 'group-[.toast]:text-foreground group-[.toast]:font-medium',
                description: 'group-[.toast]:text-muted-foreground',
                actionButton:
                    'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground group-[.toast]:rounded-lg group-[.toast]:font-medium',
                cancelButton:
                    'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground group-[.toast]:rounded-lg',
                closeButton:
                    'group-[.toast]:bg-background group-[.toast]:text-foreground group-[.toast]:border-border/60 group-[.toast]:hover:bg-muted',
                success:
                    'group-[.toaster]:border-emerald-500/30 group-[.toaster]:bg-emerald-50 group-[.toaster]:text-emerald-900 dark:group-[.toaster]:bg-emerald-950/50 dark:group-[.toaster]:text-emerald-100 dark:group-[.toaster]:border-emerald-500/40',
                error: 'group-[.toaster]:border-red-500/30 group-[.toaster]:bg-red-50 group-[.toaster]:text-red-900 dark:group-[.toaster]:bg-red-950/50 dark:group-[.toaster]:text-red-100 dark:group-[.toaster]:border-red-500/40',
                warning:
                    'group-[.toaster]:border-amber-500/30 group-[.toaster]:bg-amber-50 group-[.toaster]:text-amber-900 dark:group-[.toaster]:bg-amber-950/50 dark:group-[.toaster]:text-amber-100 dark:group-[.toaster]:border-amber-500/40',
                info: 'group-[.toaster]:border-blue-500/30 group-[.toaster]:bg-blue-50 group-[.toaster]:text-blue-900 dark:group-[.toaster]:bg-blue-950/50 dark:group-[.toaster]:text-blue-100 dark:group-[.toaster]:border-blue-500/40',
            },
        }"
    />
</template>

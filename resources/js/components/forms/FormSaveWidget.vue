<script setup lang="ts">
import { RotateCcw, Save } from 'lucide-vue-next';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

interface Props {
    isDirty: boolean;
    processing?: boolean;
    recentlySuccessful?: boolean;
    saveLabel?: string;
    savingLabel?: string;
    savedLabel?: string;
    discardLabel?: string;
    position?: 'bottom' | 'top';
}

const props = withDefaults(defineProps<Props>(), {
    processing: false,
    recentlySuccessful: false,
    saveLabel: 'Save changes',
    savingLabel: 'Saving...',
    savedLabel: 'Saved',
    discardLabel: 'Discard',
    position: 'bottom',
});

const emit = defineEmits<{
    save: [];
    discard: [];
}>();

const isVisible = computed(
    () => props.isDirty || props.processing || props.recentlySuccessful,
);

const buttonText = computed(() => {
    if (props.processing) {
        return props.savingLabel;
    }
    if (props.recentlySuccessful) {
        return props.savedLabel;
    }
    return props.saveLabel;
});
</script>

<template>
    <Teleport to="body">
        <div
            :data-visible="isVisible"
            :class="[
                'fixed left-1/2 z-50 -translate-x-1/2 px-4 transition-all duration-300 ease-out',
                position === 'bottom'
                    ? 'bottom-6 translate-y-[calc(100%+2rem)] data-[visible=true]:translate-y-0'
                    : 'top-6 -translate-y-[calc(100%+2rem)] data-[visible=true]:translate-y-0',
            ]"
        >
            <div
                class="flex items-center gap-3 rounded-2xl border border-border/60 bg-card/95 px-4 py-3 shadow-lg backdrop-blur-md transition-all duration-300"
                :class="{
                    'ring-2 ring-[hsl(32_95%_55%/0.3)]': isDirty && !processing,
                    'ring-2 ring-emerald-500/30': recentlySuccessful,
                }"
            >
                <!-- Status indicator -->
                <div class="flex items-center gap-2">
                    <div
                        class="h-2 w-2 rounded-full transition-colors duration-200"
                        :class="{
                            'animate-pulse bg-[hsl(32_95%_55%)]':
                                isDirty && !processing,
                            'bg-muted-foreground/50': processing,
                            'bg-emerald-500': recentlySuccessful,
                        }"
                    />
                    <span class="text-sm font-medium text-muted-foreground">
                        {{
                            recentlySuccessful
                                ? 'Changes saved'
                                : processing
                                  ? 'Saving'
                                  : 'Unsaved changes'
                        }}
                    </span>
                </div>

                <div class="mx-2 h-6 w-px bg-border/60" aria-hidden="true" />

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="processing"
                        class="h-8 gap-1.5 px-3 text-muted-foreground hover:text-foreground"
                        @click="emit('discard')"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        {{ discardLabel }}
                    </Button>

                    <Button
                        type="button"
                        size="sm"
                        :disabled="processing"
                        class="h-8 gap-1.5 px-4 shadow-sm"
                        @click="emit('save')"
                    >
                        <Spinner v-if="processing" class="h-3.5 w-3.5" />
                        <Save v-else class="h-3.5 w-3.5" />
                        {{ buttonText }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

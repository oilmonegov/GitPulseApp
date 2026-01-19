import type { InertiaForm } from '@inertiajs/vue3';
import { computed, type ComputedRef, type Ref, ref, watch } from 'vue';

import { toast } from '@/components/ui/sonner';

type WayfinderRoute = {
    url: string;
    method: string;
};

interface UseFormSaveWidgetOptions<T extends Record<string, unknown>> {
    form: InertiaForm<T>;
    route: WayfinderRoute | (() => WayfinderRoute);
    successMessage?: string;
    errorMessage?: string;
    preserveScroll?: boolean;
    onSuccess?: () => void;
    onError?: () => void;
}

interface UseFormSaveWidgetReturn {
    isDirty: ComputedRef<boolean>;
    processing: ComputedRef<boolean>;
    recentlySuccessful: Ref<boolean>;
    save: () => void;
    discard: () => void;
}

export function useFormSaveWidget<T extends Record<string, unknown>>(
    options: UseFormSaveWidgetOptions<T>,
): UseFormSaveWidgetReturn {
    const {
        form,
        route,
        successMessage = 'Changes saved successfully',
        errorMessage = 'Failed to save changes',
        preserveScroll = true,
        onSuccess,
        onError,
    } = options;

    const recentlySuccessful = ref(false);
    let successTimeout: ReturnType<typeof setTimeout> | null = null;

    const isDirty = computed(() => form.isDirty);
    const processing = computed(() => form.processing);

    // Clear timeout on unmount
    watch(
        () => form.processing,
        (isProcessing, wasProcessing) => {
            if (wasProcessing && !isProcessing && !form.hasErrors) {
                recentlySuccessful.value = true;
                if (successTimeout) {
                    clearTimeout(successTimeout);
                }
                successTimeout = setTimeout(() => {
                    recentlySuccessful.value = false;
                }, 2000);
            }
        },
    );

    function save() {
        const routeValue = typeof route === 'function' ? route() : route;

        form.submit(
            routeValue.method as 'get' | 'post' | 'put' | 'patch' | 'delete',
            routeValue.url,
            {
                preserveScroll,
                onSuccess: () => {
                    toast.success(successMessage);
                    onSuccess?.();
                },
                onError: () => {
                    toast.error(errorMessage);
                    onError?.();
                },
            },
        );
    }

    function discard() {
        form.reset();
        form.clearErrors();
    }

    return {
        isDirty,
        processing,
        recentlySuccessful,
        save,
        discard,
    };
}

import { computed, onMounted, onUnmounted, ref } from 'vue';

/**
 * Composable that provides reactive chart colors based on current theme.
 *
 * Chart.js renders to canvas and evaluates CSS custom properties once at render time.
 * This composable reads computed CSS values and reacts to theme changes via MutationObserver.
 *
 * Usage:
 * ```ts
 * const { colors, themeKey } = useChartColors();
 * // Use themeKey as :key on chart component to force re-render on theme change
 * // Use colors.value.primary, colors.value.border, etc. for chart options
 * ```
 */
export function useChartColors() {
    const themeKey = ref(0);

    const getCssVariable = (variable: string): string => {
        if (typeof window === 'undefined') return '';
        return getComputedStyle(document.documentElement)
            .getPropertyValue(variable)
            .trim();
    };

    const getColors = () => ({
        // CSS variables already contain complete hsl() values, so we use them directly
        primary: getCssVariable('--primary'),
        primaryForeground: getCssVariable('--primary-foreground'),
        background: getCssVariable('--background'),
        foreground: getCssVariable('--foreground'),
        muted: getCssVariable('--muted'),
        mutedForeground: getCssVariable('--muted-foreground'),
        border: getCssVariable('--border'),
        popover: getCssVariable('--popover'),
        popoverForeground: getCssVariable('--popover-foreground'),
        chart1: getCssVariable('--chart-1'),
        chart2: getCssVariable('--chart-2'),
        chart3: getCssVariable('--chart-3'),
        chart4: getCssVariable('--chart-4'),
        chart5: getCssVariable('--chart-5'),
    });

    const colors = ref(getColors());

    const updateColors = () => {
        colors.value = getColors();
        themeKey.value++;
    };

    let observer: MutationObserver | null = null;

    onMounted(() => {
        // Initial color fetch
        updateColors();

        // Watch for class changes on document element (dark mode toggle)
        observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (
                    mutation.type === 'attributes' &&
                    mutation.attributeName === 'class'
                ) {
                    // Small delay to ensure CSS variables have updated
                    requestAnimationFrame(() => {
                        updateColors();
                    });
                }
            }
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    });

    onUnmounted(() => {
        observer?.disconnect();
    });

    const isDark = computed(() => {
        if (typeof document === 'undefined') return false;
        return document.documentElement.classList.contains('dark');
    });

    return {
        colors,
        themeKey,
        isDark,
        updateColors,
    };
}

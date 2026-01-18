<script setup lang="ts">
import { Check, Monitor, Moon, Sun } from 'lucide-vue-next';

import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    {
        value: 'light',
        Icon: Sun,
        label: 'Light',
        description: 'Clean and bright',
    },
    {
        value: 'dark',
        Icon: Moon,
        label: 'Dark',
        description: 'Easy on the eyes',
    },
    {
        value: 'system',
        Icon: Monitor,
        label: 'System',
        description: 'Match your device',
    },
] as const;
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <button
                v-for="{ value, Icon, label, description } in tabs"
                :key="value"
                @click="updateAppearance(value)"
                :class="[
                    'group relative flex flex-col items-start rounded-2xl border p-5 text-left transition-all duration-200',
                    appearance === value
                        ? 'border-[hsl(32_95%_55%/0.5)] bg-gradient-to-br from-[hsl(32_95%_55%/0.08)] to-transparent ring-1 ring-[hsl(32_95%_55%/0.2)]'
                        : 'border-border/60 bg-card hover:border-border hover:bg-muted/30',
                ]"
            >
                <!-- Check indicator -->
                <div
                    :class="[
                        'absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full transition-all duration-200',
                        appearance === value
                            ? 'bg-[hsl(32_95%_55%)] text-white'
                            : 'bg-muted text-transparent',
                    ]"
                >
                    <Check class="h-3 w-3" />
                </div>

                <!-- Icon -->
                <div
                    :class="[
                        'mb-4 flex h-12 w-12 items-center justify-center rounded-xl transition-colors duration-200',
                        appearance === value
                            ? 'bg-[hsl(32_95%_55%/0.15)] text-[hsl(32_80%_45%)] dark:text-[hsl(32_95%_65%)]'
                            : 'bg-muted/60 text-muted-foreground group-hover:bg-muted group-hover:text-foreground',
                    ]"
                >
                    <component :is="Icon" class="h-5 w-5" />
                </div>

                <!-- Text content -->
                <span
                    :class="[
                        'font-display text-sm font-semibold transition-colors duration-200',
                        appearance === value
                            ? 'text-foreground'
                            : 'text-foreground',
                    ]"
                >
                    {{ label }}
                </span>
                <span class="mt-0.5 text-xs text-muted-foreground">
                    {{ description }}
                </span>

                <!-- Preview mockup -->
                <div
                    :class="[
                        'mt-4 h-16 w-full overflow-hidden rounded-lg border transition-colors duration-200',
                        appearance === value
                            ? 'border-[hsl(32_95%_55%/0.3)]'
                            : 'border-border/60',
                    ]"
                >
                    <div
                        v-if="value === 'light'"
                        class="flex h-full w-full flex-col bg-[hsl(40_33%_99%)]"
                    >
                        <div
                            class="h-3 w-full border-b border-[hsl(30_15%_90%)] bg-[hsl(40_25%_98%)]"
                        />
                        <div class="flex flex-1 gap-1 p-1.5">
                            <div
                                class="h-full w-1/4 rounded bg-[hsl(35_15%_95%)]"
                            />
                            <div class="flex flex-1 flex-col gap-1">
                                <div
                                    class="h-2 w-3/4 rounded bg-[hsl(35_15%_95%)]"
                                />
                                <div
                                    class="h-2 w-1/2 rounded bg-[hsl(35_15%_95%)]"
                                />
                            </div>
                        </div>
                    </div>
                    <div
                        v-else-if="value === 'dark'"
                        class="flex h-full w-full flex-col bg-[hsl(20_10%_6%)]"
                    >
                        <div
                            class="h-3 w-full border-b border-[hsl(20_6%_18%)] bg-[hsl(20_8%_7%)]"
                        />
                        <div class="flex flex-1 gap-1 p-1.5">
                            <div
                                class="h-full w-1/4 rounded bg-[hsl(20_6%_15%)]"
                            />
                            <div class="flex flex-1 flex-col gap-1">
                                <div
                                    class="h-2 w-3/4 rounded bg-[hsl(20_6%_15%)]"
                                />
                                <div
                                    class="h-2 w-1/2 rounded bg-[hsl(20_6%_15%)]"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex h-full w-full overflow-hidden">
                        <!-- Half light, half dark -->
                        <div
                            class="flex h-full w-1/2 flex-col bg-[hsl(40_33%_99%)]"
                        >
                            <div
                                class="h-3 w-full border-b border-[hsl(30_15%_90%)] bg-[hsl(40_25%_98%)]"
                            />
                            <div class="flex flex-1 gap-1 p-1">
                                <div
                                    class="h-full w-1/3 rounded bg-[hsl(35_15%_95%)]"
                                />
                                <div
                                    class="flex-1 rounded bg-[hsl(35_15%_95%)]"
                                />
                            </div>
                        </div>
                        <div
                            class="flex h-full w-1/2 flex-col bg-[hsl(20_10%_6%)]"
                        >
                            <div
                                class="h-3 w-full border-b border-[hsl(20_6%_18%)] bg-[hsl(20_8%_7%)]"
                            />
                            <div class="flex flex-1 gap-1 p-1">
                                <div
                                    class="h-full w-1/3 rounded bg-[hsl(20_6%_15%)]"
                                />
                                <div
                                    class="flex-1 rounded bg-[hsl(20_6%_15%)]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>

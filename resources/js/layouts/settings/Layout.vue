<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Bell,
    ChevronRight,
    Database,
    Lock,
    Settings,
    Shield,
    Sun,
    User,
} from 'lucide-vue-next';

import { useActiveUrl } from '@/composables/useActiveUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { index as dataIndex } from '@/routes/data';
import { edit as editNotifications } from '@/routes/notifications';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Password',
        href: editPassword(),
        icon: Lock,
    },
    {
        title: 'Two-Factor Auth',
        href: show(),
        icon: Shield,
    },
    {
        title: 'Notifications',
        href: editNotifications(),
        icon: Bell,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: Sun,
    },
    {
        title: 'Data & Privacy',
        href: dataIndex(),
        icon: Database,
    },
];

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <div class="px-4 py-8 lg:px-6">
        <!-- Editorial header -->
        <header class="mb-10">
            <div class="flex items-center gap-3 text-muted-foreground">
                <Settings class="h-4 w-4" />
                <span class="text-sm font-medium tracking-wide uppercase"
                    >Account</span
                >
            </div>
            <h1
                class="mt-2 font-display text-3xl font-semibold tracking-tight text-foreground"
            >
                Settings
            </h1>
            <p class="mt-1 text-base text-muted-foreground">
                Manage your profile, security, and preferences
            </p>
        </header>

        <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
            <!-- Refined sidebar navigation - sticky with custom scrollbar -->
            <aside
                class="w-full shrink-0 lg:sticky lg:top-4 lg:h-fit lg:max-h-[calc(100vh-6rem)] lg:w-56 lg:self-start"
            >
                <nav
                    class="settings-sidebar-scroll relative flex flex-row gap-1 overflow-x-auto pb-2 lg:flex-col lg:gap-0.5 lg:overflow-x-visible lg:overflow-y-auto lg:pr-2 lg:pb-0"
                    aria-label="Settings navigation"
                >
                    <!-- Decorative sidebar accent (desktop only) -->
                    <div
                        class="absolute top-0 -left-3 hidden h-full w-px bg-gradient-to-b from-[hsl(32_95%_55%/0.3)] via-border to-transparent lg:block"
                    />

                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="[
                            'group relative flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium whitespace-nowrap transition-all duration-200',
                            urlIsActive(item.href)
                                ? 'bg-card text-foreground shadow-sm ring-1 ring-border/60'
                                : 'text-muted-foreground hover:bg-card/60 hover:text-foreground',
                        ]"
                    >
                        <!-- Active indicator -->
                        <div
                            v-if="urlIsActive(item.href)"
                            class="absolute top-1/2 -left-3 hidden h-8 w-1 -translate-y-1/2 rounded-full bg-[hsl(32_95%_55%)] lg:block"
                        />

                        <!-- Icon container -->
                        <div
                            :class="[
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors duration-200',
                                urlIsActive(item.href)
                                    ? 'bg-[hsl(32_95%_55%/0.12)] text-[hsl(32_80%_45%)] dark:text-[hsl(32_95%_65%)]'
                                    : 'bg-muted/60 text-muted-foreground group-hover:bg-muted group-hover:text-foreground',
                            ]"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                        </div>

                        <span class="flex-1">{{ item.title }}</span>

                        <!-- Arrow indicator for active state -->
                        <ChevronRight
                            v-if="urlIsActive(item.href)"
                            class="hidden h-4 w-4 text-muted-foreground lg:block"
                        />
                    </Link>
                </nav>
            </aside>

            <!-- Main content area with refined styling -->
            <div class="min-w-0 flex-1">
                <section class="max-w-2xl space-y-10">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>

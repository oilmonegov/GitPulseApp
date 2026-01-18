<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-5">
        <!-- Section header with danger styling -->
        <header class="flex items-start gap-4">
            <div
                class="relative flex h-10 w-10 shrink-0 items-center justify-center"
            >
                <!-- Decorative background circle - danger variant -->
                <div
                    class="absolute inset-0 rounded-xl bg-gradient-to-br from-red-500/12 to-red-500/4 ring-1 ring-red-500/15 dark:from-red-500/20 dark:to-red-500/5 dark:ring-red-500/20"
                />
                <AlertTriangle
                    class="relative h-4.5 w-4.5 text-red-600 dark:text-red-400"
                />
            </div>
            <div class="flex-1 pt-0.5">
                <h3
                    class="font-display text-base font-semibold tracking-tight text-foreground"
                >
                    Danger Zone
                </h3>
                <p class="mt-0.5 text-sm leading-relaxed text-muted-foreground">
                    Irreversible account actions
                </p>
            </div>
        </header>

        <!-- Content container with danger styling -->
        <div
            class="relative overflow-hidden rounded-2xl border border-red-200/60 bg-card shadow-sm transition-shadow duration-200 dark:border-red-900/40"
        >
            <!-- Subtle top accent line - danger variant -->
            <div
                class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-red-500/40 to-transparent"
            />

            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <p class="font-medium text-foreground">
                            Delete Account
                        </p>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Once deleted, all your data including commits,
                            statistics, and preferences will be permanently
                            removed. This action cannot be undone.
                        </p>
                    </div>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button
                                variant="destructive"
                                data-test="delete-user-button"
                                class="shadow-sm"
                                >Delete account</Button
                            >
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="ProfileController.destroy.form()"
                                reset-on-success
                                @error="() => passwordInput?.$el?.focus()"
                                :options="{
                                    preserveScroll: true,
                                }"
                                class="space-y-6"
                                v-slot="{
                                    errors,
                                    processing,
                                    reset,
                                    clearErrors,
                                }"
                            >
                                <DialogHeader class="space-y-3">
                                    <DialogTitle
                                        >Are you sure you want to delete your
                                        account?</DialogTitle
                                    >
                                    <DialogDescription>
                                        Once your account is deleted, all of its
                                        resources and data will also be
                                        permanently deleted. Please enter your
                                        password to confirm you would like to
                                        permanently delete your account.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label for="password" class="sr-only"
                                        >Password</Label
                                    >
                                    <Input
                                        id="password"
                                        type="password"
                                        name="password"
                                        ref="passwordInput"
                                        placeholder="Password"
                                    />
                                    <InputError :message="errors.password" />
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            variant="secondary"
                                            @click="
                                                () => {
                                                    clearErrors();
                                                    reset();
                                                }
                                            "
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>

                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                        data-test="confirm-delete-user-button"
                                    >
                                        Delete account
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    </div>
</template>

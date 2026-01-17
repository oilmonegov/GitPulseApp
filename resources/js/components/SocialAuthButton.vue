<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface Props {
    href: string;
    provider: 'github';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
});

const providerConfig = {
    github: {
        icon: 'Github',
        label: 'Continue with GitHub',
    },
};

const config = providerConfig[props.provider];
</script>

<template>
    <Button
        as="a"
        :href="href"
        variant="outline"
        :class="cn('w-full gap-2', props.class)"
    >
        <Icon :name="config.icon" :size="18" />
        {{ config.label }}
    </Button>
</template>

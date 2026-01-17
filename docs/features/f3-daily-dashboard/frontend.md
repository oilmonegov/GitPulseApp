# F3: Frontend Implementation

## Page Component

### Dashboard.vue

```vue
<script setup lang="ts">
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import MetricCard from '@/Components/Dashboard/MetricCard.vue'
import TodayVsYesterday from '@/Components/Dashboard/TodayVsYesterday.vue'
import CommitTimeline from '@/Components/Charts/CommitTimeline.vue'
import LiveCommitFeed from '@/Components/Dashboard/LiveCommitFeed.vue'
import TypeBreakdown from '@/Components/Charts/TypeBreakdown.vue'
import StreakBadge from '@/Components/Dashboard/StreakBadge.vue'
import { useRealtimeMetrics } from '@/Composables/useRealtime'
import type { DailyMetric, Commit, Repository, TrendData } from '@/types/models'

interface Props {
    todayMetrics: DailyMetric
    yesterdayMetrics: DailyMetric
    recentCommits: Commit[]
    weeklyTrend: TrendData[]
    streak: number
    repositories: Repository[]
    hourlyDistribution: number[]
    typeBreakdown: Record<string, number>
}

const props = defineProps<Props>()

// Real-time updates via Laravel Reverb
const {
    todayCommits,
    todayImpact,
    liveCommits,
    isConnected
} = useRealtimeMetrics(props.todayMetrics)

const percentChange = computed(() => {
    const today = todayCommits.value
    const yesterday = props.yesterdayMetrics.total_commits
    if (yesterday === 0) return today > 0 ? 100 : 0
    return Math.round(((today - yesterday) / yesterday) * 100)
})

// Merge live commits with initial data
const allCommits = computed(() => {
    const liveIds = new Set(liveCommits.value.map(c => c.id))
    const filtered = props.recentCommits.filter(c => !liveIds.has(c.id))
    return [...liveCommits.value, ...filtered].slice(0, 20)
})
</script>

<template>
    <AuthenticatedLayout title="Dashboard">
        <!-- Connection Status -->
        <div v-if="isConnected" class="mb-4 flex items-center gap-2 text-sm text-pulse-green">
            <span class="h-2 w-2 rounded-full bg-pulse-green animate-pulse"></span>
            Live updates active
        </div>

        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <MetricCard
                title="Today's Commits"
                :value="todayCommits"
                :change="percentChange"
                icon="code-branch"
                :animate="true"
            />
            <MetricCard
                title="Impact Score"
                :value="todayImpact.toFixed(1)"
                icon="bolt"
            />
            <MetricCard
                title="Active Repos"
                :value="todayMetrics.repos_active"
                icon="folder"
            />
            <StreakBadge :days="streak" />
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Timeline Chart (2/3 width) -->
            <div class="lg:col-span-2">
                <div class="card">
                    <h3 class="text-lg font-semibold mb-4">Weekly Activity</h3>
                    <CommitTimeline :data="weeklyTrend" />
                </div>
            </div>

            <!-- Comparison Widget (1/3 width) -->
            <div>
                <TodayVsYesterday
                    :today="{ ...todayMetrics, total_commits: todayCommits, total_impact: todayImpact }"
                    :yesterday="yesterdayMetrics"
                />
            </div>
        </div>

        <!-- Secondary Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Live Commit Feed (2/3 width) -->
            <div class="lg:col-span-2">
                <LiveCommitFeed :commits="allCommits" />
            </div>

            <!-- Type Breakdown (1/3 width) -->
            <div>
                <TypeBreakdown :data="typeBreakdown" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

---

## Components

### MetricCard.vue

```vue
<script setup lang="ts">
import { computed } from 'vue'
import {
    CodeBranchIcon,
    BoltIcon,
    FolderIcon,
    FireIcon
} from '@heroicons/vue/24/outline'

interface Props {
    title: string
    value: string | number
    change?: number
    icon: 'code-branch' | 'bolt' | 'folder' | 'fire'
    animate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    change: undefined,
    animate: false,
})

const iconComponent = computed(() => {
    const icons = {
        'code-branch': CodeBranchIcon,
        'bolt': BoltIcon,
        'folder': FolderIcon,
        'fire': FireIcon,
    }
    return icons[props.icon]
})

const changeColor = computed(() => {
    if (props.change === undefined || props.change === 0) return 'text-gray-500'
    return props.change > 0 ? 'text-green-600' : 'text-red-600'
})

const changePrefix = computed(() => {
    if (props.change === undefined || props.change === 0) return ''
    return props.change > 0 ? '+' : ''
})
</script>

<template>
    <div class="metric-card">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ title }}
            </span>
            <component
                :is="iconComponent"
                class="h-5 w-5 text-gray-400"
            />
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span
                class="text-3xl font-bold text-gray-900 dark:text-white"
                :class="{ 'animate-pulse': animate }"
            >
                {{ value }}
            </span>
            <span
                v-if="change !== undefined"
                :class="changeColor"
                class="text-sm font-medium"
            >
                {{ changePrefix }}{{ change }}%
            </span>
        </div>
    </div>
</template>
```

### LiveCommitFeed.vue

```vue
<script setup lang="ts">
import { TransitionGroup } from 'vue'
import CommitCard from './CommitCard.vue'
import type { Commit } from '@/types/models'

interface Props {
    commits: Commit[]
}

defineProps<Props>()
</script>

<template>
    <div class="card">
        <h3 class="text-lg font-semibold mb-4">Recent Commits</h3>

        <div class="space-y-3 max-h-96 overflow-y-auto">
            <TransitionGroup name="commit-list">
                <CommitCard
                    v-for="commit in commits"
                    :key="commit.id"
                    :commit="commit"
                />
            </TransitionGroup>

            <p v-if="commits.length === 0" class="text-gray-500 text-center py-8">
                No commits yet today. Push some code!
            </p>
        </div>
    </div>
</template>

<style scoped>
.commit-list-enter-active {
    transition: all 0.3s ease-out;
}

.commit-list-leave-active {
    transition: all 0.2s ease-in;
}

.commit-list-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.commit-list-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

.commit-list-move {
    transition: transform 0.3s ease;
}
</style>
```

### CommitCard.vue

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import type { Commit } from '@/types/models'

interface Props {
    commit: Commit
}

const props = defineProps<Props>()

const timeAgo = computed(() => {
    return formatDistanceToNow(new Date(props.commit.committed_at), { addSuffix: true })
})

const typeColors: Record<string, string> = {
    feat: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    fix: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    refactor: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    docs: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    test: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    chore: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    style: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    perf: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    other: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
}
</script>

<template>
    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span
                    :class="typeColors[commit.commit_type] || typeColors.other"
                    class="px-2 py-0.5 text-xs font-medium rounded"
                >
                    {{ commit.commit_type }}
                </span>
                <span class="text-xs text-gray-500 truncate">
                    {{ commit.repository?.name }}
                </span>
            </div>
            <p class="text-sm text-gray-900 dark:text-white truncate">
                {{ commit.message }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                {{ timeAgo }}
            </p>
        </div>
        <div class="text-right">
            <span class="text-sm font-semibold text-pulse-blue">
                {{ commit.impact_score.toFixed(1) }}
            </span>
            <p class="text-xs text-gray-500">impact</p>
        </div>
    </div>
</template>
```

### TodayVsYesterday.vue

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { ArrowUpIcon, ArrowDownIcon, MinusIcon } from '@heroicons/vue/24/solid'
import type { DailyMetric } from '@/types/models'

interface Props {
    today: DailyMetric
    yesterday: DailyMetric
}

const props = defineProps<Props>()

const comparisons = computed(() => [
    {
        label: 'Commits',
        today: props.today.total_commits,
        yesterday: props.yesterday.total_commits,
    },
    {
        label: 'Impact',
        today: props.today.total_impact,
        yesterday: props.yesterday.total_impact,
    },
    {
        label: 'Active Repos',
        today: props.today.repos_active,
        yesterday: props.yesterday.repos_active,
    },
])

function getChangeIcon(today: number, yesterday: number) {
    if (today > yesterday) return ArrowUpIcon
    if (today < yesterday) return ArrowDownIcon
    return MinusIcon
}

function getChangeColor(today: number, yesterday: number) {
    if (today > yesterday) return 'text-green-600'
    if (today < yesterday) return 'text-red-600'
    return 'text-gray-500'
}
</script>

<template>
    <div class="card">
        <h3 class="text-lg font-semibold mb-4">Today vs Yesterday</h3>

        <div class="space-y-4">
            <div
                v-for="item in comparisons"
                :key="item.label"
                class="flex items-center justify-between"
            >
                <span class="text-sm text-gray-500">{{ item.label }}</span>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-400">
                        {{ typeof item.yesterday === 'number' ? item.yesterday.toFixed(1) : item.yesterday }}
                    </span>
                    <component
                        :is="getChangeIcon(item.today, item.yesterday)"
                        :class="getChangeColor(item.today, item.yesterday)"
                        class="h-4 w-4"
                    />
                    <span class="text-sm font-semibold">
                        {{ typeof item.today === 'number' ? item.today.toFixed(1) : item.today }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
```

---

## Composables

### useRealtime.ts

```typescript
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface CommitProcessedEvent {
    commit_id: number
    sha: string
    message: string
    repository: string
    impact_score: number
    committed_at: string
    commit_type: string
}

interface DailyMetricsEvent {
    total_commits: number
    total_impact: number
    repos_active: number
}

interface DailyMetric {
    total_commits: number
    total_impact: number
}

interface LiveCommit {
    id: number
    message: string
    repository: { name: string }
    impact_score: number
    committed_at: string
    commit_type: string
}

export function useRealtimeMetrics(initialMetrics: DailyMetric) {
    const todayCommits = ref(initialMetrics.total_commits)
    const todayImpact = ref(initialMetrics.total_impact)
    const liveCommits = ref<LiveCommit[]>([])
    const isConnected = ref(false)

    onMounted(() => {
        const userId = usePage().props.auth.user.id

        window.Echo.private(`user.${userId}`)
            .listen('CommitProcessed', (e: CommitProcessedEvent) => {
                // Update counters
                todayCommits.value++
                todayImpact.value += e.impact_score

                // Add to live feed
                liveCommits.value.unshift({
                    id: e.commit_id,
                    message: e.message,
                    repository: { name: e.repository },
                    impact_score: e.impact_score,
                    committed_at: e.committed_at,
                    commit_type: e.commit_type,
                })

                // Keep only last 20
                if (liveCommits.value.length > 20) {
                    liveCommits.value.pop()
                }
            })
            .listen('DailyMetricsUpdated', (e: DailyMetricsEvent) => {
                todayCommits.value = e.total_commits
                todayImpact.value = e.total_impact
            })

        // Track connection status
        window.Echo.connector.pusher.connection.bind('connected', () => {
            isConnected.value = true
        })

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            isConnected.value = false
        })

        isConnected.value = window.Echo.connector.pusher.connection.state === 'connected'
    })

    onUnmounted(() => {
        const userId = usePage().props.auth.user.id
        window.Echo.leave(`user.${userId}`)
    })

    return { todayCommits, todayImpact, liveCommits, isConnected }
}
```

---

## Charts

### CommitTimeline.vue

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js'
import type { TrendData } from '@/types/models'

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
)

interface Props {
    data: TrendData[]
}

const props = defineProps<Props>()

const chartData = computed(() => ({
    labels: props.data.map(d => d.date),
    datasets: [
        {
            label: 'Commits',
            data: props.data.map(d => d.total_commits),
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.3,
        },
        {
            label: 'Impact',
            data: props.data.map(d => d.total_impact),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: true,
            tension: 0.3,
            yAxisID: 'y1',
        },
    ],
}))

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
        },
    },
    scales: {
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            title: {
                display: true,
                text: 'Commits',
            },
        },
        y1: {
            type: 'linear' as const,
            display: true,
            position: 'right' as const,
            title: {
                display: true,
                text: 'Impact',
            },
            grid: {
                drawOnChartArea: false,
            },
        },
    },
}
</script>

<template>
    <div class="h-64">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
```

---

## TypeScript Types

### types/models.ts

```typescript
export interface DailyMetric {
    date: string
    total_commits: number
    total_impact: number
    repos_active: number
    hours_active: number
    commit_types: Record<string, number>
    hourly_distribution: number[]
    additions: number
    deletions: number
}

export interface Commit {
    id: number
    sha: string
    message: string
    author_name: string
    author_email: string
    committed_at: string
    additions: number
    deletions: number
    files_changed: number
    commit_type: string
    impact_score: number
    is_merge: boolean
    repository?: Repository
}

export interface Repository {
    id: number
    name: string
    full_name: string
    is_active: boolean
}

export interface TrendData {
    date: string
    total_commits: number
    total_impact: number
}
```
